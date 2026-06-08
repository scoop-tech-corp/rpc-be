<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\PushNotification\PushNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = PushNotification::where('usersId', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('isRead')) {
            $query->where('isRead', (bool) $request->isRead);
        }

        $limit = $request->get('limit', 20);
        $data  = $query->limit($limit)->get();

        $unreadCount = PushNotification::where('usersId', $request->user()->id)
            ->where('isRead', false)
            ->count();

        return response()->json([
            'data'        => $data,
            'unreadCount' => $unreadCount,
        ], 200);
    }

    public function markRead(Request $request, $id)
    {
        $notif = PushNotification::where('id', $id)
            ->where('usersId', $request->user()->id)
            ->first();

        if (!$notif) {
            return responseInvalid(['Notification not found.']);
        }

        $notif->update([
            'isRead' => true,
            'readAt' => Carbon::now(),
        ]);

        return responseUpdate();
    }

    public function markAllRead(Request $request)
    {
        PushNotification::where('usersId', $request->user()->id)
            ->where('isRead', false)
            ->update([
                'isRead' => true,
                'readAt' => Carbon::now(),
            ]);

        return responseUpdate();
    }
}
