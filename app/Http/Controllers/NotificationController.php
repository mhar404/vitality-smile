<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->notifications();

        // Status filter: 'all', 'unread', 'read'
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->whereNull('read_at');
            } elseif ($request->status === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        // Category filter: 'appointment', 'treatment', 'billing', 'payment'
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('data->category', $request->category);
        }

        $notifications = $query->paginate(12)->withQueryString();

        $metrics = [
            'total' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
            'read' => $user->readNotifications()->count(),
            'appointments' => $user->notifications()->where('data->category', 'appointment')->count(),
            'treatments' => $user->notifications()->where('data->category', 'treatment')->count(),
            'billing' => $user->notifications()->whereIn('data->category', ['billing', 'payment'])->count(),
            'medical' => $user->notifications()->whereIn('data->category', ['medical', 'clearance'])->count(),
        ];

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filters' => $request->only(['status', 'category']),
            'metrics' => $metrics,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();

        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();

        $notification->delete();

        return back()->with('success', 'Notification removed.');
    }

    /**
     * Delete all read notifications.
     */
    public function clearRead(Request $request)
    {
        $user = Auth::user();
        $user->readNotifications()->delete();

        return back()->with('success', 'All read notifications cleared.');
    }
}

