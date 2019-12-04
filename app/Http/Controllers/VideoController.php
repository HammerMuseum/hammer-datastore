<?php

namespace App\Http\Controllers;

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
     * Get a video by its Datastore ID
     *
     * @param $id
     * @return VideoResource
     */
    public function getById(Request $request, $id)
    {
        $video = new VideoResource(
            Video::findOrFail($id)
        );
        return response()->json($video, 200);
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
