<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthorController extends Controller
{
    public function access(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required',
        ],
            [
                'email.required' => 'email is required',
            ]
        );

        if ($validate->fails()) {
            return response()->json($validate->errors(), 400);
        }

        $file = File::find($id);
        if (!$file->author == Auth::user()->getAuthIdentifier()) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'File not found',
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $currentCoAuthors = $file->co_author ?? [];

            $userData = [
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ];

            if (!in_array($userData, $currentCoAuthors)) {
                $currentCoAuthors[] = $userData;
            }

            $file->co_author = $currentCoAuthors;
            $file->save();
        }

        return $this->accessListResponse($file);
    }

    public function remove(Request $request, $id)
    {
        $data = $request->validate(['email' => 'required|email']);
        $file = File::where('id', $id)->where('author', Auth::id())->firstOrFail();

        $file->co_author = collect($file->co_author ?? [])
            ->reject(fn($author) => ($author['email'] ?? '') === $data['email'])
            ->values()
            ->toArray();

        $file->save();

        return $this->accessListResponse($file);
    }

    private function accessListResponse($file)
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

        return response()->json($result);
    }

    public function show_co_author()
    {
        $files = File::whereJsonContains('co_author', [
            ['email' => Auth::user()->email],
        ])->get();

        $result = $files->map(function ($file) {
            return [
                'file_id' => $file->file_id,
                'code' => 200,
                'name' => $file->name,
                'url' => url("/api-file/files/{$file->file_id}"),
            ];
        });

        return response()->json($result);
    }
}
