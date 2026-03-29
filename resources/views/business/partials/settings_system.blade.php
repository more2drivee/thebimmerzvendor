<div class="pos-tab-content">
     <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('theme_color', __('lang_v1.theme_color')); !!}
                {!! Form::select('theme_color', $theme_colors,   $business->theme_color, 
                    ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $page_entries = [25 => 25, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, -1 => __('lang_v1.all')];
                @endphp
                {!! Form::label('default_datatable_page_entries', __('lang_v1.default_datatable_page_entries')); !!}
                {!! Form::select('common_settings[default_datatable_page_entries]', $page_entries, !empty($common_settings['default_datatable_page_entries']) ? $common_settings['default_datatable_page_entries'] : 25 , 
                    ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'default_datatable_page_entries']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_tooltip', 1, $business->enable_tooltip , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'business.show_help_text' ) }}
                  </label>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('business_work_schedule', __('Business Work Schedule')) !!}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Day') }}</th>
                                <th>{{ __('Working') }}</th>
                                <th>{{ __('Start Time') }}</th>
                                <th>{{ __('End Time') }}</th>
                                <th>{{ __('Total Hours') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $workScheduleDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                            @endphp
                            @foreach ($workScheduleDays as $weekday)
                                @php
                                    $isWorking = !empty($common_settings['work_days'][$weekday]);
                                    $dayHours = $common_settings['work_hours'][$weekday] ?? [];
                                    $startAttributes = ['class' => 'form-control work-start', 'data-weekday' => $weekday];
                                    $endAttributes = ['class' => 'form-control work-end', 'data-weekday' => $weekday];
                                    if (!$isWorking) {
                                        $startAttributes['disabled'] = true;
                                        $endAttributes['disabled'] = true;
                                    }
                                @endphp
                                <tr class="work-schedule-row" data-weekday="{{ $weekday }}">
                                    <td class="text-capitalize">{{ $weekday }}</td>
                                    <td>
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox("common_settings[work_days][$weekday]", 1, $isWorking, ['class' => 'input-icheck workday-toggle']) !!}
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        {!! Form::time("common_settings[work_hours][$weekday][start]", $dayHours['start'] ?? null, $startAttributes) !!}
                                    </td>
                                    <td>
                                        {!! Form::time("common_settings[work_hours][$weekday][end]", $dayHours['end'] ?? null, $endAttributes) !!}
                                    </td>
                                    <td>
                                        {!! Form::number("common_settings[work_hours][$weekday][total]", $dayHours['total'] ?? null, ['class' => 'form-control input_number work-total', 'step' => '0.25', 'min' => '0', 'readonly' => true]) !!}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>