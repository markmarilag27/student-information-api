<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;

class ProcessImportSegment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1200;

    public bool $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importId,
        public string $filePath,
        public int $offset,
        public int $limit
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $batch = Bus::batch([])
            ->name("Import #{$this->importId} - Segment {$this->offset}")
            ->allowFailures()
            ->dispatch();

        $absolutePath = Storage::path($this->filePath);

        LazyCollection::make(function () use ($absolutePath) {
            $handle = fopen($absolutePath, 'r');
            $headers = fgetcsv($handle);

            $currentIndex = 0;
            while ($currentIndex < $this->offset && ! feof($handle)) {
                fgets($handle);
                $currentIndex++;
            }

            $rowsRead = 0;
            while (($data = fgetcsv($handle)) !== false && $rowsRead < $this->limit) {
                if ($data) {
                    yield array_combine($headers, $data);
                }
                $rowsRead++;
            }

            fclose($handle);
        })
            ->chunk(10000)
            ->each(function ($chunk) use ($batch) {
                $batch->add(new ProcessImportBatch($this->importId, $chunk->toArray()));
            });
    }
}
