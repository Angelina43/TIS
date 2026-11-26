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
        $file = $this->authorService->access($id, $request->validated('email'), $request->user());

        if (!$file) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'File not found',
            ], 404);
        }

        return $this->accessListResponse($file);
    }
    public function remove(AccessRequest $request, $id)
    {
        $file = $this->authorService->remove($id, $request->validated('email'), $request->user());

        if (!$file) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'File not found',
            ], 404);
        }

        return $this->accessListResponse($file);
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
