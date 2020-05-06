<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Search;

/**
 * Class TranscriptController
 * @package App\Http\Controllers
 */
class TranscriptController extends Controller
{
    /** @var Search */
    protected $search;

    /**
     * Constructor.
     */
    public function __construct(Search $search)
    {
        $this->search = $search;
    }

    /**
     * @param $id
     * @return array|bool
     */
    public function show(Request $request, $id)
    {
        // Default is to return value of transcription field.
        // Other formats can be requested via this parameter
        // if available.
        $format = $request->query('format');
        if (!in_array($format, ['json', 'vtt'])) {
            return response()->json([
                'success' => false,
                'message' => 'Requested format is not a valid option.'
            ], 400);
        } else {
            $field = 'transcription_' . $format;
        }

        try {
            $result = $this->search->field($field, $id);
            if ($doc = $result['result'][0]) {
                if (!empty($doc[$field])) {
                    if ($format === 'vtt') {
                        $response = response($doc[$field], 200);
                        $response->header('Content-Type', 'text/vtt');
                    } else {
                        return response()->json([
                            'success' => true,
                            'data' => json_decode($doc[$field]),
                        ], 200);
                    }
                    return $response;
                }
            }
            return response()->json([
                'success' => false,
                'message' => 'No transcription available.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'There was an error.'
            ], 503);
        }
    }
}
