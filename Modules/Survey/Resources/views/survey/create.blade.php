@extends('layouts.app')

@section('title', __('survey.create'))

@section('content')
   @include('survey::layouts.nav')

    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('survey::lang.survey')</h1>
    </section>

    <section class="content-header no-print">

        {!! Form::open([
            'url' => action([\Modules\Survey\Http\Controllers\SurveyController::class, 'store']),
            'method' => 'post',
        ]) !!}
        <div class="row">
            <div class="col-md-12 col-sm-12">

                @component('components.widget', ['class' => 'box-primary', 'title' => __('survey::lang.create')])
                    @if (auth()->user()->can('survey.view') || auth()->user()->can('survey.update') || auth()->user()->can('survey.delete'))
                        @php
                            $typeName = [];
                            foreach ($types as $type) {
                                $typeName[$type->id] = $type->name;
                            }
                        @endphp
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('name', __('survey::lang.title') . ':*') !!}
                                    {!! Form::text('title', null, [
                                        'class' => 'form-control',
                                        'required',
                                        'placeholder' => __('survey::lang.title'),
                                    ]) !!}
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('Description', __('survey::lang.description') . ':') !!}
                                    {!! Form::text('description', null, ['class' => 'form-control', 'placeholder' => __('survey::lang.description')]) !!}
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('type', __('survey::lang.type') . ':') !!}
                                    {!! Form::select('type', $typeName, null, ['class' => 'form-control', 'placeholder' => __('survey::lang.select_type')]) !!}
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('survey_category_id', __('survey::lang.category') . ':') !!}
                                    {!! Form::select('survey_category_id', $categories->pluck('name', 'id'), null, ['class' => 'form-control', 'placeholder' => __('messages.please_select')]) !!}
                                </div>
                            </div>

                        </div>


                        <div id="questionsContainer">
                            <div class="form-section">
                                <div class="row">

                                </div>

                                <div class="options-container" style="display: none;">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>@lang('survey::lang.option'):</label>
                                                <div class="options-list">
                                                    <div class="row">
                                                        <div class="col-sm-8">
                                                            <label>@lang('survey::lang.label')</label>
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <label>@lang('survey::lang.score')</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-primary add-option-btn">@lang('survey::lang.addoption')</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>



                            </div>
                        </div>


                        <div class="row">
                            <div class="col-sm-12">
                                <button type="button" id="addQuestionBtn" class="btn btn-success">@lang('survey::lang.addqes')</button>
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

@section('javascript')
    <script type="text/javascript">
        const questionsContainer = document.getElementById('questionsContainer');
        const addQuestionBtn = document.getElementById('addQuestionBtn');

        let questionCount = 0;


        addQuestionBtn.addEventListener('click', () => {
            questionCount++;

            const questionSection = document.createElement('div');
            questionSection.className = 'form-section';
            questionSection.innerHTML = `
                <div class="row">
                    <div class="col-sm-8">
                        <div class="form-group">
                            <label for="question_${questionCount}">@lang('survey::lang.qes'):</label>
                            <input type="text" name="questions[${questionCount}][question_text]" class="form-control" placeholder="@lang('survey::lang.qesplace')" required>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="question_type_${questionCount}">@lang('survey::lang.qestype'):</label>
                            <select name="questions[${questionCount}][type]" class="form-control question-type-selector" required>
                                <option value="text">Text</option>
                                <option value="radio">Single Choice</option>
                                <option value="checkbox">Multiple Choice</option>
                                <option value="rating">Rating</option>
                                <option value="like">Like</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Options Container (for radio/checkbox) -->
                <div class="options-container" style="display: none;">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>@lang('survey::lang.option'):</label>
                                <div class="options-list"></div>
                                <button type="button" class="btn btn-sm btn-primary add-option-btn">@lang('survey::lang.addoption')</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rating Container (for rating type) -->
                <div class="rating-container" style="display: none;">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="min_range_${questionCount}">@lang('survey::lang.minrate'):</label>
                                <input type="number" name="questions[${questionCount}][min_range]" class="form-control" placeholder="@lang('survey::lang.minrate')">
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="max_range_${questionCount}">@lang('survey::lang.maxrate'):</label>
                                <input type="number" name="questions[${questionCount}][max_range]" class="form-control" placeholder="@lang('survey::lang.maxrate')">
                            </div>
                        </div>
                    </div>
                </div>
            `;

            questionsContainer.appendChild(questionSection);

            const questionTypeSelector = questionSection.querySelector('.question-type-selector');
            const optionsContainer = questionSection.querySelector('.options-container');
            const ratingContainer = questionSection.querySelector('.rating-container');
            const addOptionBtn = questionSection.querySelector('.add-option-btn');
            const optionsList = questionSection.querySelector('.options-list');

            questionTypeSelector.addEventListener('change', () => {
                if (questionTypeSelector.value === 'radio' || questionTypeSelector.value === 'checkbox') {
                    optionsContainer.style.display = 'block';
                    ratingContainer.style.display = 'none';
                } else if (questionTypeSelector.value === 'rating') {
                    ratingContainer.style.display = 'block';
                    optionsContainer.style.display = 'none';
                } else {
                    optionsContainer.style.display = 'none';
                    ratingContainer.style.display = 'none';
                }
            });

            addOptionBtn.addEventListener('click', () => {
                const optionItem = document.createElement('div');
                optionItem.className = 'option-item';
                optionItem.innerHTML = `
                    <div class="row">
                        <div class="col-sm-8">
                            <input type="text" name="questions[${questionCount}][options][][label]" class="form-control" placeholder="@lang('survey::lang.addoption')" required>
                        </div>
                        <div class="col-sm-4">
                            <input type="number" name="questions[${questionCount}][options][][score]" class="form-control" placeholder="@lang('survey::lang.option_score')">
                        </div>
                    </div>
                `;
                optionsList.appendChild(optionItem);
            });
        });
    </script>
@endsection
