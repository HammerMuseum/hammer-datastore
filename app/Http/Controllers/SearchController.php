<?php

namespace App\Http\Controllers;

use App\Search;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected $search;
    public function __construct(
        Search $search
    ) {
        $this->search = $search;
    }

    public function search(Request $request, $term)
    {
        if (!is_null($term)) {
            $results = $this->search->search($term);

            if ($results) {
                return response()->json($results, 200);
            }
        }
    }
}
