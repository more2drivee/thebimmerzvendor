@extends('layouts.app')

@section('title', __('Add User to Service'))

@section('content')
   @include('survey::layouts.nav')

    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('Add User to Service')</h1>

        {!! Form::open([
            'url' => action([\Modules\Survey\Http\Controllers\CreateGroupController::class, 'indexAddUserToService']),
            'method' => 'post',
        ]) !!}

        @php
            $names = [];
            $nameOfContacts = [];
            if (
                auth()->user()->can('survey.view') ||
                auth()->user()->can('survey.update') ||
                auth()->user()->can('survey.delete')
            ) {
                foreach ($services as $service) {
                    $names[$service->id] = $service->name;
                }
                foreach ($contacts as $contact) {
                    $nameOfContacts[$contact->id] = $contact->name;
                }
            }
        @endphp
        <div class="row">
            <div class="col-md-12 col-sm-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('Add User to Service')])
                    @if (auth()->user()->can('survey.view') || auth()->user()->can('survey.update') || auth()->user()->can('survey.delete'))
                        <div class="row">

                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('contact_id', __('Select Users') . ':') !!}
                                    {!! Form::select('contact_id[]', $nameOfContacts, null, [
                                        'multiple' => 'multiple',
                                        'class' => 'form-control select2',
                                    ]) !!}
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('service_id', __('Select Service') . ':') !!}
                                    {!! Form::select('service', $names, null, [
                                        'class' => 'form-control select2',
                                        'placeholder' => __('messages.please_select'),
                                    ]) !!}
                                </div>
                            </div>

                        </div>
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
