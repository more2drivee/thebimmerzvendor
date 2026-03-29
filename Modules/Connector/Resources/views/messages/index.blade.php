@extends('layouts.app')

@section('content')
    <section class="content-header no-print">

        {!! Form::open([
            'url' => action([\Modules\Connector\Http\Controllers\Api\MessagesController::class, 'store']),
            'method' => 'post',
        ]) !!}
        <div class="row">
            <div class="col-md-12 col-sm-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => 'Create Message'])
                    @if (auth()->user()->can('survey.view') || auth()->user()->can('survey.update') || auth()->user()->can('survey.delete'))
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('name', __('Title') . ':*') !!}
                                    {!! Form::textarea('message', null, [
                                        'class' => 'form-control',
                                        'required',
                                        'placeholder' => __('Enter Your Message'),
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
@endsection
