<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ImportResource;
use App\Jobs\ProcessStudentImport;
use App\Models\Import;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentImportController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'upload' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        $path = $request->file('upload')->store('imports');

        /** @var User $user */
        $user = Auth::user();

        /** @var Import $import */
        $import = Import::create([
            'user_id' => $user->id,
            'filename' => $request->file('upload')->getClientOriginalName(),
            'file_path' => $path,
            'status' => ImportStatus::Pending,
            'type' => ImportType::Student,
            'started_at' => now(),
        ]);

        ProcessStudentImport::dispatch($path, $import->id)->afterCommit();

        return response()->json([
            'message' => __('general.upload_in_progress'),
            'status' => $import->status->value,
        ]);
    }

    public function checkImportStatus(Import $import): JsonResponse
    {
        return response()->json([
            'data' => new ImportResource($import),
        ]);
    }
}
