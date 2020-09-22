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
            $fullpath = Storage::disk('transcripts')->path($path);

            if ($format === 'vtt') {
                return response()
                    ->file($fullpath, ['Content-Type' => 'text/vtt']);
            }

            if ($format === 'json') {
                return response()
                    ->file($fullpath, ['Content-Type' => 'application/json']);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No transcription available.'
            ], 404);
        }
    }
}
