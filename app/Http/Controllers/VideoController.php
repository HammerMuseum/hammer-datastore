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
     * @param $id
     * @return VideoResource
     */
    public function getById(Request $request, $id)
    {
        try {
            $video = $this->search->term(['asset_id' => $id]);
            if (count($video) && count($video) > 0) {
                return response()->json([
                    'success' => true,
                    'data' => $video
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
     * @param $field
     * @param $id
     * @return array|bool
     */
    public function getTranscript($id)
    {
        try {
            $response = $this->search->field('transcription', $id);
            if (count($response)) {
                return response()->json([
                    'success' => true,
                    'data' => $response
                ], 200);
            }
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
        $videoCollection = collect(
            $items
        );
        $count = $videoCollection->count();
        if ($count > 0) {
            return response()->json([
                'success' => true,
                    'data' => $videoCollection,
            ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => 'No video resources found.'
        ], 404);
    }
}
