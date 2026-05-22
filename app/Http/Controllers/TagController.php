<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tags = $request->user()
            ->tags()
            ->orderBy('name')
            ->get();

        return response()->json([
            'tags' => $tags,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'color' => ['required', 'string', 'size:7'],
        ]);

        $tag = $request->user()->tags()->create([
            'name' => trim($data['name']),
            'color' => $data['color'],
        ]);

        return response()->json([
            'tag' => $tag,
        ], 201);
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $this->authorizeOwnership($request, $tag);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:20'],
            'color' => ['sometimes', 'string', 'size:7'],
        ]);

        $tag->update($data);

        return response()->json([
            'tag' => $tag->fresh(),
        ]);
    }

    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        $this->authorizeOwnership($request, $tag);

        $tag->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }

    private function authorizeOwnership(Request $request, Tag $tag): void
    {
        abort_unless(
            $tag->user_id === $request->user()->id,
            403
        );
    }
}