#!/usr/bin/env -S uv run --script
# /// script
# requires-python = ">=3.11"
# dependencies = [
#   "httpx",
# ]
# ///
"""
Recreates the test index from the video template and the sample data fixture.
"""

import sys
from pathlib import Path
import httpx

if len(sys.argv) < 2:
    print(f"Usage: {sys.argv[0]} <elasticsearch host> [index name]", file=sys.stderr)
    sys.exit(1)

host = sys.argv[1].rstrip("/")
index = sys.argv[2] if len(sys.argv) > 2 else "video_testing"
root = Path(__file__).resolve().parent.parent.parent

template_file = root / "elasticsearch/templates/video_.json"
sample_file = root / "utils/sample-data/sample-elastic.json"

with httpx.Client(base_url=host, timeout=60.0) as client:
    # 1. Put index template
    with template_file.open("rb") as f:
        res = client.put(
            "/_index_template/template_video",
            headers={"Content-Type": "application/json"},
            content=f,
        )
        res.raise_for_status()

    # 2. Delete existing index
    client.delete(f"/{index}", params={"ignore_unavailable": "true"})

    # 3. Bulk load sample data
    with sample_file.open("rb") as f:
        bulk_res = client.post(
            f"/{index}/_bulk",
            params={"refresh": "true"},
            headers={"Content-Type": "application/x-ndjson"},
            content=f,
        )
        bulk_res.raise_for_status()

    if bulk_res.json().get("errors") is not False:
        print(f"Bulk load into {index} failed: {bulk_res.text[:1000]}", file=sys.stderr)
        sys.exit(1)

    # 4. Success message
    count_res = client.get(f"/{index}/_count")
    count = count_res.json()["count"]
    print(f"Loaded {count} documents into {index}.")
