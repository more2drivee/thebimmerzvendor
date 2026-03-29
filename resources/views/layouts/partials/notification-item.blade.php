{{-- Notification Item Card Component --}}
{{-- This is a reference template for notification card structure --}}
{{-- The actual rendering is done via JavaScript with dynamic data --}}

<li class="list-group-item mb-3 p-3 glass-notification"
    data-status="{{ $notification['notification_status'] ?? 'pending' }}"
    data-text="{{ strtolower($notification['title'] ?? 'Notification') }}"
    data-day="{{ $notification['day'] ?? 'today' }}"
    {{ isset($notification['unread']) && $notification['unread'] ? 'style="opacity: 1;"' : '' }}>
    
    <div class="d-flex gap-3 align-items-start p-2" style="background: rgba(255,255,255,0.05); border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center" style="width:50px; height:50px; border-radius:50%;">
            <i class="{{ $iconClass ?? 'fas fa-bell text-muted' }} fa-2x" style="{{ $iconStyle ?? '' }}"></i>
        </div>
        
        <div class="flex-grow-1 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="badge rounded-pill border">{{ $badge ?? 'Info' }}</span>
                <small class="text-light">{{ $time ?? now()->diffForHumans() }}</small>
            </div>
            
            <h5 style="
                font-family: 'Cairo', sans-serif;
                font-weight: 600;
                color: #f8f9fa;
                margin-bottom: 4px;">
                {{ $title ?? 'New Notification' }}
            </h5>
            
            <p class="text-light mb-1" style="font-size:0.9rem;">
                {{ $content ?? '' }}
            </p>
            
            @if($buttons ?? false)
                {!! $buttons !!}
            @endif
        </div>
    </div>
</li>
