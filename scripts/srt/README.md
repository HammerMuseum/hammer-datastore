# Convert an SRT to transcript files & make available in Hammer Channel

## Convert SRT to transcript files

Use the helper script to convert an SRT file into VTT, JSON & TXT, ready to be uploaded to the Hammer Channel datastore.

Note: the SRT file must be named with the video ID, e.g. `1234.srt`.

### Requirements

-   python3 (written with 3.11.1, but 3.9 onwards should work)
    -   Test with `python3 -V`
-   ffmpeg (written with 5.1.2)
    -   Test with `ffmpeg -version`

### Usage

To generate all file formats, and write them to `./manual_transcripts` on your machine:

```bash
# for a specific file
python3 srt.py 1234.srt

# batch for all SRTs in a dir
python3 srt.py *.srt
```

## Upload transcript files

See the Hammer Channel internal Wiki for full details.
