<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Video
 * @package App
 */
class Video extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'asset_id',
        'title',
        'description',
        'date',
        'duration',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
    ];
}
