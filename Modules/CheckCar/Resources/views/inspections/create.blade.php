@extends('layouts.app')

@section('title', __('checkcar::lang.module_title'))

@section('css')
<style>
    @media (min-width: 768px) {
        .nav-justified>li {
            display: table-cell;
            width: 10%;
        }
    }
</style>
@endsection

@php
$categoriesData = [];
$subcategoriesData = [];
$obdCodesData = [];

foreach ($categories as $cat) {
$categoriesData[$cat->id] = $cat->name;
if (isset($subcategoriesByCategory[$cat->id])) {
$subcategoriesData[$cat->id] = $subcategoriesByCategory[$cat->id]->pluck('name', 'id')->toArray();
}
}

foreach ($obdCodes as $obd) {
$obdCodesData[$obd->id] = $obd->code . ' - ' . $obd->description;
}
@endphp

@section('content')
@include('checkcar::layouts.nav')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('checkcar::lang.module_title')
    </h1>
    <p class="tw-text-gray-700 tw-mt-1">
        @lang('checkcar::lang.module_subtitle')
    </p>
</section>

<section class="content no-print">
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if (session('status'))
    <div class="alert alert-success">
        {{ session('status') }}
    </div>
    @endif

    <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6">
        <ul class="nav nav-pills nav-justified tw-bg-gray-100 tw-rounded-xl tw-overflow-hidden tw-mb-6">
            <li class="active js-step-pill" data-step="1">
                <a href="#" class="js-step-link tw-font-semibold">
                    @lang('checkcar::lang.step_customers')
                </a>
            </li>
            <li class="js-step-pill" data-step="2">
                <a href="#" class="js-step-link tw-font-semibold">
                    @lang('checkcar::lang.step_car')
                </a>
            </li>
            <li class="js-step-pill" data-step="3">
                <a href="#" class="js-step-link tw-font-semibold">
                    @lang('checkcar::lang.step_inspection')
                </a>
            </li>
            <li class="js-step-pill" data-step="4">
                <a href="#" class="js-step-link tw-font-semibold">
                    @lang('checkcar::lang.step_final_report')
                </a>
            </li>
        </ul>

        {!! Form::open(['route' => 'checkcar.inspections.store', 'method' => 'post', 'id' => 'checkcar_inspection_form']) !!}
        @csrf

        {{-- Step 1: Customers --}}
        <div class="js-step-content" data-step="1">
            <div class="row">
                <div class="col-md-6">
                    <div
                        class="tw-bg-green-50 tw-rounded-2xl tw-border tw-border-green-100 tw-p-4 md:tw-p-5 tw-h-full">
                        <h3 class="tw-text-lg tw-font-bold tw-text-green-800 tw-mb-4">
                            @lang('checkcar::lang.buyer_section_title')
                        </h3>

                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                {!! Form::text('buyer_full_name', old('buyer_full_name'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.full_name')]) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                                {!! Form::text('buyer_phone', old('buyer_phone'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.phone')]) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-id-card"></i></span>
                                {!! Form::text('buyer_id_number', old('buyer_id_number'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.national_id')]) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div
                        class="tw-bg-indigo-50 tw-rounded-2xl tw-border tw-border-indigo-100 tw-p-4 md:tw-p-5 tw-h-full">
                        <h3 class="tw-text-lg tw-font-bold tw-text-indigo-800 tw-mb-4">
                            @lang('checkcar::lang.seller_section_title')
                        </h3>

                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                {!! Form::text('seller_full_name', old('seller_full_name'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.full_name')]) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                                {!! Form::text('seller_phone', old('seller_phone'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.phone')]) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-id-card"></i></span>
                                {!! Form::text('seller_id_number', old('seller_id_number'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.national_id')]) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Car data & team --}}
        <div class="js-step-content" data-step="2" style="display: none;">
            <div class="row">
                <div class="col-md-12">
                    <div
                        class="tw-bg-orange-50 tw-rounded-2xl tw-border tw-border-orange-100 tw-p-4 md:tw-p-5 tw-mb-4">
                        <h3 class="tw-text-lg tw-font-bold tw-text-orange-800 tw-mb-4">
                            @lang('checkcar::lang.car_section_title')
                        </h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-car"></i></span>
                                        {!! Form::text('car_brand', old('car_brand'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.brand')]) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-cog"></i></span>
                                        {!! Form::text('car_model', old('car_model'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.model')]) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        {!! Form::text('car_year', old('car_year'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.year')]) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-palette"></i></span>
                                        {!! Form::text('car_color', old('car_color'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.color')]) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-hashtag"></i></span>
                                        {!! Form::text('car_chassis_number', old('car_chassis_number'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.chassis_number')]) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-id-badge"></i></span>
                                        {!! Form::text('car_plate_number', old('car_plate_number'), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.plate_number')]) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-tachometer-alt"></i></span>
                                        {!! Form::number('car_kilometers', old('car_kilometers', 0), ['class' => 'form-control', 'placeholder' => __('checkcar::lang.km'), 'min' => 0]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div
                        class="tw-bg-purple-50 tw-rounded-2xl tw-border tw-border-purple-100 tw-p-4 md:tw-p-5">
                        <h3 class="tw-text-lg tw-font-bold tw-text-purple-800 tw-mb-4">
                            @lang('checkcar::lang.inspection_team_title')
                        </h3>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                        {!! Form::text('inspection_team[inspectors][]', null, ['class' => 'form-control js-inspector-input', 'placeholder' => __('checkcar::lang.inspectors')]) !!}
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-default js-add-inspector">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </span>
                                    </div>
                                    <div class="js-inspectors-list tw-mt-2"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-wrench"></i></span>
                                        {!! Form::text('inspection_team[technicians][]', null, ['class' => 'form-control js-technician-input', 'placeholder' => __('checkcar::lang.technicians')]) !!}
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-default js-add-technician">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </span>
                                    </div>
                                    <div class="js-technicians-list tw-mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: Inspection sections --}}
        <div class="js-step-content" data-step="3" style="display: none;">
            @php
                $elements = $elements ?? collect();
            @endphp

            @if ($elements->isEmpty())
                <div class="alert alert-info">
                    {{ __('messages.no_data_available') }}
                </div>
            @endif

            @foreach ($elements as $element)
            @php $key = $element->id; @endphp
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-5 tw-mb-4">
                <h3 class="tw-text-lg tw-font-bold tw-text-gray-900 tw-mb-3">
                    {{ $element->name }}
                </h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-star-half-alt"></i></span>
                                {!! Form::select(
                                    "sections[{$key}][status_rating]",
                                    [
                                        'good:3' => __('checkcar::lang.status_good') . ' - 3 ',
                                        'average:2' => __('checkcar::lang.status_average') . ' - 2 ',
                                        'bad:1' => __('checkcar::lang.status_bad') . ' - 1 ',
                                    ],
                                    null,
                                    ['class' => 'form-control']
                                ) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-barcode"></i></span>
                                {!! Form::select("sections[{$key}][obd_codes][]", $obdCodesData, null, ['class' => 'form-control js-obd-select', 'data-section-key' => $key, 'multiple' => 'multiple']) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon" style="vertical-align: top;"><i class="fa fa-sticky-note"></i></span>
                                {!! Form::textarea("sections[{$key}][notes]", null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('checkcar::lang.notes')]) !!}
                            </div>
                        </div>
                    </div>
                </div>
                   @if (!empty($phraseTemplatesBySection ?? []))
                    <div class="tw-mt-3 tw-flex tw-flex-wrap tw-gap-2">
                        @foreach ($phraseTemplatesBySection as $tpl)
                        <button type="button" class="btn btn-xs btn-default js-insert-template"
                            data-section-key="{{ $key }}"
                            data-text="{{ e($tpl->phrase) }}">
                            {{ Str::limit($tpl->phrase, 30) }}
                        </button>
                        @endforeach
                    </div>
                    @endif

                <div class="tw-mt-4">
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                        <h4 class="tw-font-semibold tw-text-base">
                            @lang('checkcar::lang.extra_questions_title')
                        </h4>
                        <button type="button" class="btn btn-xs btn-primary js-add-extra-question" data-section-key="{{ $key }}">
                            <i class="fa fa-plus"></i>
                            @lang('checkcar::lang.add_question')
                        </button>
                    </div>


                    <div class="table-responsive">
                        <table class="table table-bordered tw-mb-0 js-extra-questions-table" data-section-key="{{ $key }}">
                            <thead>
                                <tr>
                                    <th style="width: 20%">@lang('checkcar::lang.question_title')</th>
                                    <th style="width: 15%">@lang('checkcar::lang.question_category')</th>
                                    <th style="width: 15%">@lang('checkcar::lang.question_subcategory')</th>
                                    <th style="width: 15%">@lang('checkcar::lang.question_type')</th>
                                    <th style="width: 30%">@lang('checkcar::lang.question_answer')</th>
                                    <th style="width: 5%"></th>
                                </tr>
                            </thead>
                            <tbody class="js-extra-questions-body">
                                {{-- rows added dynamically by JS --}}
                            </tbody>
                        </table>
                    </div>

                 
                </div>
            </div>
            @endforeach
        </div>

        {{-- Step 4: Final report --}}
        <div class="js-step-content" data-step="4" style="display: none;">
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-5">
                <h3 class="tw-text-lg tw-font-bold tw-text-gray-900 tw-mb-4">
                    @lang('checkcar::lang.final_report_title')
                </h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-star-half-alt"></i></span>
                                {!! Form::select('overall_rating', [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5], 4, ['class' => 'form-control']) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon" style="vertical-align: top;"><i class="fa fa-file-alt"></i></span>
                                {!! Form::textarea('final_summary', null, ['class' => 'form-control', 'rows' => 4, 'placeholder' => __('checkcar::lang.final_summary')]) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-flex tw-items-center tw-justify-between tw-mt-6">
            <button type="button" id="js-prev-step"
                class="btn btn-default tw-rounded-full tw-px-4 tw-py-2 js-step-prev">
                @lang('checkcar::lang.btn_prev')
            </button>
            <div class="tw-flex tw-gap-2">
                <button type="button" id="js-next-step"
                    class="btn btn-primary tw-rounded-full tw-px-4 tw-py-2 js-step-next">
                    @lang('checkcar::lang.btn_next')
                </button>
                <button type="submit" id="js-save-report"
                    class="btn btn-success tw-rounded-full tw-px-4 tw-py-2" style="display: none;">
                    @lang('checkcar::lang.btn_save_report')
                </button>
            </div>
        </div>

        {!! Form::close() !!}
    </div>
</section>
@endsection

@section('javascript')
<script>
    $(function() {
        var currentStep = 1;
        var totalSteps = 4;

        function showStep(step) {
            currentStep = step;
            $('.js-step-content').hide();
            $('.js-step-content[data-step="' + step + '"]').show();

            $('.js-step-pill').removeClass('active');
            $('.js-step-pill[data-step="' + step + '"]').addClass('active');

            $('#js-prev-step').toggle(step > 1);
            $('#js-next-step').toggle(step < totalSteps);
            $('#js-save-report').toggle(step === totalSteps);
        }

        $('.js-step-link').on('click', function(e) {
            e.preventDefault();
            var step = $(this).closest('.js-step-pill').data('step');
            showStep(step);
        });

        $('.js-step-next').on('click', function() {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        });

        $('.js-step-prev').on('click', function() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        });

        // Simple dynamic lists for inspectors / technicians (add more inputs)
        $('.js-add-inspector').on('click', function() {
            var $list = $('.js-inspectors-list');
            var input = '<input type="text" name="inspection_team[inspectors][]" class="form-control tw-mb-2" />';
            $list.append(input);
        });

        $('.js-add-technician').on('click', function() {
            var $list = $('.js-technicians-list');
            var input = '<input type="text" name="inspection_team[technicians][]" class="form-control tw-mb-2" />';
            $list.append(input);
        });

        // Extra survey-like questions per section
        var categoriesData = @json($categoriesData);
        var subcategoriesData = @json($subcategoriesData);

        function buildCategorySelect(baseName) {
            var html = '<select name="' + baseName + '[category_id]" class="form-control js-question-category" data-base-name="' + baseName + '">' +
                '    <option value="">---</option>';

            for (var catId in categoriesData) {
                html += '<option value="' + catId + '">' + categoriesData[catId] + '</option>';
            }

            html += '</select>';
            return html;
        }

        function buildSubcategorySelect(baseName, categoryId) {
            var html = '<select name="' + baseName + '[subcategory_id]" class="form-control js-question-subcategory">' +
                '    <option value="">---</option>';

            if (categoryId && subcategoriesData[categoryId]) {
                for (var subId in subcategoriesData[categoryId]) {
                    html += '<option value="' + subId + '">' + subcategoriesData[categoryId][subId] + '</option>';
                }
            }

            html += '</select>';
            return html;
        }

        function buildAnswerInput(sectionKey, index, type) {
            var baseName = 'sections[' + sectionKey + '][questions][' + index + ']';

            if (type === 'status') {
                return '' +
                    '<select name="' + baseName + '[answer]" class="form-control">' +
                    '    <option value="">---</option>' +
                    '    <option value="good">' + @json(__('checkcar::lang.status_good')) + '</option>' +
                    '    <option value="average">' + @json(__('checkcar::lang.status_average')) + '</option>' +
                    '    <option value="bad">' + @json(__('checkcar::lang.status_bad')) + '</option>' +
                    '</select>';
            } else if (type === 'rating') {
                return '' +
                    '<select name="' + baseName + '[answer]" class="form-control">' +
                    '    <option value="">---</option>' +
                    '    <option value="1">1</option>' +
                    '    <option value="2">2</option>' +
                    '    <option value="3">3</option>' +
                    '    <option value="4">4</option>' +
                    '    <option value="5">5</option>' +
                    '</select>';
            }

            // default text
            return '<input type="text" name="' + baseName + '[answer]" class="form-control" />';
        }

        $('.js-add-extra-question').on('click', function() {
            var sectionKey = $(this).data('section-key');
            var $table = $(this).closest('.tw-mt-4').find('.js-extra-questions-table');
            var $tbody = $table.find('.js-extra-questions-body');
            var index = $tbody.children('tr').length;

            var baseName = 'sections[' + sectionKey + '][questions][' + index + ']';

            var rowHtml = '' +
                '<tr>' +
                '  <td>' +
                '    <input type="text" name="' + baseName + '[title]" class="form-control" placeholder="Title" />' +
                '  </td>' +
                '  <td>' +
                buildCategorySelect(baseName) +
                '  </td>' +
                '  <td class="js-subcategory-cell">' +
                buildSubcategorySelect(baseName, null) +
                '  </td>' +
                '  <td>' +
                '    <select name="' + baseName + '[type]" class="form-control js-question-type" data-section-key="' + sectionKey + '" data-index="' + index + '">' +
                '       <option value="text">' + @json(__('checkcar::lang.question_type_text')) + '</option>' +
                '       <option value="status">' + @json(__('checkcar::lang.question_type_status')) + '</option>' +
                '       <option value="rating">' + @json(__('checkcar::lang.question_type_rating')) + '</option>' +
                '    </select>' +
                '  </td>' +
                '  <td class="js-answer-cell">' +
                buildAnswerInput(sectionKey, index, 'text') +
                '  </td>' +
                '  <td class="text-center">' +
                '    <button type="button" class="btn btn-xs btn-danger js-remove-question"><i class="fa fa-trash"></i></button>' +
                '  </td>' +
                '</tr>';

            $tbody.append(rowHtml);
        });

        // Handle category change to update subcategories
        $(document).on('change', '.js-question-category', function() {
            var baseName = $(this).data('base-name');
            var categoryId = $(this).val();
            var $row = $(this).closest('tr');
            $row.find('.js-subcategory-cell').html(buildSubcategorySelect(baseName, categoryId));
        });

        // delegate type change & remove for dynamic rows
        $(document).on('change', '.js-question-type', function() {
            var sectionKey = $(this).data('section-key');
            var index = $(this).data('index');
            var type = $(this).val();
            var $row = $(this).closest('tr');
            $row.find('.js-answer-cell').html(buildAnswerInput(sectionKey, index, type));
        });

        $(document).on('click', '.js-remove-question', function() {
            $(this).closest('tr').remove();
        });

        // Insert predefined phrases (from DB) into section notes
        $(document).on('click', '.js-insert-template', function() {
            var sectionKey = $(this).data('section-key');
            var text = $(this).data('text') || '';

            if (!text) {
                return;
            }

            var $sectionCard = $(this).closest('.tw-bg-white.tw-rounded-2xl.tw-border.tw-border-gray-200');
            var selector = 'textarea[name="sections[' + sectionKey + '][notes]"]';
            var $notes = $sectionCard.find(selector);

            if ($notes.length) {
                var current = $notes.val() || '';
                $notes.val(current ? current + "\n" + text : text);
                $notes.trigger('change');
            }
        });

        // Initialize Select2 for OBD codes dropdown (searchable multi-select)
        $('.js-obd-select').select2({
            placeholder: @json(__('checkcar::lang.obd_codes')),
            allowClear: true,
            width: '100%',
            closeOnSelect: false
        });

        showStep(1);
    });
</script>
@endsection