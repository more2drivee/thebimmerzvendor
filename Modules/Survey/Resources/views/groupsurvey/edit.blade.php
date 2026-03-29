@extends('layouts.app')

@section('title', __('Create Group'))

@section('content')
    @include('survey::layouts.nav')

    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('Group')</h1>


        {!! Form::open([
            'url' => action([\Modules\Survey\Http\Controllers\CreateGroupController::class, 'update']),
            'method' => 'post',
        ]) !!}

        @php
            $names = [];
            $selectedUsers = [];
            if (
                auth()->user()->can('survey.view') ||
                auth()->user()->can('survey.update') ||
                auth()->user()->can('survey.delete')
            ) {
                foreach ($contacts as $contact) {
                    $names[$contact->id] = $contact->name;
                }

                foreach ($contactsID as $contactID) {
                    $selectedUsers[] = $contactID->user_id;
                }
            }

        @endphp
        <div class="row">
            <div class="col-md-12 col-sm-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('Create Group')])
                    @if (auth()->user()->can('survey.view') || auth()->user()->can('survey.update') || auth()->user()->can('survey.delete'))
                        <input type="hidden" name="group_id" id="group_id" value="<?= $groupName->id ?>">

                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('name', __('Name') . ':*') !!}
                                    {!! Form::text('title', $groupName->name, [
                                        'class' => 'form-control',
                                        'required',
                                    ]) !!}
                                </div>
                            </div>


                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('contact_id', __('Select Users') . ':') !!}
                                    {!! Form::select('contact_id[]', $names, $selectedUsers, [
                                        'multiple' => 'multiple',
                                        'class' => 'form-control select2',
                                    ]) !!}
                                </div>
                            </div>

                            {{-- 
<div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('enable_cash_denomination_for_payment_methods', __('lang_v1.enable_cash_denomination_for_payment_methods') . ':') !!}
                {!! Form::select('pos_settings[enable_cash_denomination_for_payment_methods][]', $names, isset($pos_settings['enable_cash_denomination_for_payment_methods']) ? $pos_settings['enable_cash_denomination_for_payment_methods'] : null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'multiple' ]); !!}
            </div>
        </div> --}}

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
