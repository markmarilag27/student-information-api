<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Import
 */
class ImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Import $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->id,
            'status' => $resource->status->value,
            'progress' => $this->getPercentage(),
            'processed_rows' => $resource->processed_rows,
            'total_rows' => $resource->total_rows,
            'failed_rows' => $resource->failed_rows,
            'error' => $resource->error_message,
            'is_completed' => $resource->status === ImportStatus::Completed,
        ];
    }

    private function getPercentage()
    {
        return $this->resource->total_rows > 0
            ? round(($this->resource->processed_rows / $this->resource->total_rows) * 100, 2)
            : 0;
    }
}
