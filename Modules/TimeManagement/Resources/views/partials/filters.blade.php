<form method="get" action="{{ $action ?? route('timemanagement.dashboard') }}" id="filter-form" class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-flex-wrap">
  <div class="tw-flex tw-items-center tw-gap-3 tw-flex-wrap">
   
    <div>
      <label class="tw-block tw-text-xs tw-font-semibold tw-text-gray-500 tw-uppercase">@lang('timemanagement::lang.location')</label>
      <select name="location_id" id="location_id" class="tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-900 tw-bg-white tw-rounded-lg tw-border tw-border-gray-300 hover:tw-bg-gray-50">
        <option value="">@lang('messages.all')</option>
        @foreach($locations as $id => $name)
          <option value="{{ $id }}" {{ (string)($location_id ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
      </select>
    </div>
  </div>
  <div class="tw-flex tw-items-center tw-gap-3">
    <input type="hidden" name="start_date" id="start_date" value="{{ $start_date ?? '' }}">
    <input type="hidden" name="end_date" id="end_date" value="{{ $end_date ?? '' }}">
    <button type="button" id="performance_date_filter" class="tw-inline-flex tw-items-center tw-justify-center tw-gap-1 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-900 tw-transition-all tw-duration-200 tw-bg-white tw-rounded-lg tw-border tw-border-gray-300 hover:tw-bg-gray-50">
      <svg aria-hidden="true" class="tw-size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
        <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
        <path d="M16 3v4" />
        <path d="M8 3v4" />
        <path d="M4 11h16" />
      </svg>
      <span>@lang('messages.filter_by_date')</span>
      <svg aria-hidden="true" class="tw-size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
        <path d="M6 9l6 6l6 -6" />
      </svg>
    </button>
    <button type="submit" class="btn btn-primary filter-btn">@lang('messages.apply')</button>
  </div>
</form>
