<?php

namespace App\Jobs;

use App\Services\RecurringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckRecurringItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(RecurringService $recurringService): void
    {
        Log::info('CheckRecurringItems job started');

        try {
            $result = $recurringService->checkRecurringItems();

            Log::info('CheckRecurringItems job completed', [
                'created_count' => $result['created_count'],
                'items' => $result['item_names'],
            ]);

            if ($result['created_count'] > 0) {
                Log::info("Created {$result['created_count']} recurring items: " . implode(', ', $result['item_names']));
            }
        } catch (\Exception $e) {
            Log::error('CheckRecurringItems job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CheckRecurringItems job failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }
}
