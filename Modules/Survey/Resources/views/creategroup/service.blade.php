@extends('layouts.app')

@section('title', __('Create Group'))

@section('content')
   @include('survey::layouts.nav')

    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('survey::lang.group')</h1>


        {!! Form::open([
            'url' => action([\Modules\Survey\Http\Controllers\CreateGroupController::class, 'indexService']),
            'method' => 'post',
        ]) !!}

        @php
            $names = [];
            if (
                auth()->user()->can('survey.view') ||
                auth()->user()->can('survey.update') ||
                auth()->user()->can('survey.delete')
            ) {
                foreach ($services as $service) {
                    $names[$service->id] = $service->name;
                }
            }
        @endphp
        <div class="row">
            <div class="col-md-12 col-sm-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('survey::lang.create-group')])
                    @if (auth()->user()->can('survey.view') || auth()->user()->can('survey.update') || auth()->user()->can('survey.delete'))
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('name', __('survey::lang.name') . ':*') !!}
                                    {!! Form::text('title', null, [
                                        'class' => 'form-control',
                                        'required',
                                        'placeholder' => __('survey::lang.title'),
                                    ]) !!}
                                </div>
                            </div>


                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('contact_id', __('survey::lang.group') . ':') !!}
                                    {!! Form::select('contact_id[]', $names, null, [
                                        'multiple' => 'multiple',
                                        'class' => 'form-control select2',
                                    ]) !!}
                                </div>
                            </div>

                        </div>
                    @endif
                @endcomponent
            </div>?
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
