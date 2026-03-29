<div class="glass-notification"
     data-status="{{ $notification->notification_status ?? 'pending' }}"
     data-text="{{ is_object($notification->title) ? ($notification->title->notification->title ?? $notification->title->data->title ?? 'Notification') : ($notification->title ?? 'Notification') }}">
     
    <div class="d-flex gap-3 align-items-start p-2" style="background: rgba(255,255,255,0.05); border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center" style="width:50px; height:50px; border-radius:50%;">
            <i class="fas fa-bell text-muted fa-2x"></i>
        </div>
        <div class="flex-grow-1 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="badge rounded-pill border">Info</span>
                <small class="text-light">{{ $notification->created_at?->diffForHumans() ?? 'just now' }}</small>
            </div>
            <h5 class="text-light mb-1" style="font-family: 'Cairo', sans-serif; font-weight:600;">
                {{ is_object($notification->title) ? ($notification->title->notification->title ?? $notification->title->data->title ?? 'New Notification') : ($notification->title ?? 'New Notification') }}
            </h5>
            <p class="text-light mb-1" style="font-size:0.9rem;">
                {{ is_object($notification->title) ? ($notification->title->data->body ?? '') : ($notification->body ?? '') }}
                @if($notification->contact_name)<br><strong>Customer:</strong> {{ $notification->contact_name }}@endif
                @if($notification->device_name)<br><strong>Device:</strong> {{ $notification->device_name }}@endif
                @if($notification->location_name)<br><strong>Location:</strong> {{ $notification->location_name }}@endif
                @if($notification->note)<br><strong>Note:</strong> {{ $notification->note }}@endif
            </p>

            @if($notification->notification_status === 'pending' && isset($notification->data['booking_id']))
                <div class="d-flex gap-2 mb-2" style="max-height:30px;">
                    <button class="btn btn-success flex-fill action-btn" 
                            data-booking-id="{{ $notification->data['booking_id'] }}"
                            data-notification-id="{{ $notification->id }}"
                            data-action="approved">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="btn btn-danger flex-fill action-btn"
                            data-booking-id="{{ $notification->data['booking_id'] }}"
                            data-notification-id="{{ $notification->id }}"
                            data-action="rejected">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
            @else
                <span class="badge {{ $notification->notification_status === 'approved' ? 'bg-success' : 'btn-danger' }}">
                    {{ $notification->actionBy ?? $notification->notification_status }}
                </span>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const card = document.currentScript.previousElementSibling; // the card div

    card.querySelectorAll('.action-btn').forEach(btn => {
        btn.onclick = async e => {
            e.preventDefault();
            const bookingId = btn.dataset.bookingId;
            const action = btn.dataset.action;
            const notificationId = btn.dataset.notificationId;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('{{ route("notification.action") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ booking_id: bookingId, action: action, notification_id: notificationId })
                });

                const data = await res.json();
                if(res.ok && data.success){
                    card.remove();
                    fetchNotificationCount(); // from your main JS
                } else {
                    alert('Failed to ' + action + ' booking');
                }
            } catch(err) {
                console.error(err);
                alert(err.message);
            }
        };
    });
});
</script>
