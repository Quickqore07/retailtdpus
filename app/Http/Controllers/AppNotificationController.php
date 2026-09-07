<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppNotificationController extends Controller
{
    /**
     * List in-app notifications for the authenticated user.
     *
     * Without `page`: returns up to `limit` unread rows (navbar dropdown).
     * With `page`: paginated. Pass `include_read=1` to include read notifications.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        if ($request->has('page')) {
            $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

            $query = AppNotification::query()
                ->where('user_id', $userId)
                ->orderByDesc('created_at');

            if (! $request->boolean('include_read')) {
                $query->whereNull('read_at');
            }

            $paginator = $query->paginate($perPage, [
                'id',
                'user_id',
                'content',
                'type',
                'read_at',
                'created_at',
            ]);

            return response()->json([
                'data' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => max($paginator->lastPage(), 1),
                    'from' => $paginator->firstItem() ?? 0,
                    'to' => $paginator->lastItem() ?? 0,
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'has_prev' => $paginator->currentPage() > 1,
                    'has_next' => $paginator->hasMorePages(),
                ],
            ]);
        }

        $limit = min((int) $request->query('limit', 50), 100);

        $items = AppNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'user_id', 'content', 'type', 'read_at', 'created_at']);

        return response()->json(['data' => $items]);
    }

    /**
     * Count unread notifications for the authenticated user, optionally filtered by type.
     */
    public function unreadCount(Request $request)
    {
        $userId = Auth::id();

        $query = AppNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at');

        $types = $request->query('types');
        if (is_string($types) && trim($types) !== '') {
            $typeList = array_values(array_filter(array_map('trim', explode(',', $types))));
            if ($typeList !== []) {
                $query->whereIn('type', $typeList);
            }
        }

        return response()->json(['count' => $query->count()]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(AppNotification $appNotification)
    {
        if ($appNotification->user_id !== Auth::id()) {
            abort(403);
        }

        if ($appNotification->read_at === null) {
            $appNotification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark multiple notifications as read for the authenticated user.
     */
    public function markManyRead(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct', 'exists:app_notifications,id'],
        ]);

        $now = now();

        $updated = AppNotification::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $validated['ids'])
            ->whereNull('read_at')
            ->update(['read_at' => $now]);

        return response()->json([
            'success' => true,
            'updated' => $updated,
        ]);
    }
}
