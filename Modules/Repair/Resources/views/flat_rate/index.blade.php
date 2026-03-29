<!-- Button to Open Create Modal -->
<button type="button" class="tw-dw-btn tw-dw-btn-sm tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right" id="openFlatRateModal">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
         class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
         <path stroke="none" d="M0 0h24v24H0z" fill="none" />
         <path d="M12 5l0 14" />
         <path d="M5 12l14 0" />
    </svg>
    @lang('messages.add')
</button>
<br><br>

<!-- Flat Rate DataTable -->
@php
  // Try to get current user's location id; adjust as needed for your app
  $current_location_id = session('user.business_location_id') ?? (auth()->check() ? optional(auth()->user())->business_location_id : null);
@endphp

<div class="form-group">
  <label for="flat_rate_location_filter">{{ __('business.location') }}</label>
  <select id="flat_rate_location_filter" class="form-control" data-current-location="{{ $current_location_id }}">
    <option value="">{{ __('messages.all') }}</option>
    @isset($business_locations)
      @foreach($business_locations as $id => $name)
        <option value="{{ $id }}" {{ (string)$current_location_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
      @endforeach
    @endisset
  </select>
</div>
<table id="flat_rate_table" class="table" style="width:100%">
    <thead>
        <tr>
            <th>@lang('messages.name')</th>
            <th>@lang('repair::lang.flat_rate_hours')</th>
      
            <th>@lang('business.location')</th>
            <th>@lang('lang_v1.active')</th>
            <th>@lang('messages.actions')</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<!-- Modal for Creating a Flat Rate Service -->
<div class="modal fade" id="flatRateModal" tabindex="-1" role="dialog" aria-labelledby="flatRateModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="flatRateModalLabel">{{ __('Add Flat Rate Service') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('messages.close') }}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Error messages for Create Modal -->
        <div id="flatRateErrorMessages" class="alert alert-danger" style="display:none;"></div>
        <!-- Form for Creating Flat Rate Service -->
        <form id="createFlatRateForm">
          @csrf
          <div class="form-group">
            <label for="name_create">{{ __('messages.name') }} *</label>
            <input type="text" class="form-control" id="name_create" name="name" required>
          </div>
          <div class="form-group">
            <label for="business_location_id_create">{{ __('business.location') }} *</label>
            <select class="form-control" id="business_location_id_create" name="business_location_id" required>
              @isset($business_locations)
                @foreach($business_locations as $id => $name)
                  <option value="{{ $id }}" {{ (string)$current_location_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
              @endisset
            </select>
          </div>
          <div class="form-group">
            <label for="hours_create">{{ __('repair::lang.flat_rate_hours') }} *</label>
            <input type="number" class="form-control" id="hours_create" name="price_per_hour" step="0.5" min="0" required>
          </div>

          {{-- Price (tax) removed as requested --}}
          <div class="form-group">
            <div class="checkbox">
              <label>
                <input type="checkbox" id="is_active_create" name="is_active" value="1" checked>
                {{ __('lang_v1.active') }}
              </label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.close') }}</button>
        <button type="button" class="btn btn-primary" id="saveFlatRate">{{ __('messages.save') }}</button>
      </div>
    </div>
  </div>
</div>
