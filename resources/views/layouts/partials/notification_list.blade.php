@if(!empty($notifications_data))
  @foreach($notifications_data as $notification_data)
    @php
      $payload = $notification_data['data'] ?? [];
      $action = trim($payload['action'] ?? '');
      $jobSheetNo = trim($payload['job_sheet_no'] ?? '');
      $estimateNo = trim($payload['estimate_no'] ?? ($payload['estimator_no'] ?? ''));
      $primaryParts = [];
      if ($action !== '') {
        $primaryParts[] = $action;
      }
      if ($jobSheetNo !== '') {
        $primaryParts[] = $jobSheetNo;
      } elseif ($estimateNo !== '') {
        $primaryParts[] = $estimateNo;
      }
      $primary = implode(' - ', $primaryParts);
      if ($primary === '' && !empty($notification_data['message'])) { $primary = $notification_data['message']; }
      if ($primary === '' && !empty($notification_data['msg'])) { $primary = strip_tags($notification_data['msg']); }
    @endphp
    <li class="@if(empty($notification_data['read_at'])) unread @endif notification-li tw-flex tw-items-center tw-gap-2 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-600 tw-transition-all tw-duration-200 tw-rounded-lg hover:tw-text-gray-900 hover:tw-bg-gray-100" data-id="{{ $notification_data['id'] ?? '' }}">
      <input type="checkbox" class="notification-select" value="{{ $notification_data['id'] ?? '' }}" />
      <a href="{{$notification_data['link'] ?? '#'}}" @if(isset($notification_data['show_popup']) && $notification_data['show_popup']) class="show-notification-in-popup" @endif >
        <span class="notif-info">{!! $primary !!}</span>
        <span class="time">{{$notification_data['created_at']}}</span>
      </a>
    </li>
  @endforeach
@else
  <li class="text-center no-notification notification-li">
    @lang('lang_v1.no_notifications_found')
  </li>
@endif