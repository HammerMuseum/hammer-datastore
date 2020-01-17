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
     * @param $term
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request, $term)
    {
        $params['from'] = $request->start ? $request->start : 0;
        if (!is_null($term)) {
            $results = $this->search->match($term, $params);
            $response = [
                'success' => true,
                'data' => $results['data'],
                'links' => $results['_links'],
                'message' => false
            ];
            if ($results) {
                return response()->json($response, 200);
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
     * @param $field
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function term(Request $request, $field, $id)
    {
        if (!is_null($field)) {
            $results = $this->search->term($field, $id);

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
