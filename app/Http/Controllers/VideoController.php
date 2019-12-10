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
            if (count($video)) {
                return response()->json($video[0], 200);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json('404: Resource not found.', 404);
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
        return response()->json($videoCollection, 200);
    }
}