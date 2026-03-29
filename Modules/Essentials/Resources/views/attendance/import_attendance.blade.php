{{-- ====================================================================
     ATTENDANCE IMPORT  –  redesigned (t.txt-like format)
     Sections:
       1. Upload / Import
       2. Template Download  (Location → Shifts cascade, FIXED)
       3. Column Instructions  (new 9-column format)
==================================================================== --}}

@php
    $shiftsUrl   = action([\Modules\Essentials\Http\Controllers\AttendanceController::class, 'getShiftsByLocation']);
    $templateUrl = action([\Modules\Essentials\Http\Controllers\AttendanceController::class, 'generateAttendanceTemplate']);
    $importUrl   = action([\Modules\Essentials\Http\Controllers\AttendanceController::class, 'importAttendance']);
@endphp

<div class="row">
<div class="col-sm-12">

{{-- ================================================================
     SECTION 1 – UPLOAD / IMPORT
================================================================ --}}
<div class="panel panel-default">
    <div class="panel-heading" style="padding:10px 15px;">
        <h4 class="panel-title" style="margin:0;">
            <i class="fa fa-upload"></i>&nbsp; @lang('essentials::lang.import_attendance')
        </h4>
    </div>
    <div class="panel-body">

        {!! Form::open([
            'url'     => $importUrl,
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
            'id'      => 'import_attendance_form',
        ]) !!}

        <div class="alert alert-info" style="margin-bottom:14px;padding:10px 14px;">
            <strong><i class="fa fa-info-circle"></i> File Format:</strong>
            &nbsp; The file must follow the <strong>9-column format</strong> described in the
            <em>Column Instructions</em> section below.
            <br>
            <small>
                Col 1: <strong>Username / Fingerprint&nbsp;ID</strong> &nbsp;|&nbsp;
                Col 2: <strong>Date</strong> &nbsp;|&nbsp;
                Col 3: <strong>Shift</strong> &nbsp;|&nbsp;
                Col 4–5: <strong>On/Off&nbsp;Duty</strong> &nbsp;|&nbsp;
                Col 6–7: <strong>Clock&nbsp;In/Out</strong> &nbsp;|&nbsp;
                Col 8–9: <strong>Dept / Designation</strong> (reference)
            </small>
        </div>

        <div class="row">
            {{-- File picker --}}
            <div class="col-sm-7">
                <div class="form-group">
                    {!! Form::label('attendance_file', __('product.file_to_import') . ':', ['class' => 'control-label']) !!}
                    <input type="file" name="attendance" id="attendance_file"
                           accept=".xls,.xlsx" required
                           class="form-control" style="padding:4px 6px;">
                    <small class="text-muted">
                        <i class="fa fa-file-excel-o"></i>
                        .xls / .xlsx &nbsp;|&nbsp; Max 31 days per file recommended
                    </small>
                </div>
            </div>

            {{-- Submit --}}
            <div class="col-sm-5" style="padding-top:25px;">
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                    <i class="fa fa-upload"></i>&nbsp; @lang('messages.submit')
                </button>
            </div>
        </div>

        {!! Form::close() !!}
    </div>
</div>

<hr style="margin:20px 0;">

{{-- ================================================================
     SECTION 2 – TEMPLATE DOWNLOAD
================================================================ --}}
<div class="panel panel-default">
    <div class="panel-heading" style="padding:10px 15px;">
        <h4 class="panel-title" style="margin:0;">
            <i class="fa fa-download"></i>&nbsp; @lang('lang_v1.download_template_file')
            <small style="font-size:12px;">
                &mdash; pre-filled with shift employees &amp; times
            </small>
        </h4>
    </div>
    <div class="panel-body">

        <div class="alert alert-info" style="margin-bottom:18px;">
            <strong><i class="fa fa-lightbulb-o"></i> How it works:</strong>
            <ol style="margin:6px 0 0 0;padding-left:18px;">
                <li>
                    <strong>Select a Location</strong> — the Shift list filters to shifts
                    assigned to employees in that location.
                </li>
                <li>
                    <strong>Pick a Shift</strong>. Leave blank and enter a Clock-In time
                    to let the system auto-detect the shift.
                </li>
                <li>
                    Choose a <strong>date range</strong> and click <strong>Download</strong>.
                    The template will contain one row per employee per day.
                </li>
            </ol>
        </div>

        <form id="download_template_form"
              method="GET"
              action="{{ $templateUrl }}">

            {{-- ── Row 1: Location + Department ─────────────────── --}}
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label class="control-label">
                            <i class="fa fa-map-marker text-primary"></i>
                            @lang('essentials::lang.filter_by_location')
                            <span class="text-danger">*</span>
                        </label>
                        <select name="location_id"
                                id="tpl_location_id"
                                class="form-control select2"
                                style="width:100%;"
                                required>
                            <option value="">-- @lang('messages.please_select') --</option>
                            @foreach($locations as $loc_id => $loc_name)
                                <option value="{{ $loc_id }}"
                                    {{ !empty($settings['default_import_location_id']) && $settings['default_import_location_id'] == $loc_id ? 'selected' : '' }}>
                                    {{ $loc_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="help-block" style="font-size:11px;margin-bottom:0;">
                            <i class="fa fa-exclamation-circle text-danger"></i>
                            Select a location first to load its shifts.
                        </p>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        <label class="control-label">
                            <i class="fa fa-building text-primary"></i>
                            @lang('essentials::lang.filter_by_department')
                            <small class="text-muted">(@lang('lang_v1.optional'))</small>
                        </label>
                        <select name="department_id"
                                id="tpl_department_id"
                                class="form-control select2"
                                style="width:100%;">
                            <option value="">-- @lang('lang_v1.all') --</option>
                            @foreach($departments as $dept_id => $dept_name)
                                <option value="{{ $dept_id }}"
                                    {{ !empty($settings['default_import_dept_id']) && $settings['default_import_dept_id'] == $dept_id ? 'selected' : '' }}>
                                    {{ $dept_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="help-block" style="font-size:11px;margin-bottom:0;">
                            Only employees from this department appear in the template.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ── Row 3: Date range ──────────────────────────────── --}}
            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        <label class="control-label">
                            <i class="fa fa-calendar text-primary"></i>
                            @lang('essentials::lang.date_from')
                            <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               id="tpl_date_from"
                               name="date_from"
                               class="form-control"
                               value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        <label class="control-label">
                            <i class="fa fa-calendar text-primary"></i>
                            @lang('essentials::lang.date_to')
                            <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               id="tpl_date_to"
                               name="date_to"
                               class="form-control"
                               value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
                    </div>
                </div>

                <div class="col-sm-3" style="padding-top:28px;">
                    <small class="text-muted">
                        <i class="fa fa-calendar-check-o"></i>
                        Max 31 days per download.
                        <br>
                        <span id="tpl_days_count" class="text-info" style="font-weight:600;"></span>
                    </small>
                </div>
            </div>

            {{-- ── Row 4: Clock-in / Clock-out ────────────────────── --}}
            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        <label class="control-label">
                            @lang('essentials::lang.clock_in_time')
                            <span class="text-danger">*</span>
                            <i class="fa fa-magic text-success"
                               id="tpl_ci_auto_icon"
                               style="display:none;"
                               title="Auto-filled from shift start time"></i>
                        </label>
                        <input type="time"
                               id="tpl_clock_in"
                               name="clock_in_time"
                               class="form-control"
                               value="{{ !empty($settings['default_clock_in_time']) ? $settings['default_clock_in_time'] : '08:00' }}">
                        <small class="text-muted">
                            Also used for shift auto-detection when no shift is selected.
                        </small>
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        <label class="control-label">
                            @lang('essentials::lang.clock_out_time')
                            <small class="text-muted">(@lang('lang_v1.optional'))</small>
                            <i class="fa fa-magic text-success"
                               id="tpl_co_auto_icon"
                               style="display:none;"
                               title="Auto-filled from shift end time"></i>
                        </label>
                        <input type="time"
                               id="tpl_clock_out"
                               name="clock_out_time"
                               class="form-control"
                               value="{{ !empty($settings['default_clock_out_time']) ? $settings['default_clock_out_time'] : '17:00' }}">
                    </div>
                </div>

                <div class="col-sm-3" style="padding-top:25px;">
                    <button type="submit"
                            id="download_template_btn"
                            class="tw-dw-btn tw-dw-btn-success tw-text-white"
                            disabled
                            title="Select a location and shift first">
                        <i class="fa fa-download"></i>&nbsp; @lang('lang_v1.download_template_file')
                    </button>
                </div>
            </div>

            {{-- ── Live summary badge ──────────────────────────────── --}}
            <div id="tpl_summary" style="display:none;margin-top:6px;">
                <div class="alert alert-info" style="padding:8px 14px;margin-bottom:0;">
                    <i class="fa fa-info-circle"></i>
                    Will generate:
                    <strong id="sum_days"></strong> day(s) &times;
                    <strong id="sum_users"></strong> employee(s) =
                    <strong id="sum_rows"></strong> row(s)
                    &nbsp;|&nbsp; Shift: <strong id="sum_shift"></strong>
                    &nbsp;|&nbsp; In: <strong id="sum_ci"></strong>
                    &nbsp;|&nbsp; Out: <strong id="sum_co"></strong>
                </div>
            </div>

        </form>
    </div>
</div>

<hr style="margin:20px 0;">

{{-- ================================================================
     SECTION 3 – COLUMN INSTRUCTIONS  (9-column t.txt-like format)
================================================================ --}}
<div class="panel panel-default">
    <div class="panel-heading" style="padding:10px 15px;">
        <h4 class="panel-title" style="margin:0;">
            <i class="fa fa-table"></i>&nbsp;
            @lang('lang_v1.instruction') &mdash; File Columns
        </h4>
    </div>
    <div class="panel-body" style="padding:0;">

        <div class="alert alert-warning" style="margin:12px 14px 0;padding:8px 12px;font-size:12px;">
            <i class="fa fa-exclamation-triangle"></i>
            <strong>Row skipping:</strong>
            Rows where both <em>Clock In</em> and <em>Clock Out</em> are empty are
            automatically skipped (treated as absent / no record). All other columns
            follow the rules below.
        </div>

        <table class="table table-bordered table-striped" style="margin:12px 0 0;">
            <thead>
                <tr class="bg-light-blue-active">
                    <th style="width:44px;">#</th>
                    <th style="width:220px;">Column</th>
                    <th style="width:120px;">Required?</th>
                    <th>Instruction / Example</th>
                </tr>
            </thead>
            <tbody>

                {{-- Column 1 --}}
                <tr>
                    <td>1</td>
                    <td>
                        <strong>Username / Fingerprint&nbsp;ID</strong>
                    </td>
                    <td><span class="label label-danger">Required</span></td>
                    <td>
                        The employee's <strong>login username</strong>
                        or their <strong>Fingerprint / Biometric ID</strong>
                        (set on the employee profile).
                        Username is tried first; if not found, the value is matched
                        against Fingerprint ID.
                        <br>
                        <small class="text-info">
                            <i class="fa fa-info-circle"></i>
                            Corresponds to <em>AC-No.</em> in device export files.
                        </small>
                    </td>
                </tr>

                {{-- Column 2 --}}
                <tr>
                    <td>2</td>
                    <td>
                        <strong>Date</strong>
                    </td>
                    <td><span class="label label-danger">Required</span></td>
                    <td>
                        Date of attendance.
                        Accepted formats:
                        <code>M/D/YYYY</code> &nbsp; <code>YYYY-MM-DD</code> &nbsp; <code>DD-MM-YYYY</code>
                        <br>
                        Examples: <code>2/1/2026</code> &nbsp; <code>2026-02-01</code>
                        <br>
                        <small class="text-muted">
                            Clock-in and clock-out times (cols 6–7) are combined with this date
                            to produce full date-time values.
                        </small>
                    </td>
                </tr>

                {{-- Column 3 --}}
                <tr>
                    <td>3</td>
                    <td>
                        <strong>Shift (Timetable)</strong>
                    </td>
                    <td>
                        <span class="label label-default">Optional</span>
                        <span class="label label-success">Auto-detected</span>
                    </td>
                    <td>
                        Name of the shift exactly as it appears in the system
                        (e.g. <code>10AM</code>, <code>R1 9:30</code>, <code>Night</code>).
                        <br>
                        <small class="text-success">
                            <i class="fa fa-magic"></i>
                            If blank, the system auto-detects the shift from the employee's
                            active shift assignments and the clock-in time (±2 h tolerance).
                            Falls back to the shift selected in the Upload section above.
                        </small>
                    </td>
                </tr>

                {{-- Column 4 --}}
                <tr>
                    <td>4</td>
                    <td>
                        <strong>On Duty (Shift Start)</strong>
                    </td>
                    <td><span class="label label-default">Optional</span></td>
                    <td>
                        The scheduled start time for this shift row.
                        Format: <code>HH:MM</code> &nbsp; Example: <code>10:00</code>
                        <br>
                        <small class="text-muted">
                            Used as a reference / verification column.
                            The system uses the shift record's own start time internally.
                        </small>
                    </td>
                </tr>

                {{-- Column 5 --}}
                <tr>
                    <td>5</td>
                    <td>
                        <strong>Off Duty (Shift End)</strong>
                    </td>
                    <td><span class="label label-default">Optional</span></td>
                    <td>
                        The scheduled end time for this shift row.
                        Format: <code>HH:MM</code> &nbsp; Example: <code>19:00</code>
                        <br>
                        <small class="text-muted">
                            Reference column only.
                        </small>
                    </td>
                </tr>

                {{-- Column 6 --}}
                <tr>
                    <td>6</td>
                    <td>
                        <strong>@lang('essentials::lang.clock_in_time')</strong>
                    </td>
                    <td>
                        <span class="label label-warning">Conditional</span>
                    </td>
                    <td>
                        Actual clock-in time.
                        Format: <code>HH:MM</code> &nbsp; Example: <code>10:04</code>
                        <br>
                        <small class="text-muted">
                            Combined with the <strong>Date</strong> column to produce a full
                            date-time stamp. Leave blank (along with Clock Out) to mark the
                            day as absent — the row will be skipped.
                        </small>
                    </td>
                </tr>

                {{-- Column 7 --}}
                <tr>
                    <td>7</td>
                    <td>
                        <strong>@lang('essentials::lang.clock_out_time')</strong>
                    </td>
                    <td><span class="label label-default">Optional</span></td>
                    <td>
                        Actual clock-out time.
                        Format: <code>HH:MM</code> &nbsp; Example: <code>19:26</code>
                        <br>
                        <small class="text-muted">
                            Combined with the <strong>Date</strong> column (next day if
                            clock-out &lt; clock-in, i.e. overnight shift).
                            Leave blank if the employee did not clock out.
                        </small>
                    </td>
                </tr>

                {{-- Column 8 --}}
                <tr>
                    <td>8</td>
                    <td>
                        <strong>@lang('essentials::lang.department')</strong>
                    </td>
                    <td>
                        <span class="label label-default">Optional</span>
                        <span class="label label-warning">Reference only</span>
                    </td>
                    <td>
                        Department name for human verification in the spreadsheet.
                        This column is <strong>not imported</strong> into attendance records.
                        <br>
                        Example: <code>Maintenance</code>
                    </td>
                </tr>

                {{-- Column 9 --}}
                <tr>
                    <td>9</td>
                    <td>
                        <strong>@lang('essentials::lang.designation')</strong>
                    </td>
                    <td>
                        <span class="label label-default">Optional</span>
                        <span class="label label-warning">Reference only</span>
                    </td>
                    <td>
                        Designation / job title for human reference.
                        This column is <strong>not imported</strong>.
                        <br>
                        Example: <code>Technician</code>
                    </td>
                </tr>

            </tbody>
        </table>

        {{-- Sample rows --}}
        <div style="padding:12px 14px 14px;">
            <p class="text-muted" style="margin-bottom:6px;font-size:12px;">
                <i class="fa fa-table"></i>
                <strong>Sample file rows</strong> (tab/Excel columns):
            </p>
            <div style="overflow-x:auto;">
                <table class="table table-condensed table-bordered"
                       style="font-size:11px;margin-bottom:0;min-width:900px;">
                    <thead style="background:#f5f5f5;">
                        <tr>
                            <th>Username / FP-ID</th>
                            <th>Date</th>
                            <th>Shift</th>
                            <th>On Duty</th>
                            <th>Off Duty</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Department</th>
                            <th>Designation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>5</td>
                            <td>2/1/2026</td>
                            <td>10AM</td>
                            <td>10:00</td>
                            <td>19:00</td>
                            <td>10:04</td>
                            <td>19:26</td>
                            <td>Maintenance</td>
                            <td>Technician</td>
                        </tr>
                        <tr class="text-muted">
                            <td>5</td>
                            <td>2/8/2026</td>
                            <td>10AM</td>
                            <td>10:00</td>
                            <td>19:00</td>
                            <td><em>(blank)</em></td>
                            <td><em>(blank)</em></td>
                            <td>Maintenance</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>john_doe</td>
                            <td>2/23/2026</td>
                            <td>Night</td>
                            <td>20:30</td>
                            <td>01:00</td>
                            <td>20:28</td>
                            <td>01:05</td>
                            <td>Operations</td>
                            <td>Supervisor</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-muted" style="font-size:11px;margin-top:6px;margin-bottom:0;">
                <i class="fa fa-info-circle"></i>
                Row 2 (blank Clock In/Out) will be <strong>skipped</strong> — treated as absent.
                Row 3 (Night shift) clock-out will be recorded on the <strong>next day</strong>
                automatically since 01:05 &lt; 20:28.
            </p>
        </div>

    </div>{{-- /panel-body --}}
</div>{{-- /panel --}}

</div>{{-- /col-sm-12 --}}
</div>{{-- /row --}}
