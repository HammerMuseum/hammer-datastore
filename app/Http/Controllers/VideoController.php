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
    public function getVideo(Request $request, $id)
    {
        try {
            $result = $this->videoManager->get($id);
            if ($result && count($result['result']) > 0) {
                return response()->json([
                    'success' => true,
                    'data' => $result['result'],
                    'pages' => $result['pages'],
                    'aggregations' => $result['aggregations'],
                ], 200);
            }
            return response()->json([
                'success' => false,
                'data' => false,
                'pages' => [],
                'aggregations' => [],
                'message' => 'Video not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'pages' => [],
                'aggregations' => [],
                'message' => 'An error has occured.'
            ], 503);
        }
    }

    /**
     * @param $id
     * @return array|bool
     */
    public function getVideoTranscript(Request $request, $id)
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

    /**
     * Get all videos.
     */
    public function getAllVideos(Request $request)
    {
        $requestParams = $request->all();
        $items = $this->videoManager->getAll($requestParams);
        $result = collect(
            $items
        );
        $count = $result->count();
        if ($count > 0) {
            return response()->json([
                'success' => true,
                'data' => $result['result'],
                'pages' => $result['pages'],
                'aggregations' => $result['aggregations'],
            ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => 'No video resources found.',
            'pages' => [],
            'aggregations' => [],
            'data' => []
        ], 404);
    }
}
