<!-- Generate a new file using -->
<!-- sed -e "s/\Convert and upload SRT files/My story/" -e "s/\184461678/156128780/" -e "s/\convert-upload-srt-184461678/`git_current_branch`/g" template.md | tee "`git_current_branch`.md" -->

# Convert and upload SRT files

## Pivotal story

[Convert and upload SRT files](https://www.pivotaltracker.com/story/show/184461678)

## Git branch

[convert-upload-srt-184461678](https://github.com/HammerMuseum/hammer-video/convert-upload-srt-184461678)

## Story description

Outside of the normal workflow, subtitles are sometimes created as SRT files.

We need to convert SRTs & upload them so that they can be served as other ones are.

We have to generate:

-   a VTT file (in-line subtitles)
-   a JSON file (transcript tab)
-   a TXT file (for Elasticsearch)
-   Make available to the Harvester (so that Elasticsearch is aware of the data)

This will be an ad-hoc task, so output could be some helper script that can be run as required.

## Required output

### SRT to VTT ([example](https://channel.hammer.ucla.edu/api/videos/1775/transcript?format=vtt))

Easy, with ffmpeg.

`ffmpeg -i srt_file vtt_file`

### SRT to JSON ([example](https://channel.hammer.ucla.edu/api/videos/1775/transcript?format=json))

More difficult.

Fields required by the frontend; `words`, `speakers`:

```js
// snippet of existing method that we want our manual transcripts to work within
fetchTranscript() {
      axios
        .get(`/api/videos/${this.$route.params.id}/transcript?format=json`)
        .then((response) => {
          // Group word level data into paragraph level data.
          const { words, speakers } = response.data;
          const map = new Map(Array.from(words, (obj) => [obj.paragraphId, []]));
          words.forEach((obj) => map.get(obj.paragraphId).push(obj));
          const paragraphs = Array.from(map.values());

          this.transcript = { paragraphs, speakers };
          this.transcriptLoaded = true;
        }).catch((err) => {
          this.transcriptError = true;
          console.error(err);
        });
    },
```

```js
// an example of a 'word' object
words[0] = {
    duration: 299,
    time: 4730,
    paragraphId: "p0-0",
    value: "Hi.",
    speaker: "49xq07i",
    strikethrough: false,
};
```

```js
// an example of a 'speaker' object
speakers[0] = { "49xq07i": { name: "Guillermo del Toro" } };
```

### SRT to TXT

Easy, if we have the data structured as described for the JSON output.

## Summary of approach

Write a wrapper script to:

1. Read an SRT (input format: `{id}.srt`).
1. Generate the VTT output with `ffmpeg`.
1. Generate the JSON output, where:

    - Each SRT line is a "word", because we can't guarantee the timestamps of each word if we were to split each line into a word.
    - Set initial paragraphId to `0` and iterate over each line.
        - If the last char of the line is a [terminal punctuation mark](https://en.wikipedia.org/wiki/Terminal_punctuation), the next paragraphId is incremented by 1.
    - No speaker defined

    for each SRT line (AKA a "word"):

    ```json
    {
        "duration": "`{end-start (ms)}`",
        "time": "`{start (ms)}`",
        "value": "`{text}`",
        "paragraphId": "`p{paragraphId}`",
        "speaker": "placeholderId",
        "strikethrough": "false"
    }
    ```

    speakers:

    ```json
    { "placeholderId": { "name": "" } };
    ```

1. Generate the TXT output where each line from paragraph joined as sentence, and each paragraph is separated by a newline.

Then output the files under a new dir: `manual_transcripts`, i.e.:

-   `manual_transcripts/{id}.vtt`
-   `manual_transcripts/{id}.json`
-   `manual_transcripts/{id}.txt`

These would then be uploaded to the [datastore server](http://wiki.office.cogapp.com/wiki/index.php/Hammer_Video_Archive#Servers) under some chosen dir, i.e. `/var/manual_transcripts`.

In preparation, on the server, create a writable dir: `mkdir -m 777 /var/manual_transcripts`

### Making files available to the harvester

Currently the Harvester hits the Trint API for all 3 files above.

In our current setup the [`TranscriptionProcessor.process` method](https://github.com/HammerMuseum/hammer-datastore/blob/develop/harvester/harvester/processors/TranscriptionProcessor.py#L105-L119)
checks if the `record` has a key of `transcription` (where the value would be a Trint ID), if not if skips to the next record.

We can alter this so that if the `transcription` key doesn't exist (i.e. no Trint transcript):

-   the processor checks some local dir (i.e. `/var/manual_transcripts`) for the manually uploaded files using `row['asset_id']`.
-   if it exists, it reads the file and assigns it to the `row`:

    ```python
    # No Trint ID defined; check for files manually added to the server
    for file_ext in ['vtt', 'json', 'txt']:
      # e.g. custom_transcript = Path('/manual_transcripts/1234.vtt')
      custom_transcript = Path(f'/manual_transcripts/{row["asset_id"]}.{file_ext}')
      if custom_transcript.exists():
        with open(custom_transcript) as fh:
          row[f'{field}_{file_ext}'] = fh.read()
    ```

## Documentation required

-   How to run the script to convert an SRT into the various files.
-   How to upload transcript files to the server (to make available on the site).
