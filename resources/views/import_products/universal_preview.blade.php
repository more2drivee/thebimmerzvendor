@extends('layouts.app')
@section('title', 'Preview Universal Product Import')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Preview Universal Product Import</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\UniversalProductImportController::class, 'store']), 'method' => 'post', 'id' => 'import_universal_form']) !!}
    {!! Form::hidden('file_name', $file_name) !!}

    {!! Form::hidden('default_unit', $settings['default_unit']) !!}
    {!! Form::hidden('default_manage_stock', $settings['default_manage_stock']) !!}
    {!! Form::hidden('default_tax_amount', $settings['default_tax_amount']) !!}
    {!! Form::hidden('create_opening_stock', $settings['create_opening_stock']) !!}
    {!! Form::hidden('create_purchase', $settings['create_purchase']) !!}
    {!! Form::hidden('require_supplier', $settings['require_supplier']) !!}
    {!! Form::hidden('auto_create_supplier', $settings['auto_create_supplier']) !!}
    {!! Form::hidden('create_location', $settings['create_location']) !!}

    @component('components.widget')
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <strong>Note:</strong> Showing first 100 rows for preview. Total rows in file: {{ count($parsed_array) - 1 }}
                </div>
                <div class="scroll-top-bottom" style="max-height: 420px;">
                    <table class="table table-condensed table-striped">
                        @foreach(array_slice($parsed_array, 0, 101) as $row)
                            <tr>
                                <td>@if($loop->index > 0 ){{$loop->index}} @else # @endif</td>
                                @foreach($row as $k => $v)
                                    @if($loop->parent->index == 0)
                                        <th>{{$v}}</th>
                                    @else
                                        <td>{{$v}}</td>
                                    @endif
                                @endforeach
                            </tr>
                            @if($loop->index == 0)
                                <tr>
                                    <td>@if($loop->index > 0 ){{$loop->index}}@endif</td>
                                    @foreach($row as $k => $v)
                                        <td>
                                            {!! Form::select('import_fields[' . $k . ']', $import_fields, $match_array[$k], ['class' => 'form-control import_fields select2', 'placeholder' => __('lang_v1.skip'), 'style' => 'width: 100%;']); !!}
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    @endcomponent
    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white pull-right">@lang('messages.submit')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).on('submit', 'form#import_universal_form', function(){
        var import_fields = [];

        $('.import_fields').each(function() {
            if ($(this).val()) {
                import_fields.push($(this).val());
            }
        });

        if (import_fields.indexOf('product_name') === -1) {
            alert('Product Name is required');
            return false;
        }

        if (hasDuplicates(import_fields)) {
            alert("{{__('lang_v1.cannot_select_a_field_twice')}}");
            return false;
        }
    });

    function hasDuplicates(array) {
        return (new Set(array)).size !== array.length;
    }
</script>
@endsection
