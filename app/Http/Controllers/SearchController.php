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
    protected $availableFacets = [
        'year'
    ];

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
     * @param $term
     *  The term to search
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request, $term)
    {
        if (!is_null($term)) {
            $queryParams = $request->all();
            $results = $this->search->match($term, $queryParams);

            if ($results) {
                return response()->json([
                    'success' => true,
                    'data' => $results,
                    'message' => false
                ], 200);
            }
        }
        return response()->json([
            'success' => false,
            'data' => false,
            'message' => 'No results found.'
        ], 404);
    }

    /**
     * @param Request $request
     *  The request from the URL
     *
     * @param $params
     *  Optional parameters, passed into the search query from within the backend application e.g
     *  VideoController::getById()
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function term(Request $request, $params = [])
    {
        if (!empty($params)) {
            $queryParams = $params;
        } else {
            $queryParams = $request->all();
        }
        if (!empty($queryParams)) {
            $results = $this->search->term($queryParams);

            if ($results) {
                return response()->json([
                    'success' => true,
                    'data' => $results,
                    'message' => false
                ], 200);
            }
        }
        return response()->json([
            'success' => false,
            'data' => false,
            'message' => 'No results found.'
        ], 404);
    }

    public function filter(Request $request, $term)
    {
        $filters = $request->all();
        if (!empty($filters)) {
            $results = $this->search->filter($term, $filters);
            if ($results) {
                return response()->json([
                    'success' => true,
                    'data' => $results,
                    'message' => false
                ], 200);
            }
            return response()->json([
                'success' => false,
                'data' => false,
                'message' => 'No results found.'
            ], 404);
        }
        return response()->json([
            'success' => false,
            'data' => false,
            'message' => 'No filters specified.'
        ], 404);
    }
}
