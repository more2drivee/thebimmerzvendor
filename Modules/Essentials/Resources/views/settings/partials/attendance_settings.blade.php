<div class="pos-tab-content">
    <div class="row">

        {{-- ================================================================= --}}
        {{-- Location Required                                                   --}}
        {{-- ================================================================= --}}
        <div class="col-xs-12">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('is_location_required', 1,
                        !empty($settings['is_location_required']) ? 1 : 0,
                        ['class' => 'input-icheck']
                    ) !!}
                    @lang('essentials::lang.is_location_required')
                </label>
            </div>
        </div>

        <div class="clearfix"></div>

        {{-- ================================================================= --}}
        {{-- Grace Times                                                         --}}
        {{-- ================================================================= --}}
        <div class="col-xs-12" style="margin-top:12px;">
            <strong>@lang('essentials::lang.grace_time'):</strong>
        </div>

        <div class="col-xs-6">
            <div class="form-group">
                {!! Form::label('grace_before_checkin',
                    __('essentials::lang.grace_before_checkin') . ':') !!}
                {!! Form::number('grace_before_checkin',
                    !empty($settings['grace_before_checkin']) ? $settings['grace_before_checkin'] : null,
                    ['class' => 'form-control',
                     'placeholder' => __('essentials::lang.grace_before_checkin'),
                     'step' => 1]
                ) !!}
                <p class="help-block">@lang('essentials::lang.grace_before_checkin_help')</p>
            </div>
        </div>

        <div class="col-xs-6">
            <div class="form-group">
                {!! Form::label('grace_after_checkin',
                    __('essentials::lang.grace_after_checkin') . ':') !!}
                {!! Form::number('grace_after_checkin',
                    !empty($settings['grace_after_checkin']) ? $settings['grace_after_checkin'] : null,
                    ['class' => 'form-control',
                     'placeholder' => __('essentials::lang.grace_after_checkin'),
                     'step' => 1]
                ) !!}
                <p class="help-block">@lang('essentials::lang.grace_after_checkin_help')</p>
            </div>
        </div>

        <div class="col-xs-6">
            <div class="form-group">
                {!! Form::label('grace_before_checkout',
                    __('essentials::lang.grace_before_checkout') . ':') !!}
                {!! Form::number('grace_before_checkout',
                    !empty($settings['grace_before_checkout']) ? $settings['grace_before_checkout'] : null,
                    ['class' => 'form-control',
                     'placeholder' => __('essentials::lang.grace_before_checkout'),
                     'step' => 1]
                ) !!}
                <p class="help-block">@lang('essentials::lang.grace_before_checkout_help')</p>
            </div>
        </div>

        <div class="col-xs-6">
            <div class="form-group">
                {!! Form::label('grace_after_checkout',
                    __('essentials::lang.grace_after_checkout') . ':') !!}
                {!! Form::number('grace_after_checkout',
                    !empty($settings['grace_after_checkout']) ? $settings['grace_after_checkout'] : null,
                    ['class' => 'form-control',
                     'placeholder' => __('essentials::lang.grace_after_checkout'),
                     'step' => 1]
                ) !!}
                <p class="help-block">@lang('essentials::lang.grace_before_checkin_help')</p>
            </div>
        </div>

        <div class="clearfix"></div>

        {{-- ================================================================= --}}
        {{-- Import Template Defaults                                            --}}
        {{-- ================================================================= --}}
        <div class="col-xs-12" style="margin-top:18px;">
            <div style="
                background: linear-gradient(135deg,#11998e 0%,#38ef7d 100%);
                border-radius: 6px 6px 0 0;
                padding: 10px 16px;
            ">
                <strong style="color:#fff;font-size:14px;">
                    <i class="fa fa-download"></i>
                    &nbsp; Import Template Defaults
                </strong>
            </div>
            <div style="
                border: 1px solid #ddd;
                border-top: none;
                border-radius: 0 0 6px 6px;
                padding: 16px;
                background: #fafafa;
            ">
                <p class="text-muted" style="margin-bottom:14px;">
                    <i class="fa fa-info-circle"></i>
                    These values pre-populate the <strong>Download Template</strong> form on the
                    Attendance import tab. Employees can still override them per-download.
                </p>

                {{-- Row A: Default Location + Default Department --}}
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('default_import_location_id',
                                'Default Location:',
                                ['class' => 'control-label']
                            ) !!}
                            @if(!empty($locations))
                                {!! Form::select('default_import_location_id', $locations,
                                    !empty($settings['default_import_location_id']) ? $settings['default_import_location_id'] : null,
                                    [
                                        'class'       => 'form-control select2',
                                        'id'          => 'default_import_location_id',
                                        'placeholder' => __('messages.please_select'),
                                        'style'       => 'width:100%',
                                    ]
                                ) !!}
                            @else
                                <p class="text-muted">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    No locations found.
                                </p>
                            @endif
                            <p class="help-block">Pre-selects this location on the template download form (filters shifts).</p>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('default_import_dept_id',
                                'Default Department:',
                                ['class' => 'control-label']
                            ) !!}
                            @if(!empty($shifts))
                                @php
                                    $depts_for_settings = \App\Category::where('category_type', 'hrm_department')
                                        ->where('business_id', request()->session()->get('user.business_id'))
                                        ->pluck('name', 'id');
                                @endphp
                                {!! Form::select('default_import_dept_id', $depts_for_settings,
                                    !empty($settings['default_import_dept_id']) ? $settings['default_import_dept_id'] : null,
                                    [
                                        'class'       => 'form-control select2',
                                        'id'          => 'default_import_dept_id',
                                        'placeholder' => __('messages.please_select'),
                                        'style'       => 'width:100%',
                                    ]
                                ) !!}
                            @else
                                <p class="text-muted">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    No departments found.
                                </p>
                            @endif
                            <p class="help-block">Pre-selects this department filter on the template download form.</p>
                        </div>
                    </div>
                </div>

                {{-- Row B: Default Shift --}}
                <div class="row">
                    <div class="col-sm-5">
                        <div class="form-group">
                            {!! Form::label('default_import_shift_id',
                                'Default Shift:',
                                ['class' => 'control-label']
                            ) !!}
                            @if(!empty($shifts))
                                {!! Form::select('default_import_shift_id', $shifts,
                                    !empty($settings['default_import_shift_id']) ? $settings['default_import_shift_id'] : null,
                                    [
                                        'class'       => 'form-control select2',
                                        'id'          => 'default_import_shift_id',
                                        'placeholder' => __('messages.please_select'),
                                        'style'       => 'width:100%',
                                    ]
                                ) !!}
                            @else
                                <p class="text-muted">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    No shifts found. Please create shifts first.
                                </p>
                            @endif
                            <p class="help-block">Pre-selects this shift on the template download form.</p>
                        </div>
                    </div>
                </div>

                {{-- Default Clock-in / Clock-out --}}
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('default_clock_in_time',
                                __('essentials::lang.clock_in_time') . ':',
                                ['class' => 'control-label']
                            ) !!}
                            <input type="time"
                                   id="default_clock_in_time"
                                   name="default_clock_in_time"
                                   class="form-control"
                                   value="{{ !empty($settings['default_clock_in_time']) ? $settings['default_clock_in_time'] : '08:00' }}">
                            <p class="help-block">Default clock-in time for downloaded templates (HH:MM).</p>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('default_clock_out_time',
                                __('essentials::lang.clock_out_time') . ':',
                                ['class' => 'control-label']
                            ) !!}
                            <input type="time"
                                   id="default_clock_out_time"
                                   name="default_clock_out_time"
                                   class="form-control"
                                   value="{{ !empty($settings['default_clock_out_time']) ? $settings['default_clock_out_time'] : '17:00' }}">
                            <p class="help-block">Default clock-out time for downloaded templates (HH:MM).</p>
                        </div>
                    </div>
                </div>

            </div>{{-- /inner panel body --}}
        </div>{{-- /col-xs-12 --}}

        <div class="clearfix"></div>

        {{-- ================================================================= --}}
        {{-- Info note                                                           --}}
        {{-- ================================================================= --}}
        <div class="col-xs-12" style="margin-top:16px;">
            <p>
                <i class="fas fa-info-circle"></i>
                <span class="text-danger">
                    @lang('essentials::lang.allow_users_for_attendance_moved_to_role')
                </span>
            </p>
        </div>

    </div>{{-- /row --}}
</div>{{-- /pos-tab-content --}}
