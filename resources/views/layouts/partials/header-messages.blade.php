@php
    //$all_notifications = auth()->user()->notifications;
    //$unread_notifications = $all_notifications->where('read_at', null);
    //$total_unread = count($unread_notifications);
    $all_messages = DB::table('messages')->where('type', 'yes')->get();
@endphp

<style>
    .message-container {
        background-color: #f9f9f9;
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .message-text {
        font-size: 14px;
        color: #333;
        font-family: 'Cairo', sans-serif;
    }

    .message-button {
        background-color: var(--bs-primary);
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        margin-left: 10px;
    }

    .message-button:hover {
        background-color: var(--bs-primary);
    }
</style>

<li class="dropdown" style="background-color: var(--bs-primary); border-radius: 15px; list-style: none;">
    <a type="button" style="background-color: var(--bs-primary);border-radius: 15px;"
        class="dropdown-toggl tw-inline-flex tw-items-center tw-ring-1 tw-ring-white/10 tw-justify-center tw-text-sm tw-font-medium tw-text-white hover:tw-text-white tw-transition-all tw-duration-200 tw-bg-@if (!empty(session('business.theme_color'))) {{ session('business.theme_color') }}@else{{ 'primary' }} @endif-800 hover:tw-bg-@if (!empty(session('business.theme_color'))) {{ session('business.theme_color') }}@else{{ 'primary' }} @endif-700 tw-p-1.5 tw-rounded-lg"
        data-toggle="dropdown" data-loaded="false">
        <span class="tw-sr-only">
            Messages
        </span>
        <svg aria-hidden="true" class="tw-size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
            stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M3 19v-13a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-12l-4 3z" />
            <path d="M7 9h10" />
            <path d="M7 13h7" />

        </svg>
        {{-- <span class="label label-warning notifications_count">@if (!empty($total_unread)){{$total_unread}}@endif</span> --}}
    </a>
    <ul class="dropdown-menu !tw-p-2 !tw-absolute !tw-z-10 !tw-mt-2 !tw-bg-white !tw-rounded-lg !tw-shadow-lg !tw-ring-1 !tw-ring-gray-200 !focus:tw-outline-none !tw-w-96 {{ app()->getLocale() === 'ar' ? '!tw-left-0 !tw-origin-top-left tw-ml-2' : '!tw-right-0 !tw-origin-top-right' }}"
        @if(app()->getLocale()==='ar') style="right: auto; left: 0;    width: 250px; height:90vh; overflow-y: scroll;" @else style="left: auto !important ; height:90vh; overflow-y: scroll;" @endif>
        <!-- <li class="header">You have 10 unread notifications</li> -->
        <li>
            <!-- inner menu: contains the actual data -->

            <ul class="menu">
                @foreach ($all_messages as $message)
                    <li class="message-container">
                        <span class="message-text">{{ $message->message }}</span>
                        <a href="{{ route('show.message', ['id' => $message->id]) }}" class="message-button">Show</a>
                    </li>
                @endforeach
            </ul>
        </li>

        <div class="text-center">

            <a href="{{ route('submit.message') }}" class="message-button" id="create-btn" style="white-space: normal;">Create
                Message</a>

        </div>


    </ul>
</li>

<input type="hidden" id="notification_page" value="1">
