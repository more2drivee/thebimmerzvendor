@extends('layouts.app')

@section('content')
    @include('survey::layouts.nav')

    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('survey::lang.settings')</h1>
    </section>

    <section class="content-header no-print">
        <form method="POST" action="{{ route('survey.settings.store') }}">
            @csrf
            @component('components.widget', ['class' => 'box-primary', 'title' => __('survey::lang.settings')])
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="active_theme">@lang('survey::lang.active_theme')</label>
                            <select name="active_theme" id="active_theme" class="form-control" required>
                                <option value="light" {{ (optional($surveySettings)->active_theme ?? 'light') === 'light' ? 'selected' : '' }}>@lang('survey::lang.theme_light')</option>
                                <option value="dark" {{ (optional($surveySettings)->active_theme ?? 'light') === 'dark' ? 'selected' : '' }}>@lang('survey::lang.theme_dark')</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group" style="margin-top: 26px;">
                            <label>
                                <input type="checkbox" name="enable_intelligent" value="1" {{ (optional($surveySettings)->enable_intelligent ?? false) ? 'checked' : '' }}>
                                @lang('survey::lang.enable_intelligent')
                            </label>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="rating_threshold">@lang('survey::lang.rating_threshold')</label>
                            <input type="number" name="rating_threshold" id="rating_threshold" class="form-control" min="0" max="100" value="{{ optional($surveySettings)->rating_threshold ?? 80 }}" required>
                            <small class="text-muted">@lang('survey::lang.rating_threshold_help')</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="google_review_url">@lang('survey::lang.google_review_url')</label>
                            <input type="text" name="google_review_url" id="google_review_url" class="form-control" value="{{ optional($surveySettings)->google_review_url }}">
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="facebook_url">@lang('survey::lang.facebook_url')</label>
                            <input type="text" name="facebook_url" id="facebook_url" class="form-control" value="{{ optional($surveySettings)->facebook_url }}">
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="instagram_url">@lang('survey::lang.instagram_url')</label>
                            <input type="text" name="instagram_url" id="instagram_url" class="form-control" value="{{ optional($surveySettings)->instagram_url }}">
                        </div>
                    </div>
                </div>
            @endcomponent

            <div class="row">
                <div class="col-sm-12 text-center">
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">@lang('messages.save')</button>
                </div>
            </div>
        </form>
    </section>
@endsection
