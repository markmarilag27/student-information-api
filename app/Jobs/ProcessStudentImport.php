<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Services\StudentImportService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessStudentImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $filePath,
        public int $importId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(StudentImportService $service): void
    {
        /** @var ?Import $import */
        $import = DB::transaction(function () {
            return Import::where('id', $this->importId)
                ->where('status', ImportStatus::Pending)
                ->lockForUpdate()
                ->first();
        });

        if (! $import) {
            return;
        }

        try {
            if (! Storage::exists($this->filePath)) {
                throw new Exception("File not found at: {$this->filePath}");
            }

            $absolutePath = Storage::path($this->filePath);

            $service->import($absolutePath, $import->id);
        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $import = Import::find($this->importId);

        if ($import && $import->batch_id) {
            $batch = Bus::findBatch($import->batch_id);
            $batch?->cancel();
        }

        $errorContext = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace_summary' => str($exception->getTraceAsString())->limit(1000),
        ];

        Import::where('id', $this->importId)->update([
            'status' => ImportStatus::Failed,
            'error_message' => str($exception->getMessage())->limit(500),
            'error_log' => json_encode($errorContext),
            'completed_at' => now(),
        ]);

        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }
    }
}
