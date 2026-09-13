<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
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

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return redirect("/ponds/{$alert->pond_id}");
    }
}
