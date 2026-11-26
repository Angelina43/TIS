<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUpdateRequest;
use App\Http\Requests\FileUploadRequest;
use App\Services\FileService;
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
        $this->fileService->updateFile(
            $id,
            $request->validated('name'),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Renamed',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $this->fileService->deleteFile($id, $request->user());

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'File deleted',
        ]);
    }

    public function download($id, Request $request)
    {
        $file = $this->fileService->downloadFile($id, $request->user());

        return Storage::disk('local')->download($file->url, $file->name);
    }

    public function showFile(Request $request)
    {
        $result = $this->fileService->showFile($request->user());

        return response()->json($result);
    }
}
