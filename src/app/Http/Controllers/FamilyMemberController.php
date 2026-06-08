<?php

namespace App\Http\Controllers;

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
}