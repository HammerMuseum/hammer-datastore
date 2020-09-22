<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TranscriptManager;

/**
 * Class TranscriptController
 * @package App\Http\Controllers
 */
class TranscriptController extends Controller
{
    /** @var TranscriptManager */
    protected $transcriptManager;

    /**
     * Constructor.
     */
    public function __construct(TranscriptManager $transcriptManager)
    {
        $this->transcriptManager = $transcriptManager;
    }

    /**
     * @param $id
     * @return array|bool
     */
    public function show(Request $request, $id)
    {
        $format = $request->query('format');

        if (in_array($format, ['json', 'vtt'])) {
            return $this->transcriptManager->get($format, $id);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Requested format is not a valid option.'
            ], 400);
        }
    }
}
