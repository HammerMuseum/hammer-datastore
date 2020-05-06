<?php

namespace App;

use App\Search;
use GuzzleHttp\Client;

/**
 * Class VideoManager.
 * @package App
 */
class VideoManager
{
    /**
     * Video manager implementation.
     *
     * @var Search
     */
    protected $searchManager;

    public function __construct(Search $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    /**
     * Build response array for a single video.
     */
    public function getAll($params)
    {
        $response = $this->searchManager->matchAll($params);
        $response['result'] = array_map(function ($item) {
            $item['links'] = ['self' => ['href' => config('app.url') . '/api/videos/' . $item['asset_id']]];
            return $item;
        }, $response['result']);
        return $response;
    }

    /**
     * Returns the response array for a single video.
     *
     * If you need to perform any transformations on the
     * output you can apply them here.
     */
    public function get($id)
    {
        $response = $this->searchManager->term(['asset_id' => $id]);
        if (!empty($response)) {
            $result = $response['result'][0];
            $result['src'] = $this->getPlaybackUrl($result['video_url'] . '/url');
            $response['result'][0] = $result;
            $response['links'] = ['self' => ['href' => config('app.url') . '/api/videos/' . $id]];
        }
        return $response;
    }

    /**
     * Helper to fetch the S3 playback URL for an asset.
     *
     * @param string $contentUrl
     * @return \Psr\Http\Message\StreamInterface
     */
    public function getPlaybackUrl($contentUrl)
    {
        try {
            $client = new Client();
            $response = $client->request('GET', $contentUrl);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            Log::error('Failed to get playback URL', ['message', $th->getMessage()]);
            abort(503);
        }

        return $response->getBody()->getContents();
    }
}
