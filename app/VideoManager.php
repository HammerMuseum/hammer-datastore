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
        $collection = collect($response['result'])->map(function ($item) {
            unset($item['video_url']);
            $item['links'] = ['self' => ['href' => config('app.url') . '/api/videos/' . $item['asset_id']]];
            return $item;
        });
        $response['result'] = $collection;
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
            $collection = collect($response['result'])->map(function ($item) {
                $item['src'] = $this->getVideoSrc($item['video_url'] . '/url');
                unset($item['video_url']);
                return $item;
            });
            $response['result'] = $collection;
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
    public function getVideoSrc($contentUrl)
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
