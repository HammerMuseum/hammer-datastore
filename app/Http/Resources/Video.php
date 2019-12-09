<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class Video
 * @package App\Http\Resources
 */
class Video extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'asset_id' => $this->asset_id,
            'title' => $this->title,
            'description' => $this->description,
            'date_recorded' => $this->date_recorded,
            'video_url' => $this->video_url,
            'duration' => $this->duration
        ];
    }
}
