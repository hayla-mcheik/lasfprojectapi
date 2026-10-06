<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BoardMemberController extends Controller
{
    /**
     * Get all board members.
     */
    public function index()
    {
        $members = BoardMember::orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json($members);
    }

    /**
     * Store a new board member.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'short_bio' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request
                ->file('image')
                ->store('board-members', 'public');
        }

        $validated['sort_order'] =
            $validated['sort_order'] ?? 0;

        $validated['is_active'] =
            $request->has('is_active')
                ? $request->boolean('is_active')
                : true;

        $member = BoardMember::create($validated);

        return response()->json([
            'message' => 'Board member created successfully.',
            'member' => $member,
        ], 201);
    }

    /**
     * Get one board member.
     */
    public function show(BoardMember $boardMember)
    {
        return response()->json($boardMember);
    }

    /**
     * Update a board member.
     */
    public function update(
        Request $request,
        BoardMember $boardMember
    ) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'short_bio' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {

            if ($boardMember->image) {
                Storage::disk('public')
                    ->delete($boardMember->image);
            }

            $validated['image'] = $request
                ->file('image')
                ->store('board-members', 'public');
        }

        $validated['sort_order'] =
            $validated['sort_order'] ?? 0;

        $validated['is_active'] =
            $request->has('is_active')
                ? $request->boolean('is_active')
                : $boardMember->is_active;

        $boardMember->update($validated);

        return response()->json([
            'message' => 'Board member updated successfully.',
            'member' => $boardMember->fresh(),
        ]);
    }

    /**
     * Delete a board member.
     */
    public function destroy(BoardMember $boardMember)
    {
        if ($boardMember->image) {
            Storage::disk('public')
                ->delete($boardMember->image);
        }

        $boardMember->delete();

        return response()->json([
            'message' => 'Board member deleted successfully.',
        ]);
    }
}