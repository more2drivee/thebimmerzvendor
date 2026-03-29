@extends('layouts.app')

@section('title', __('survey.update'))

@section('content')
    @include('survey::layouts.nav')

    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('survey::lang.survey')</h1>

        {!! Form::open([
            'url' => action([\Modules\Survey\Http\Controllers\SurveyController::class, 'update']),
            'method' => 'post',
        ]) !!}
        <div class="row">
            <div class="col-md-12 col-sm-12">

                @component('components.widget', ['class' => 'box-primary', 'title' => __('survey.update')])
                    @if (auth()->user()->can('survey.view') || auth()->user()->can('survey.update') || auth()->user()->can('survey.delete'))
                        <input type="hidden" name="survey_id" id="survey_id" value="<?= $survey->id ?>">
                        @php
                            $typeName = [];
                            foreach ($types as $type) {
                                $typeName[$type->id] = $type->name;
                            }
                        @endphp
                        <div class="row">
                            {!! Form::hidden('survey_id', $survey->id, ['id' => 'survey_id']) !!}
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('name', __('Title') . ':*') !!}
                                    {!! Form::text('title', $survey->title, [
                                        'class' => 'form-control',
                                        'required',
                                        'placeholder' => __('Title'),
                                    ]) !!}
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('Description', __('Description') . ':') !!}
                                    {!! Form::text('description', $survey->description, [
                                        'class' => 'form-control',
                                        'placeholder' => __('Description'),
                                    ]) !!}
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('survey_category_id', __('survey::lang.category') . ':') !!}
                                    {!! Form::select('survey_category_id', $categories->pluck('name', 'id'), $survey->survey_category_id, ['class' => 'form-control', 'placeholder' => __('messages.please_select')]) !!}
                                </div>
                            </div>

                        </div>


                        <div id="questionsContainer">

                        </div>


                        <div class="row">
                            <div class="col-sm-12">
                                <button type="button" id="addQuestionBtn" class="btn btn-success">Add Question</button>
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
    <script type="application/json" id="survey-questions-data">{!! json_encode($questions) !!}</script>
    <script type="application/json" id="survey-option-label-placeholder">{!! json_encode(__('survey::lang.option_label')) !!}</script>
    <script type="application/json" id="survey-option-score-placeholder">{!! json_encode(__('survey::lang.option_score')) !!}</script>
    <script type="text/javascript">
        const questionsContainer = document.getElementById('questionsContainer');
        const addQuestionBtn = document.getElementById('addQuestionBtn');
        const parseJson = (id, fallback) => {
            const el = document.getElementById(id);
            return JSON.parse(el ? el.textContent : fallback);
        };
        const optionLabelPlaceholder = parseJson('survey-option-label-placeholder', '""');
        const optionScorePlaceholder = parseJson('survey-option-score-placeholder', '""');

        const questions = parseJson('survey-questions-data', '[]');
        let questionCount = Array.isArray(questions) ? questions.length : 0;

        questions.forEach((question, index) => {
            renderQuestion(question, index);
        });


        addQuestionBtn.addEventListener('click', () => {
            questionCount++;
            renderQuestion({}, questionCount);
        });

        function renderQuestion(question, index) {
            const questionSection = document.createElement('div');
            questionSection.className = 'form-section';
            const rangeArray = JSON.parse(question.description || '[]');

            questionSection.innerHTML = `
            <div class="row">
                <div class="col-sm-8">
                    <div class="form-group">
                        <label for="question_${index}">Question:</label>
                        <input type="text" name="questions[${index}][question_text]" class="form-control" value="${question.text || ''}" placeholder="Enter your question" required>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="question_type_${index}">Question Type:</label>
                        <select name="questions[${index}][type_id]" class="form-control question-type-selector" required>
                            <option value="1" ${question.type_id === 1 ? 'selected' : ''}>Text</option>
                            <option value="2" ${question.type_id === 2 ? 'selected' : ''}>Single Choice</option>
                            <option value="3" ${question.type_id === 3 ? 'selected' : ''}>Multiple Choice</option>
                            <option value="4" ${question.type_id === 4 ? 'selected' : ''}>Rating</option>
                            <option value="5" ${question.type_id === 5 ? 'selected' : ''}>Like</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Options Container (for radio/checkbox) -->
            <div class="options-container" style="display: ${[2, 3].includes(question.type_id) ? 'block' : 'none'};">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label>Options:</label>
                            <div class="options-list">
                                ${renderOptions(rangeArray, index)}
                            </div>
                            <button type="button" class="btn btn-sm btn-primary add-option-btn">Add Option</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rating Container (for rating type) -->
            <div class="rating-container" style="display: ${question.type_id === 4 ? 'block' : 'none'};">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="min_range_${index}">Min Rating:</label>
                            <input type="number" name="questions[${index}][min_range]" class="form-control" value="${rangeArray[0] || ''}" placeholder="Enter Min Rating">
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="max_range_${index}">Max Rating:</label>
                            <input type="number" name="questions[${index}][max_range]" class="form-control" value="${rangeArray[1] || ''}" placeholder="Enter Max Rating">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Like Container (for like type) -->
            <div class="like-container" style="display: ${question.type_id === 5 ? 'block' : 'none'};">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> This question will be displayed as a Like/Dislike rating with thumbs up and thumbs down buttons.
                </div>
            </div>
            </div>`;

            questionsContainer.appendChild(questionSection);


            const questionTypeSelector = questionSection.querySelector('.question-type-selector');
            const optionsContainer = questionSection.querySelector('.options-container');
            const ratingContainer = questionSection.querySelector('.rating-container');
            const addOptionBtn = questionSection.querySelector('.add-option-btn');
            const optionsList = questionSection.querySelector('.options-list');


            questionTypeSelector.addEventListener('change', () => {
                if ([2, 3].includes(parseInt(questionTypeSelector.value))) {
                    optionsContainer.style.display = 'block';
                    ratingContainer.style.display = 'none';
                } else if (questionTypeSelector.value === '4') {
                    ratingContainer.style.display = 'block';
                    optionsContainer.style.display = 'none';
                } else if (questionTypeSelector.value === '5') {
                    optionsContainer.style.display = 'none';
                    ratingContainer.style.display = 'none';
                } else {
                    optionsContainer.style.display = 'none';
                    ratingContainer.style.display = 'none';
                    optionsList.innerHTML = '';
                }
            });


            addOptionBtn.addEventListener('click', () => {
                const optionItem = document.createElement('div');
                optionItem.className = 'option-item';
                optionItem.innerHTML = `
                    <div class="row">
                        <div class="col-sm-8">
                            <input type="text" name="questions[${index}][options][][label]" class="form-control" placeholder="${optionLabelPlaceholder}" required>
                        </div>
                        <div class="col-sm-4">
                            <input type="number" name="questions[${index}][options][][score]" class="form-control" placeholder="${optionScorePlaceholder}">
                        </div>
                    </div>`;
                optionsList.appendChild(optionItem);
            });

            questionTypeSelector.dispatchEvent(new Event('change'));
        }


        function renderOptions(options, questionIndex) {
            return options.map(option => {
                const label = typeof option === 'object' ? (option.label || '') : option;
                const score = typeof option === 'object' && option.score !== undefined ? option.score : '';
                return `
            <div class="option-item">
                <div class="row">
                    <div class="col-sm-8">
                        <input type="text" name="questions[${questionIndex}][options][][label]" class="form-control" value="${label}" placeholder="${optionLabelPlaceholder}" required>
                    </div>
                    <div class="col-sm-4">
                        <input type="number" name="questions[${questionIndex}][options][][score]" class="form-control" value="${score}" placeholder="${optionScorePlaceholder}">
                    </div>
                </div>
            </div>`;
            }).join('');
        }
    </script>
@endsection
