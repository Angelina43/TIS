<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
class FileService
{
    public function uploadFiles(array $files, User $author)
    {
        $response = [];

        foreach ($files as $uploadedFile) {
            $validator = Validator::make(['file' => $uploadedFile], [
                'file' => 'mimes:doc,pdf,docx,zip,jpeg,jpg,png|max:2048',
            ], [
                'file.mimes' => 'file must be a doc, pdf, docx, zip, jpeg, jpg, png',
                'file.max' => 'file is too large, maximum size is 2MB',
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
                'author' => $author->id,
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

        return $response;
    }

    public function updateFile(string $id, string $newName, User $author)
    {
        $file = File::find($id);

        if (!$file || $file->author !== $author->id) {
            return false;
        }

        $file->name = $newName;
        $file->save();

        return true;
    }

    public function deleteFile(string $id, User $author)
    {
        $file = File::find($id);

        if (!$file || $file->author !== $author->id) {
            return false;
        }

        if ($file->url && Storage::disk('local')->exists($file->url)) {
            Storage::disk('local')->delete($file->url);
        }

        $file->delete();

        return true;
    }

    public function DownloadFile(string $fileId, User $user)
    {
        $file = File::where('file_id', $fileId)->first();

        if (!$file) {
            return null;
        }

        $isAuthor = $file->author === $user->id;
        $isCoAuthor = collect($file->co_author ?? [])->contains('email', $user->email);

        if (!$isAuthor && !$isCoAuthor) {
            return null;
        }

        if (!$file->url || !Storage::disk('local')->exists($file->url)) {
            return null;
        }

        return $file;
    }

    public function showFile(User $author): array
    {
        $files = File::where('author', $author->id)->get();

        return $files->map(function ($file) use ($author) {
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
        })->all();
    }

    private function generateUniqueFileName(UploadedFile $file, string $folder): string
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
}
