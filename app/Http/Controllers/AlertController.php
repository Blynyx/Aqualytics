<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function resolve(Request $request, Alert $alert): RedirectResponse
    {
        abort_unless($alert->pond->user_id === $request->user()->id, 404);

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return redirect("/ponds/{$alert->pond_id}");
    }
}
