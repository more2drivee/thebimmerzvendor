<?php

namespace Modules\Connector\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;

class NotificationController extends ApiController
{
    public function index(Request $request)
    {
        /** @var \App\User&\Illuminate\Notifications\Notifiable $user */
        $user = Auth::user();

        $status = $request->query('status', 'unread'); // unread|read|all
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 20));

        $query = $user->notifications()->latest();
        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        $paginator = $query->paginate($perPage);

        $data = $paginator->getCollection()->map(function (DatabaseNotification $notification) {
            $payload = $notification->data ?? [];
            // Build human-readable message from common payload
            $message = '';
            if (!empty($payload)) {
                $action = $payload['action'] ?? null;
                $job_sheet_no = $payload['job_sheet_no'] ?? ($payload['job_sheet_no'] ?? null);
                // $product_id = $payload['product_id'] ?? null;
                // $qty = $payload['quantity'] ?? null;
                // $price = $payload['price'] ?? null;
                // $purchase_price = $payload['purchase_price'] ?? null;
                // $client_approval = isset($payload['client_approval']) ? (int)$payload['client_approval'] : null;

                $parts = [];
                if ($action) { $parts[] = $action; }
                if (!empty($job_sheet_no)) { $parts[] = '[JS ' . $job_sheet_no . ']'; }
                // if ($product_id) { $parts[] = 'Product #' . $product_id; }
                // if ($qty !== null) { $parts[] = 'Qty ' . $qty; }
                // if ($price !== null) { $parts[] = 'Price ' . $price; }
                // if ($purchase_price !== null) { $parts[] = 'Purchase ' . $purchase_price; }
                // if ($client_approval !== null) { $parts[] = 'Approval ' . ($client_approval ? 'yes' : 'no'); }
                $message = trim(implode(' • ', $parts));

                if ($message === '' && isset($payload['message'])) {
                    $message = (string) $payload['message'];
                }
            }

            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $payload,
                'message' => $message,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'updated_at' => $notification->updated_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function markAsRead(Request $request)
    {
        $validated = $request->validate([
            'notification_ids' => ['required', 'array', 'min:1'],
            'notification_ids.*' => ['uuid'],
        ]);

        /** @var \App\User&\Illuminate\Notifications\Notifiable $user */
        $user = Auth::user();

        $notifications = $user->unreadNotifications()
            ->whereIn('id', $validated['notification_ids'])
            ->get();

        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'updated' => $notifications->pluck('id'),
        ]);
    }

    public function markAllAsRead()
    {
        /** @var \App\User&\Illuminate\Notifications\Notifiable $user */
        $user = Auth::user();
        $unread = $user->unreadNotifications;

        if ($unread->isNotEmpty()) {
            $unread->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }
}
