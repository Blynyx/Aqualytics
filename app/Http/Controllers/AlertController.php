<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlertController extends Controller
{
    public function assign(Request $request, Alert $alert): RedirectResponse
    {
        abort_unless(
            $alert->pond->fish_farm_id === $request->user()->fish_farm_id,
            404,
        );

        abort_unless(
            in_array($request->user()->role, [
                User::ROLE_ADMIN,
                User::ROLE_SUPERVISOR,
            ], true),
            403,
        );

        $validated = $request->validate([
            'specialist_id' => [
                'required',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query
                        ->where('fish_farm_id', $request->user()->fish_farm_id)
                        ->where('role', User::ROLE_SPECIALIST),
                ),
            ],
        ]);

        $alert->update([
            'assigned_to_user_id' => $validated['specialist_id'],
            'reported_by_user_id' => $request->user()->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        return redirect("/ponds/{$alert->pond_id}");
    }

    public function resolve(Request $request, Alert $alert): RedirectResponse
    {
        abort_unless(
            $alert->pond->fish_farm_id === $request->user()->fish_farm_id,
            404,
        );

        abort_unless(
            in_array($request->user()->role, [
                User::ROLE_ADMIN,
                User::ROLE_SPECIALIST,
            ], true),
            403,
        );

        if ($request->user()->role === User::ROLE_SPECIALIST) {
            abort_unless(
                $alert->assigned_to_user_id === $request->user()->id,
                403,
            );
        }

        $validated = $request->validate([
            'resolution_notes' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by_user_id' => $request->user()->id,
            'resolution_notes' => $validated['resolution_notes'],
        ]);

        return redirect("/ponds/{$alert->pond_id}");
    }
}
