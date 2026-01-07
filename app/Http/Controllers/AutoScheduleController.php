<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ExamSchedulerService;
use Illuminate\Support\Facades\Log;

class AutoScheduleController extends Controller
{
    protected $scheduler;

    public function __construct(ExamSchedulerService $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    public function generate(Request $request)
    {
        // Increase time limit for this request
        set_time_limit(120);

        try {
            $result = $this->scheduler->generate();
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Scheduling failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
