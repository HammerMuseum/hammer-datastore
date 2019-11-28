<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        $testData = [
            'message' => 'Success'
        ];

        return response()->json($testData, 200);
    }
}
