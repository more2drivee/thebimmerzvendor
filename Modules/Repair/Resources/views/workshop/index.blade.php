<!-- Button to Open Create Modal -->
<button type="button" class="tw-dw-btn tw-dw-btn-sm tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right" id="openCreateModal">
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

<!-- Workshops DataTable -->
<table id="workshop_table" class="table" style="width:100%">
    <thead>
        <tr>
            <th>@lang('messages.name')</th>
            <th>@lang('messages.location')</th>
            <th>@lang('messages.status')</th>
            <th>@lang('messages.actions')</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<!-- Modal for Editing a Workshop -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">{{ __('repair.edit_workshop') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('messages.close') }}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Error messages for Edit Modal -->
        <div id="editErrorMessages" class="alert alert-danger" style="display:none;"></div>
        <!-- Form for Editing Workshop -->
        <form id="editWorkshopForm">
          @csrf
          <input type="hidden" name="_method" value="PUT" />
          <input type="hidden" id="workshop_id_edit" name="id">
          <div class="form-group">
            <label for="name_edit">{{ __('messages.name') }}</label>
            <input type="text" class="form-control" id="name_edit" name="name" required>
          </div>
          <div class="form-group">
            <label for="business_location_id_edit">{{ __('messages.location') }}</label>
            <select class="form-control" id="business_location_id_edit" name="business_location_id" required>
              <!-- Business locations will be dynamically populated here -->
            </select>
          </div>
          <div class="form-group">
            <label for="status_edit">{{ __('messages.status') }}</label>
            <select class="form-control" id="status_edit" name="status" required>
              <option value="available">Available</option>
              <option value="not_available">Not Available</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.close') }}</button>
        <button type="button" class="btn btn-primary" id="saveWorkshopEdit">{{ __('messages.update') }}</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Creating a Workshop -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createModalLabel">{{ __('repair.create_workshop') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('messages.close') }}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Error messages for Create Modal -->
        <div id="createErrorMessages" class="alert alert-danger" style="display:none;"></div>
        <!-- Form for Creating Workshop -->
        <form id="createWorkshopForm">
          @csrf
          <div class="form-group">
            <label for="name_create">{{ __('messages.name') }}</label>
            <input type="text" class="form-control" id="name_create" name="name" required>
          </div>
          <div class="form-group">
            <label for="business_location_id_create">{{ __('messages.location') }}</label>
            <select class="form-control" id="business_location_id_create" name="business_location_id" required>
              <!-- Business locations will be dynamically populated here -->
            </select>
          </div>
          <div class="form-group">
            <label for="status_create">{{ __('messages.status') }}</label>
            <select class="form-control" id="status_create" name="status" required>
              <option value="available">Available</option>
              <option value="not_available">Not Available</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.close') }}</button>
        <button type="button" class="btn btn-primary" id="saveWorkshopCreate">{{ __('messages.save') }}</button>
      </div>
    </div>
  </div>
</div>
