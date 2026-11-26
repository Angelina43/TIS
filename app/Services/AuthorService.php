<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;

class AuthorService
{
    public function access(string $fileId, string $email, User $author): array
    {
        $file = $this->findOwnedFile($fileId, $author);

        $user = User::where('email', $email)->first();

        if (!$user) {
            abort(404);
        }

        if ($user->id !== $author->id) {
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

        return $this->getAccessList($file);
    }

    public function remove(string $fileId, string $email, User $author): array
    {
        $file = $this->findOwnedFile($fileId, $author);

        if ($email === $author->email) {
            abort(403);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            abort(404);
        }

        $coAuthors = collect($file->co_author ?? []);

        if (!$coAuthors->contains('email', $email)) {
            abort(404);
        }

        $file->co_author = $coAuthors
            ->reject(fn($coAuthor) => ($coAuthor['email'] ?? '') === $email)
            ->values()
            ->toArray();

        $file->save();

        return $this->getAccessList($file);
    }

    public function showCoAuthor(User $user): array
    {
        $files = File::where('author', '!=', $user->id)
            ->whereJsonContains('co_author', [['email' => $user->email]])
            ->get();

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

    private function findOwnedFile(string $fileId, User $author): File
    {
        $file = File::where('file_id', $fileId)->firstOrFail();

        if ($file->author !== $author->id) {
            abort(403);
        }

        return $file;
    }
}
