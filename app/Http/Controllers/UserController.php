<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = $request->user()
            ->fishFarm
            ->users()
            ->orderBy('name')
            ->get();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $fishFarm = $request->user()->fishFarm;
        $limits = app(SubscriptionLimitService::class);

        if (! $limits->canCreateUser($fishFarm)) {
            throw ValidationException::withMessages([
                'email' => 'Has alcanzado el límite de usuarios de tu plan.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => [
                'required',
                Rule::in([
                    User::ROLE_SUPERVISOR,
                    User::ROLE_SPECIALIST,
                ]),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()
            ->fishFarm
            ->users()
            ->create($validated);

        return redirect('/users')
            ->with('success', 'Usuario creado correctamente.');
    }
}
