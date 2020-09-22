<?php

namespace App;

use Illuminate\Support\Facades\Storage;

/**
 * Class TranscriptManager.
 * @package App
 */
class TranscriptManager
{
    /**
     * Return response for a single transcript in JSON format.
     */
    public function get($format, $id)
    {
        $path = "$format/$id";
        $exists = Storage::disk('transcripts')->exists($path);

        if ($exists) {
            $content = Storage::disk('transcripts')->get($path);

            if ($format === 'vtt') {
                $type = 'text/vtt';
            }

            if ($format === 'json') {
                $type = 'application/json';
            }

            return response($content)->header('Content-Type', $type);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No transcription available.'
            ], 404);
        }
    }
}
