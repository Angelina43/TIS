<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;

class AuthorService
{
    public function access(string $fileId, string $email, User $author)
    {
        $file = File::where('file_id', $fileId)->first();

        if (!$file || $file->author !== $author->id) {
            return null;
        }

        $user = User::where('email', $email)->first();

        if ($user && $user->id !== $author->id) {
            $currentCoAuthors = $file->co_author ?? [];

            $userData = [
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ];

            if (!in_array($userData, $currentCoAuthors)) {
                $currentCoAuthors[] = $userData;
                $file->co_author = $currentCoAuthors;
                $file->save();
            }
        }

        return $file;
    }

    public function remove(string $fileId, string $email, User $author)
    {
        $file = File::where('file_id', $fileId)->first();

        if (!$file || $file->author !== $author->id) {
            return null;
        }

        $file->co_author = collect($file->co_author ?? [])
            ->reject(fn($coAuthor) => ($coAuthor['email'] ?? '') === $email)
            ->values()
            ->toArray();

        $file->save();

        return $file;
    }

    public function showCoAuthor(User $user): array
    {
        $files = File::whereJsonContains('co_author', [['email' => $user->email],])->get();

        return $files->map(function ($file) {
            return [
                'file_id' => $file->file_id,
                'code' => 200,
                'name' => $file->name,
                'url' => url("/api-file/files/{$file->file_id}"),
            ];
        })->all();
    }

    public function getAccessList(File $file): array
    {
        $author = User::find($file->author);

        $result = [
            [
                'fullname' => "{$author->first_name} {$author->last_name}",
                'email' => $author->email,
                'type' => 'author',
                'code' => 200,
            ],
        ];

        foreach ($file->co_author ?? [] as $coAuthor) {
            $result[] = [
                'fullname' => "{$coAuthor['first_name']} {$coAuthor['last_name']}",
                'email' => $coAuthor['email'],
                'type' => 'co-author',
                'code' => 200,
            ];
        }

        return $result;
    }
}
