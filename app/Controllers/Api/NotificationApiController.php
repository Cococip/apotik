<?php

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Services\NotificationService;

class NotificationApiController extends Controller
{
    public function index(Request $request): void
    {
        $userId = Auth::id();
        NotificationService::sync($userId);

        $items = array_map(function ($n) {
            return [
                'id' => (int) $n['id'],
                'type' => $n['type'],
                'title' => $n['title'],
                'message' => $n['message'],
                'is_read' => (bool) $n['is_read'],
                'created_at' => format_datetime($n['created_at']),
            ];
        }, NotificationService::recentFor($userId));

        $this->json([
            'success' => true,
            'message' => 'OK',
            'errors' => [],
            'data' => [
                'items' => $items,
                'unread_count' => NotificationService::unreadCount($userId),
            ],
        ]);
    }

    public function markRead(Request $request, string $id): void
    {
        $ok = NotificationService::markRead(Auth::id(), (int) $id);
        $this->json(['success' => $ok, 'message' => $ok ? 'OK' : 'Gagal', 'errors' => [], 'data' => null]);
    }
}
