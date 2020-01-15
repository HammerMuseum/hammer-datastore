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
     * @param $sortField
     *  The field to sort on
     *
     * @param $direction
     *  The direction to order the results
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request, $term, $sortField = null, $direction = null)
    {
        if (!is_null($term)) {
            $params = $request->all($this->availableFacets);
            $results = $this->search->match($term, $sortField, $direction, $params);

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
     * @param $field
     *  The field to search
     *
     * @param $value
     *  The value to match
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function term(Request $request, $field, $value)
    {
        if (!is_null($field)) {
            $results = $this->search->term($field, $value);

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
}
