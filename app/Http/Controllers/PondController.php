<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PondController extends Controller
{
    public function index(Request $request): View
    {
        $ponds = $request->user()->ponds()->get();

        return view('ponds.index', compact('ponds'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('ponds', 'code')],
            'species' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $request->user()->ponds()->create([
            ...$validated,
            'status' => 'active',
        ]);

        return redirect('/');
    }
}
