<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccessRequest;
use App\Models\File;
use App\Services\AuthorService;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function __construct(private AuthorService $authorService)
    {}
    public function access(AccessRequest $request, $id)
    {
        $result = $this->authorService->access(
            $id,
            $request->validated('email'),
            $request->user()
        );

        return response()->json($result);
    }

    public function remove(AccessRequest $request, $id)
    {
        $result = $this->authorService->remove(
            $id,
            $request->validated('email'),
            $request->user()
        );

        return response()->json($result);
    }
    public function showCoAuthor(Request $request)
    {
        $result = $this->authorService->showCoAuthor($request->user());

        return response()->json($result);
    }

    private function accessListResponse(File $file)
    {
        return response()->json(
            $this->authorService->getAccessList($file)
        );
    }
}
