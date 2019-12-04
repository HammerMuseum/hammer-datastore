<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use App\Video;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Video::class, function (Faker $faker) {
    return [
        'asset_id' => 1234,
        'title' => 'Sample video',
        'description' => 'Description of the sample video.',
        'thumbnail_url' => 'http://url.com',
        'video_url' => 'http://url.com',
        'date_recorded' => '2019-01-01',
        'duration' => '01:01:01'
    ];
});
