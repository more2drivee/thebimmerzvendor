@extends('layouts.app')

@section('title', __('survey.send'))

@section('content')
   @include('survey::layouts.nav')

    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('survey::lang.survey')</h1>


        {!! Form::open([
            'url' => action([\Modules\Survey\Http\Controllers\KnowSurveyController::class, 'store']),
            'method' => 'post',
        ]) !!}

        @php
            $names = [];
            if (
                auth()->user()->can('survey.view') ||
                auth()->user()->can('survey.update') ||
                auth()->user()->can('survey.delete')
            ) {
                foreach ($users as $user) {
                    $names[$user->id] = $user->name;
                }
            }
        @endphp
        <div class="row">
            <div class="col-md-12 col-sm-12">

                @component('components.widget', ['class' => 'box-primary', 'title' => __('survey.send')])
                    @if (auth()->user()->can('survey.view') || auth()->user()->can('survey.update') || auth()->user()->can('survey.delete'))
                        <div class="col-sm-4 @if (!session('business.enable_category')) hide @endif">
                            <div class="form-group">
                                {!! Form::label('user_id', __('Select User') . ':') !!}
                                {!! Form::select('user_id', $names, null, [
                                    'placeholder' => __('messages.please_select'),
                                    'class' => 'form-control select2',
                                ]) !!}
                            </div>
                        </div>
                        {!! Form::hidden('survey_id', $id, ['id' => 'survey_id']) !!}
                    @endif
                @endcomponent
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <input type="hidden" name="submit_type" id="submit_type">
                <div class="text-center">
                    <div class="btn-group">
                        <button type="submit" value="submit"
                            class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">@lang('messages.save')</button>
                    </div>
                </div>
            </div>
        </div>

        {!! Form::close() !!}
    </section>
@stop
