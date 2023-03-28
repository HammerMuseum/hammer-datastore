#!/usr/bin/env python3

import argparse
from pathlib import Path
import subprocess
from utils import time_units_to_ms, mkdir
from typing import Dict, TypedDict
import json


class Word(TypedDict):
    duration: int
    time: int
    value: str
    paragraphId: str
    speaker: str
    strikethrough: bool


class SpeakerData(TypedDict):
    name: str


class JSONOutput(TypedDict):
    words: list[Word]
    speakers: Dict[str, SpeakerData]


class ParsedTime(TypedDict):
    start_time: int
    end_time: int


class ParsedLine(ParsedTime):
    line_number: int
    text: str


class SRT:
    TERMINAL_PUNCTUATION_MARKS = [".", "!", "?", "‽"]
    speakerId = "placeholderId"

    def __init__(self, output_dir="manual_transcripts") -> None:
        # set output_dir relative to this script to avoid creating dirs in strange places.
        self.output_dir = Path(__file__).parent.joinpath(output_dir)

    def load(self, srt_filepath: str) -> None:
        self.file = Path(srt_filepath)
        with open(srt_filepath) as fh:
            self.data = fh.read().splitlines()

    def get_linebreaks(self) -> list[int]:
        """Return line number of empty lines i.e. the gaps between lines"""
        return [i for i, line in enumerate(self.data) if line == ""]

    def get_lines(self) -> list[ParsedLine]:
        lines = []
        linebreaks = self.get_linebreaks()

        for i, linebreak in enumerate(linebreaks):
            if i == 0:
                # first line
                line = (i, linebreak)
                lines.append(self.data[line[0] : line[1]])
            else:
                # middle line, so get last linebreak + 1 line
                line = (linebreaks[i - 1] + 1, linebreak)
                lines.append(self.data[line[0] : line[1]])

        # get last line
        line = (linebreak + 1, len(self.data))
        lines.append(self.data[line[0] :])

        return [SRT.line_parser(line) for line in lines]

    @staticmethod
    def srt_time_to_ms(srt_time: str) -> int:
        hours, minutes, seconds = srt_time.split(":")
        seconds, milliseconds = seconds.split(",")
        return time_units_to_ms(
            int(hours), int(minutes), int(seconds), int(milliseconds)
        )

    @staticmethod
    def srt_time_line_parser(srt_time_line: str) -> ParsedTime:
        start, end = srt_time_line.split(" --> ")
        return ParsedTime(
            start_time=SRT.srt_time_to_ms(start),
            end_time=SRT.srt_time_to_ms(end),
        )

    @staticmethod
    def line_parser(line: list[str]) -> ParsedLine:
        parsed_time = SRT.srt_time_line_parser(line[1])
        return ParsedLine(
            line_number=int(line[0]),
            start_time=parsed_time["start_time"],
            end_time=parsed_time["end_time"],
            text="\n".join(line[2:]),
        )

    @staticmethod
    def line_to_json(parsed_line: ParsedLine, paragraphId: int = 0) -> Word:
        return Word(
            duration=parsed_line["end_time"] - parsed_line["start_time"],
            time=parsed_line["start_time"],
            value=parsed_line["text"],
            paragraphId=f"p{paragraphId}",
            speaker=SRT.speakerId,
            strikethrough=False,
        )

    def json(self) -> JSONOutput:
        words = []
        paragraphId = 0
        for line in self.get_lines():
            words.append(SRT.line_to_json(line, paragraphId))
            # if this line is the end of a sentence, next line is a new paragraph
            if line["text"][-1] in SRT.TERMINAL_PUNCTUATION_MARKS:
                paragraphId += 1

        return JSONOutput(
            words=words,
            speakers={SRT.speakerId: {"name": ""}},
        )

    def output_json(self) -> None:
        mkdir(self.output_dir)
        filename = f"{self.output_dir}/{self.file.stem}.json"
        print(f"Writing: {filename}")
        with open(filename, "w") as fp:
            json.dump(self.json(), fp)

    def output_vtt(self) -> None:
        mkdir(self.output_dir)
        filename = f"{self.output_dir}/{self.file.stem}.vtt"
        print(f"Writing: {filename}")
        command = [
            "ffmpeg",
            "-y",  # overwrite
            "-hide_banner",
            "-loglevel",
            "error",
            "-i",
            self.file,
            "-f",
            "webvtt",
            filename,
        ]
        subprocess.run(command)

    def output_txt(self) -> None:
        mkdir(self.output_dir)
        filename = f"{self.output_dir}/{self.file.stem}.txt"
        json = self.json()
        # unique list of paragraphIds & preserve order
        paragraphIds = [*dict.fromkeys([i["paragraphId"] for i in json["words"]])]

        # for each paragraphId, get the words belonging to it, and join with ' ' (a space)
        paragraphs = [
            " ".join(
                [
                    word["value"]
                    for word in json["words"]
                    if word["paragraphId"] == paragraphId
                ]
            )
            for paragraphId in paragraphIds
        ]

        print(f"Writing: {filename}")
        with open(filename, "wt") as fp:
            fp.write("\n\n".join(paragraphs))


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("file", nargs='*')
    args = parser.parse_args()

    for file in args.file:
        print(f"Processing: {file}")
        srt = SRT()
        srt.load(file)
        srt.output_json()
        srt.output_vtt()
        srt.output_txt()
