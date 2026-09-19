<?php

namespace App\Http\Controllers;

use App\Models\InternalNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InternalNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $notifications = InternalNotification::query()
            ->where('fish_farm_id', $user->fish_farm_id)
            ->where('user_id', $user->id)
            ->latestFirst()
            ->paginate(15);

        $sourceLinks = $notifications->getCollection()->mapWithKeys(
            fn (InternalNotification $notification): array => [
                $notification->id => $notification->sourceUrlFor($user),
            ],
        );

        return view('notifications.index', compact('notifications', 'sourceLinks'));
    }

    public function markAsRead(Request $request, InternalNotification $notification): RedirectResponse
    {
        $this->ensureRecipient($request, $notification);

        if ($notification->read_at === null) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        return redirect()->route('notifications.index');
    }

    private function ensureRecipient(Request $request, InternalNotification $notification): void
    {
        abort_unless(
            $notification->fish_farm_id === $request->user()->fish_farm_id
            && $notification->user_id === $request->user()->id,
            404,
        );
    }
}
