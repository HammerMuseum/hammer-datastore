<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Search;
use App\VideoManager;

/**
 * Class VideoController
 * @package App\Http\Controllers
 */
class VideoController extends Controller
{
    /** @var Search */
    protected $search;

    /** @var VideoManager */
    protected $videoManager;

    /**
     * Playlist constructor.
     */
    public function __construct(Search $search, VideoManager $videoManager)
    {
        $this->search = $search;
        $this->videoManager = $videoManager;
    }

    /**
     * Get a video by its asset ID
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $response = $this->videoManager->get($id);
        if ($response['result']->count()) {
            return response()->json([
                'success' => true,
                'data' => $response['result']
            ], 200);
        }
        return response()->json([
            'success' => false,
            'data' => false,
            'message' => 'Video not found.'
        ], 404);
    }

    /**
     * @param $id
     * @return array|bool
     */
    public function showTranscript(Request $request, $id)
    {
        // Default is to return value of transcription field.
        // Other formats can be requested via this parameter
        // if available.
        $format = $request->query('format');
        if (!in_array($format, ['json', 'vtt', 'txt'])) {
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
                    } else if ($format === 'txt') {
                        $response = response($doc[$field], 200);
                        $response->header('Content-Type', 'text/plain');
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

    /**
     * Get all videos.
     */
    public function index(Request $request)
    {
        $requestParams = $request->all();
        $response = $this->videoManager->getAll($requestParams);
        if ($response['result']->count()) {
            return response()->json([
                'success' => true,
                'data' => $response['result']->all(),
                'pages' => $response['pages'],
                'aggregations' => $response['aggregations'],
            ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => 'No items found.',
            'pages' => [],
            'aggregations' => [],
            'data' => []
        ], 200);
    }
}
