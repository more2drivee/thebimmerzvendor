<div class="progress-container" wire:poll.1s="refreshStatus">
    @foreach ($statuses as $status)
        <div class="{{ $currentStatusId == $status->id ? 'step completed' : 'step' }}" id="pro{{ $status->id }}">
            <span class="icon">
                <i class="{{ $currentStatusId == $status->id ? 'fa-solid fa-check' : 'fas fa-hourglass-half' }}"
                   style="color: {{ $status->color }};"></i>
            </span>
            <p>{{ $status->name }}</p>
        </div>
    @endforeach
</div>
