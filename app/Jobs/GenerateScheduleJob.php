<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use App\Services\ExamSchedulerService;

class GenerateScheduleJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 900; // 15 minutes timeout

    /**
     * Create a new job instance.
     */
    public function __construct(public string $jobId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ExamSchedulerService $scheduler): void
    {
        // 1. Update Status: Processing
        Cache::put('scheduler_job_' . $this->jobId, [
            'status' => 'processing',
            'progress' => 10,
            'message' => 'Starting generation...'
        ], 3600); // 1 hour TTL

        try {
            // Set limits for CLI worker (if not already set by php.ini)
            ini_set('memory_limit', '2048M');
            set_time_limit(0);

            // 2. Run Generation
            $result = $scheduler->generate();

            // 3. Update Status: Completed
            Cache::put('scheduler_job_' . $this->jobId, [
                'status' => 'completed',
                'result' => $result,
                'progress' => 100
            ], 3600);

        } catch (\Throwable $e) {
            // 4. Update Status: Failed
            Cache::put('scheduler_job_' . $this->jobId, [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'progress' => 0
            ], 3600);

            throw $e;
        }
    }
}
