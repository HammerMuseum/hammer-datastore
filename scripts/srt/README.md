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

The processed files need to then be moved to the backend server, so they're available to the Harvester.

Find the server user & IP at the [Cogapp Wiki](http://wiki.office.cogapp.com/wiki/index.php/Hammer_Video_Archive#Servers), and the contents of the `.pem` file referenced below in [TPM](http://tpm.office.cogapp.com/index.php/pwd/view/656).

```bash
# you must be on the VPN to run this
rsync -chavzP --stats -e "ssh -i ~/.ssh/hammer/hammer-backend.pem" manual_transcripts/* user@ip:/var/manual_transcripts/
```

Note: if `/var/manual_transcripts/` doesn't exist on the server you will need to SSH on and create it with: `mkdir -m 777 /var/manual_transcripts`.
