<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Video as VideoResource;
use App\Http\Resources\VideoCollection;
use App\Search;

/**
 * Class VideoController
 * @package App\Http\Controllers
 */
class VideoController extends Controller
{
    /** @var Search */
    protected $search;

    /**
     * SearchController constructor.
     * @param Search $search
     */
    public function __construct(
        Search $search
    ) {
        $this->search = $search;
    }

    /**
     * Get a video by its asset ID
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getById(Request $request, $id)
    {
        try {
            $result = $this->search->term(['asset_id' => $id]);
            if (count($result) && count($result) > 0) {
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
                'message' => 'Video not found.'
            ], 404);
        }
    }

    /**
     * @param $id
     * @return array|bool
     */
    public function getTranscript($id)
    {
        try {
            $result = $this->search->field('transcription', $id);
            if (count($result)) {
                return response()->json([
                    'success' => true,
                    'data' => $result['result'],
                    'pages' => $result['pages'],
                    'aggregations' => $result['aggregations'],
                ], 200);
            }
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.'
            ], 404);
        }
    }

    /**
     * Get all videos
     *
     * @return VideoCollection
     */
    public function getAllVideos(Request $request)
    {
        $requestParams = $request->all();
        $items = $this->search->matchAll($requestParams);
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
