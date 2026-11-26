<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUpdateRequest;
use App\Http\Requests\FileUploadRequest;
use App\Services\FileService;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct(private FileService $fileService)
    {}
    public function upload(FileUploadRequest $request)
    {
        $files = $request->file('files') ?? $request->file('file');
        $files = is_array($files) ? $files : [$files];

        $response = $this->fileService->uploadFiles($files, $request->user());

        return response()->json($response);
    }
    public function update(FileUpdateRequest $request, $id)
    {
        $updated = $this->fileService->updateFile(
            $id,
            $request->validated('name'),
            $request->user()
        );

        if (!$updated) {
            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => 'Permission denied',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Renamed',
        ]);
    }
    public function destroy(Request $request, $id)
    {
        $deleted = $this->fileService->deleteFile($id, $request->user());

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => 'Permission denied',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'File deleted',
        ]);
    }
    public function download($id, Request $request)
    {
        $file = $this->fileService->DownloadFile($id, $request->user());

        if (!$file) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'File not found',
            ], 404);
        }

        return Storage::disk('local')->download($file->url, $file->name);
    }
    public function showFile(Request $request)
    {
        $result = $this->fileService->showFile($request->user());

        return response()->json($result);
    }
}
