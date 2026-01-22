<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ImportStatus;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Throwable;

class ProcessImportBatch implements ShouldQueue
{
    use Batchable, Dispatchable, Queueable;

    // Set tries to 1 because we handle retries manually for deadlocks
    public int $tries = 1;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $importId, public array $rows) {}

    /**
     * Execute the job.
     */
    public function handle(LoggerInterface $logger): void
    {
        if ($this->batch()?->canceled()) {
            return;
        }

        try {
            DB::transaction(function () {
                $schools = [];
                $students = [];
                $now = now();

                foreach ($this->rows as $row) {
                    $schools[$row['school_code']] = [
                        'code' => $row['school_code'],
                        'name' => $row['school_name'] ?? 'Unknown',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $students[] = [
                        'student_code' => $row['student_id'],
                        'school_code' => $row['school_code'],
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name'],
                        'date_of_birth' => $row['date_of_birth'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                // Batch inserts
                foreach (array_chunk(array_values($schools), 500) as $schoolChunk) {
                    DB::table('schools')->insertOrIgnore($schoolChunk);
                }

                foreach (array_chunk($students, 500) as $studentChunk) {
                    DB::table('students')->insertOrIgnore($studentChunk);
                }

                DB::table('imports')->where('id', $this->importId)
                    ->increment('processed_rows', count($this->rows));

                $this->checkCompletion();
            }, 3);

        } catch (Throwable $e) {
            $logger->error('Student migration chunk failed. Rolling back.', [
                'errorMessage' => $e,
                'importId' => $this->importId,
            ]);

            $this->batch()?->cancel();

            throw $e;
        }
    }

    protected function checkCompletion(): void
    {
        $import = DB::table('imports')->where('id', $this->importId)->lockForUpdate()->first();

        if ($import && $import->processed_rows >= $import->total_rows && $import->status !== ImportStatus::Completed) {
            DB::table('imports')->where('id', $this->importId)->update([
                'status' => ImportStatus::Completed,
                'completed_at' => now(),
            ]);
        }
    }
}
