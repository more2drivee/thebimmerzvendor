@extends('layouts.app')
@section('title', __('timemanagement::technician_attendance.clock_in_technician'))

@section('content')
@include('timemanagement::partials.nav')

<section class="content-header">
    <h1>@lang('timemanagement::technician_attendance.clock_in_technician')</h1>
</section>

<section class="content">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('timemanagement::technician_attendance.clock_in_form')</h3>
                    <div class="box-tools">
                        <a href="{{ route('timemanagement.index') }}" class="btn btn-box-tool">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('timemanagement.clock_in') }}">
                    @csrf
                    <div class="box-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="user_id">@lang('timemanagement::technician_attendance.select_technician') *</label>
                            <select name="user_id" id="user_id" class="form-control select2" required style="width: 100%;">
                                <option value="">@lang('timemanagement::technician_attendance.please_select')</option>
                                @foreach ($technicians as $technician)
                                    <option value="{{ $technician->id }}" {{ old('user_id') == $technician->id ? 'selected' : '' }}>
                                        {{ $technician->first_name }} {{ $technician->last_name }}
                                        @if($technician->email)
                                            ({{ $technician->email }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="clock_in_time">@lang('timemanagement::technician_attendance.clock_in_time')</label>
                            <input type="datetime-local" name="clock_in_time" id="clock_in_time" class="form-control" value="{{ old('clock_in_time', date('Y-m-d\TH:i')) }}">
                            <small class="text-muted">@lang('timemanagement::technician_attendance.leave_blank_for_current_time')</small>
                        </div>

                        <div class="form-group">
                            <label for="note">@lang('timemanagement::technician_attendance.note')</label>
                            <textarea name="note" id="note" class="form-control" rows="3" placeholder="@lang('timemanagement::technician_attendance.add_note')">{{ old('note') }}</textarea>
                        </div>
                    </div>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">@lang('timemanagement::technician_attendance.clock_in')</button>
                        <a href="{{ route('timemanagement.index') }}" class="btn btn-default">@lang('timemanagement::technician_attendance.back')</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endsection
