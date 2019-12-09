<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Video;

/**
 * Class ApiController
 * @package App\Http\Controllers
 */
class ApiController extends Controller
{
    /**
     * Create a new video resource
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        // Check if there is already a video with this asset ID
        $assetId = $request->asset_id;
        $videoExists = Video::where('asset_id', $assetId)->get();
        if (!count($videoExists)) {
            $video = Video::create($request->all());
            $video->save();
            return response()->json([
                'success' => true,
                'message' => 'Video asset added to datastore.',
                'data_id' => $video->id
            ], 201);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Video asset with ID ' . $assetId . ' already exists in datastore.',
                'data_id' => null
            ], 200);
        }
    }

    /**
     * Update an existing video resource
     *
     * @param Request $request
     * @param $assetId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $assetId)
    {
        // Check that the asset to be updated actually exists
        $video = Video::where('asset_id', $assetId)->first();
        if (!is_null($video) && isset($video->id)) {
                $videoId = $video->id;
                $video->update($request->all());
                return response()->json([
                    'success'  => true,
                    'message' => 'Video asset successfully updated',
                    'data_id' => $videoId
                ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => 'Unable to find video asset in the datastore to update.',
            'data_id' => null
        ], 200);
    }

    /**
     * Delete a video resource
     *
     * @param Request $request
     * @param $assetId
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $assetId)
    {
        // Check that the asset to be updated actually exists
        $video = Video::where('asset_id', $assetId)->first();
        if (!is_null($video) && isset($video->id)) {
            try {
                $video->delete();
                return response()->json([
                   'success' => true,
                    'message' => 'Video asset successfully deleted.',
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to delete video asset: ' . $e->getMessage(),
                ], 200);
            }
        }
        return response()->json([
            'success' => false,
            'message' => 'Unable to find video asset to delete.',
        ], 200);
    }
}
