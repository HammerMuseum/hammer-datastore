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
            $item['links'] = ['self' => ['href' => config('app.url') . '/api/videos/' . $item['title_slug']]];
            return $item;
        }, $response['result']);
        return $response;
    }

    /**
     * Build response array for a single video.
     */
    public function get($id)
    {
        $response = $this->searchManager->term(['title_slug' => $id]);
        if (!empty($response)) {
            // Get video URL
            $contentUrl = $response['result'][0]['video_url'] . '/url';
            $playbackUrl = $this->getPlaybackUrl($contentUrl);
            $response['result'][0]['src'] = $playbackUrl;
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
