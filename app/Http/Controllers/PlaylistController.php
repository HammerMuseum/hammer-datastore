<?php

namespace App\Http\Controllers;

use App\PlaylistManager;

class PlaylistController extends Controller
{
    /** @var Playlist */
    protected $playlistManager;

    /**
     * Playlist constructor.
     */
    public function __construct(PlaylistManager $playlistManager)
    {
        $this->playlistManager = $playlistManager;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result = $this->playlistManager->getAll();
        return response()->json([
            'success' => true,
            'data' => $result['result'],
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $name
     * @return \Illuminate\Http\Response
     */
    public function show($name)
    {
        $result = $this->playlistManager->get($name);
        if ($result) {
            return response()->json([
                'success' => true,
                'data' => $result['result']
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'data' => [],
                'pages' => [],
                'aggregations' => [],
            ], 404);
        }
    }
}
