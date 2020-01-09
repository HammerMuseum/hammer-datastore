<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Video;
use App\Search;

/**
 * Class ApiController
 * @package App\Http\Controllers
 */
class ApiController extends Controller
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
     * Create a new video resource
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        // Check if there is already a video with this asset ID
        $params = [
            'index' => config('app.es_index'),
            'id'    => $request->asset_id,
            'body'  => $request->except('api_token'),
        ];

        try {
            $client = $this->search->getClient();
            $response = $client->index($params);
            if ($response) {
                return response()->json([
                    'success' => true,
                    'message' => 'Resource ' . $response['result'],
                    'id' => $response['_id']
                ], 201);
            }
        } catch (\Throwable $th) {
            $status = $th->getCode();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'status' => $status,
            ], $status);
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
        // Check if there is already a video with this asset ID
        $params = [
            'index' => config('app.es_index'),
            'id'    => $assetId,
        ];

        try {
            $client = $this->search->getClient();
            $response = $client->get($params);
            if (!$response['found']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource' . $assetId .' not found.',
                    'id' => null
                ], 404);
            }

            $params['body'] = $request->except('api_token');
            $response = $client->index($params);
            
            if ($response['result']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Resource' . $response['result'],
                    'id' => $response['_id']
                ], 200);
            }
        } catch (\Throwable $th) {
            $status = $th->getCode();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'status' => $status,
            ], $status);
        }
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
        // Check if there is already a video with this asset ID
        $params = [
            'index' => config('app.es_index'),
            'id'    => $assetId
        ];

        try {
            $client = $this->search->getClient();
            $response = $client->delete($params);
            if ($response) {
                return response()->json([
                    'success' => true,
                    'message' => 'Resource ' . $response['result'],
                    'id' => $response['_id']
                ], 200);
            }
        } catch (\Throwable $th) {
            $status = $th->getCode();
            return response()->json([
                'success' => false,
                'message' => 'Unable to delete resource',
                'status' => $status,
            ], $status);
        }
    }
}
