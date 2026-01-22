<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ImportStatus;
use App\Jobs\ProcessImportBatch;
use App\Jobs\ProcessImportSegment;
use App\Models\Import;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use SplFileObject;
use Throwable;

class StudentImportService
{
    protected int $batchThreshold = 5000;

    public function import(string $filePath, int $importId): void
    {
        $import = Import::findOrFail($importId);

        $file = new SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalRows = $file->key();
        $file = null;

        $import->update(['total_rows' => $totalRows]);

        if ($totalRows <= 1000) {
            $this->importSync($filePath, $import);

            return;
        }

        if ($totalRows <= 500000) {
            $this->dispatchBatch($import, $filePath, 10000);

            return;
        }

        $this->dispatchSegments($import, $filePath);
    }

    public function dispatchBatch(Import $import, string $filePath, int $chunkSize): void
    {
        $importId = $import->id;
        $relativeStoragePath = $import->file_path;

        $batch = Bus::batch([])
            ->then(function () use ($importId) {
                Import::where('id', $importId)->update([
                    'status' => ImportStatus::Completed,
                    'completed_at' => now(),
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($importId) {
                Import::where('id', $importId)->update([
                    'status' => ImportStatus::Failed,
                    'error_message' => 'Batch failed: '.str($e->getMessage())->limit(200),
                    'completed_at' => now(),
                ]);
            })
            ->finally(function () use ($relativeStoragePath) {
                if (Storage::exists($relativeStoragePath)) {
                    Storage::delete($relativeStoragePath);
                }
            })
            ->name("Importing Students: #{$importId}")
            ->dispatch();

        $import->update(['batch_id' => $batch->id, 'status' => ImportStatus::Processing]);

        LazyCollection::make(function () use ($filePath) {
            $handle = fopen($filePath, 'r');
            $headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                yield array_combine($headers, $data);
            }
            fclose($handle);
        })
            ->chunk($chunkSize)
            ->each(function ($chunk) use ($batch, $importId) {
                $batch->add(new ProcessImportBatch($importId, $chunk->toArray()));
            });
    }

    public function importSync(string $filePath, Import $import): void
    {
        $import->update(['status' => ImportStatus::Processing]);

        try {
            LazyCollection::make(function () use ($filePath) {
                $handle = fopen($filePath, 'r');
                $headers = fgetcsv($handle);
                while (($data = fgetcsv($handle)) !== false) {
                    yield array_combine($headers, $data);
                }
                fclose($handle);
            })
                ->chunk(500)
                ->each(function ($chunk) use ($import) {
                    $job = new ProcessImportBatch($import->id, $chunk->toArray());
                    app()->call([$job, 'handle']);
                });

            $import->update([
                'status' => ImportStatus::Completed,
                'completed_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            $import->update([
                'status' => ImportStatus::Failed,
                'error_message' => 'Sync Import Failed: '.str($e->getMessage())->limit(200),
                'completed_at' => now(),
            ]);

            Log::error('Critical failure in sync import', ['errorMessage' => $e]);
        } finally {
            if (Storage::exists($import->file_path)) {
                Storage::delete($import->file_path);
            }
        }
    }

    public function dispatchSegments(Import $import, string $filePath): void
    {
        $import->update(['status' => ImportStatus::Processing]);

        $segmentSize = 1000000;
        $totalRows = $import->total_rows;

        for ($offset = 0; $offset < $totalRows; $offset += $segmentSize) {
            ProcessImportSegment::dispatch(
                $import->id,
                $import->file_path,
                $offset,
                $segmentSize
            );
        }
    }
}
