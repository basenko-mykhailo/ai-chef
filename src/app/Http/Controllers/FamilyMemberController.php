<?php

namespace App\Http\Controllers;

use App\Http\Requests\FamilyMemberRequest;
use App\Models\FamilyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FamilyMemberController extends Controller
{
    public function index(Request $request): View
    {
        $members = $request->user()->familyMembers()->orderBy('id')->get();

        return view('family.index', [
            'members' => $members,
        ]);
    }

    public function create(): View
    {
        return view('family.create', [
            'member' => new FamilyMember(),
        ]);
    }

    public function store(FamilyMemberRequest $request): RedirectResponse
    {
        // Create through the relationship so user_id is bound to the current
        // user and can never be spoofed via the request payload.
        $request->user()->familyMembers()->create($request->validated());

        return redirect()
            ->route('family.index')
            ->with('status', 'family-member-created');
    }

    public function edit(Request $request, FamilyMember $familyMember): View
    {
        abort_unless($familyMember->user_id === $request->user()->id, 403);

        return view('family.edit', [
            'member' => $familyMember,
        ]);
    }

    public function update(FamilyMemberRequest $request, FamilyMember $familyMember): RedirectResponse
    {
        // Ownership is enforced by FamilyMemberRequest::authorize().
        $familyMember->update($request->validated());

        return redirect()
            ->route('family.index')
            ->with('status', 'family-member-updated');
    }

    public function destroy(Request $request, FamilyMember $familyMember): RedirectResponse
    {
        abort_unless($familyMember->user_id === $request->user()->id, 403);

        $familyMember->delete();

        return redirect()
            ->route('family.index')
            ->with('status', 'family-member-deleted');
    }
}
