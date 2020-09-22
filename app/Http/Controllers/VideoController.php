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
     * Get a video by its asset ID
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showRelated(Request $request, $id)
    {
        $response = $this->videoManager->getRelated($id);
        return response()->json([
            'success' => true,
            'data' => $response['result']
        ], 200);
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
