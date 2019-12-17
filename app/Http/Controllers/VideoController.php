<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Resources\Video as VideoResource;
use App\Http\Resources\VideoCollection;
use App\Video;

/**
 * Class VideoController
 * @package App\Http\Controllers
 */
class VideoController extends Controller
{
    /**
     * Get a video by its asset ID
     *
     * @param $id
     * @return VideoResource
     */
    public function getById(Request $request, $id)
    {
        try {
            $video = Video::where('asset_id', $id)->get()->take(1);
            if (count($video) && count($video) > 0) {
                return response()->json([
                    'success' => true,
                    'data' => $video[0]
                ], 200);
            }
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.'
            ], 404);
        } catch (ModelNotFoundException $e) {
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
    public function getAllVideos()
    {
        $videoCollection = new VideoCollection(
            Video::all()
        );
        if (isset($videoCollection['data']) && !empty($videoCollection['data'])) {
            return response()->json($videoCollection, 200);
        }
        return response()->json(['success' => false, 'message' => 'No video resources found.'], 404);
    }
}
