<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $batch_id
 * @property string $filename
 * @property string $file_path
 * @property string $type
 * @property ImportStatus $status
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $failed_rows
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $error_message
 * @property array<array-key, mixed>|null $error_log
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\ImportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereErrorLog($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereFailedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereProcessedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereTotalRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Import whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Import extends Model
{
    /** @use HasFactory<\Database\Factories\ImportFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'batch_id',
        'filename',
        'file_path',
        'type',
        'status',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'started_at',
        'completed_at',
        'error_message',
        'error_log',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'error_log' => 'array',
        ];
    }
}
