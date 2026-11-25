<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function upload(Request $request)
    {
        $files = $request->file('files');

        if (!$files) {
            return response()->json(['message' => 'Files are required'], 400);
        }

        $files = is_array($files) ? $files : [$files];
        $response = [];

        foreach ($files as $uploadedFile) {
            $validator = Validator::make(['file' => $uploadedFile], [
                'files' => 'mimes:doc,pdf,docx,zip,jpeg,jpg,png|max:2048',
            ], [
                'files.mimes' => 'file must be a doc, pdf, docx, zip, jpeg, jpg, png',
                'files.max' => 'file is too large, maximum size is 2MB',
            ]);

            if ($validator->fails()) {
                $response[] = [
                    'success' => false,
                    'message' => $validator->errors()->all(),
                    'name' => $uploadedFile->getClientOriginalName(),
                ];
                continue;
            }

            $fileName = $this->generateUniqueFileName($uploadedFile, 'files');
            $path = $uploadedFile->storeAs('files', $fileName, 'local');
            $fileId = Str::lower(Str::random(10));

            $fileRecord = File::create([
                'name' => $fileName,
                'url' => $path,
                'file_id' => $fileId,
                'author' => Auth::id(),
            ]);

            $response[] = [
                'success' => true,
                'code' => 200,
                'message' => 'Success',
                'name' => $fileRecord->name,
                'url' => url("/api-file/files/{$fileRecord->file_id}"),
                'file_id' => $fileRecord->file_id,
            ];
        }

        return response()->json($response);
    }

    private function generateUniqueFileName($file, string $folder): string
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext = $file->getClientOriginalExtension();
        $fileName = "{$name}.{$ext}";
        $counter = 1;

        while (Storage::disk('local')->exists("{$folder}/{$fileName}")) {
            $fileName = "{$name} ({$counter}).{$ext}";
            $counter++;
        }

        return $fileName;
    }

    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|unique:files,name,' . $id,
        ],
            [
                'name.required' => 'file is required',
                'name.unique' => 'name must be unique',
            ]
        );

        if ($validate->fails()) {
            return response()->json($validate->errors(), 400);
        }

        $file = File::all()->where('id', $id)->first();

        if ($file->author == Auth::user()->getAuthIdentifier()) {
            $file->name = $request->name;
            $file->save();

            return response()->json([
                'success' => true,
                'code' => 200,
                'message' => 'Renamed',
            ]);
        }

        return response()->json([
            'success' => false,
            'code' => 401,
            'message' => 'Permission denied',
        ]);
    }

    public function destroy($id)
    {
        $file = File::findOrFail($id);

        if ($file->author == Auth::user()->getAuthIdentifier()) {
            $file->delete();
            return response()->json([
                'success' => true,
                'code' => 200,
                'message' => 'File deleted',
            ]);
        }

        return response()->json([
            'success' => false,
            'code' => 401,
            'message' => 'Permission denied',
        ]);
    }

    public function download($id)
    {
        $file = File::find($id);

        if (!$file->author == Auth::user()->getAuthIdentifier()) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'File not found',
            ]);
        }

        if (!Storage::exists($file->url)) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'Physical file not found on disk',
            ], 404);
        }

        return Storage::download($file->url, $file->name);
    }

    public function show_file()
    {
        $author = Auth::user();
        $files = File::where('author', $author->id)->get();

        $result = $files->map(function ($file) use ($author) {
            $accesses = [
                [
                    'fullname' => "{$author->first_name} {$author->last_name}",
                    'email' => $author->email,
                    'type' => 'author',
                ],
            ];

            foreach ($file->co_author ?? [] as $coAuthor) {
                $accesses[] = [
                    'fullname' => "{$coAuthor['first_name']} {$coAuthor['last_name']}",
                    'email' => $coAuthor['email'],
                    'type' => 'co-author',
                ];
            }

            return [
                'file_id' => $file->file_id,
                'name' => $file->name,
                'code' => 200,
                'url' => url("/api-file/files/{$file->file_id}"),
                'accesses' => $accesses,
            ];
        });

        return response()->json($result);
    }
}
