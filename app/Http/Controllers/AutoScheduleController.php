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
        $jobId = (string) \Illuminate\Support\Str::uuid();

        // Init Cache Status (Prevents 404 on immediate poll)
        \Illuminate\Support\Facades\Cache::put('scheduler_job_' . $jobId, [
            'status' => 'processing',
            'progress' => 0,
            'message' => 'Initializing...'
        ], 3600);

        $examDays = $request->input('examDays', 12);
        $startDate = $request->input('startDate'); // optional

        // Dispatch Job
        \App\Jobs\GenerateScheduleJob::dispatch($jobId, $examDays, $startDate);

        return response()->json([
            'success' => true,
            'jobId' => $jobId,
            'message' => 'Schedule generation started in background.'
        ]);
    }

    public function status($jobId)
    {
        $status = \Illuminate\Support\Facades\Cache::get('scheduler_job_' . $jobId);

        if (!$status) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($status);
    }
}
