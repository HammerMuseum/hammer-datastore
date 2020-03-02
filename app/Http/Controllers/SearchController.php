<?php

namespace App\Http\Controllers;

use App\Search;
use Illuminate\Http\Request;

/**
 * Class SearchController
 * @package App\Http\Controllers]
 */
class SearchController extends Controller
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
     * @param Request $request
     *  The request from the URL
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function search(Request $request)
    {
        $queryParams = $request->all();
        $results = $this->search->match($queryParams);

        if ($results) {
            return response()->json([
                'success' => true,
                'data' => $results['result'],
                'pages' => $results['pages'],
                'aggregations' => $results['aggregations'],
                'message' => false
            ], 200);
        }
        return response()->json([
            'success' => false,
            'data' => [],
            'pages' => [],
            'aggregations' => [],
            'message' => 'No results found.'
        ], 404);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function term(Request $request)
    {
        $queryParams = $request->all();
        $results = $this->search->term($queryParams);
        return response()->json([
            'success' => true,
            'data' => $results['result'],
            'pages' => [],
            'aggregations' => $results['aggregations'],
            'message' => false
        ], 200);
    }
}
