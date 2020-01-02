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
        if (!is_null($term)) {
            $results = $this->search->search($term);

            if ($results) {
                return response()->json([
                    'success' => true,
                    'result' => $results,
                    'message' => false
                ], 200);
            }
        }
        return response()->json([
            'success' => false,
            'result' => false,
            'message' => 'No results found.'
        ], 404);
    }
}
