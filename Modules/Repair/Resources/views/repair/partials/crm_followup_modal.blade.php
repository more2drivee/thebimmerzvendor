<div class="modal-header">
    <h5 class="modal-title">{{ __('repair::lang.add_crm_followup') }}</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                {{ __('repair::lang.crm_followup_info') }}
            </div>
        </div>
    </div>

    <form id="crm_followup_form" action="{{ route('repair.store_crm_followup') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>{{ __('contact.contact') }}</label>
                    <input type="text" class="form-control" value="{{ $contact->name ?? '' }}" readonly>
                    <input type="hidden" name="contact_id" value="{{ $contact->id ?? '' }}">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>{{ __('crm::lang.title') }} <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>{{ __('crm::lang.description') }}</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ __('crm::lang.start_datetime') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" name="start_datetime" class="form-control datetimepicker" required>
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ __('crm::lang.end_datetime') }}</label>
                    <div class="input-group">
                        <input type="text" name="end_datetime" class="form-control datetimepicker">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ __('crm::lang.assigned_to') }} <span class="text-danger">*</span></label>
                    <select name="user_id" class="form-control select2" required>
                        <option value="">{{ __('lang_v1.select') }}</option>
                        @foreach($users as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ __('crm::lang.followup_category') }}</label>
                    <select name="followup_category_id" class="form-control select2">
                        <option value="">{{ __('lang_v1.select') }}</option>
                        @foreach($followup_categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>{{ __('crm::lang.notify_via') }}</label>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="notify_via[sms]" value="1"> {{ __('crm::lang.sms') }}
                        </label>
                        <label class="ml-3">
                            <input type="checkbox" name="notify_via[mail]" value="1"> {{ __('business.email') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>{{ __('crm::lang.notify_before') }}</label>
                    <select name="notify_type" class="form-control">
                        <option value="minute">{{ __('crm::lang.minute') }}</option>
                        <option value="hour">{{ __('crm::lang.hour') }}</option>
                        <option value="day">{{ __('lang_v1.day') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="allow_notification" value="1">
                        {{ __('crm::lang.allow_notification') }}
                    </label>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
    <button type="submit" form="crm_followup_form" class="btn btn-primary">{{ __('messages.save') }}</button>
</div>

<script>
    $(document).ready(function() {
        $('.datetimepicker').datetimepicker({
            format: 'YYYY-MM-DD HH:mm',
            useCurrent: false
        });

        $('.select2').select2();
    });
</script>
