@extends('layouts.app')
@section('title', 'Simple Product Import')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">استيراد المنتجات (مبسّط)</h1>
    <p class="text-muted">ارفع ملف إكسل بالترتيب المبين، وسيتم إدخال المنتج وتهيئة التوافق (MODEL_YEAR_RANGE).</p>
    <small>مرجع الأعمدة: d:\pos-main\altrapos\pos\test.xlsx</small>
</section>

<section class="content">
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary'])
            {!! Form::open(['url' => action([\App\Http\Controllers\ImportProductsController::class, 'storeSimple']), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('products_excel', 'ملف الإكسل المراد استيراده:') !!}
                            {!! Form::file('products_excel', ['accept'=> '.xls, .xlsx, .csv', 'required' => 'required']); !!}
                            <small class="text-muted">ترتيب الأعمدة المتوقع:</small>
                            <ul style="margin-top: 5px;">
                                <li>اسم الصنف</li>
                                <li>كود الصنف 1 (SKU)</li>
                                <li>CATEGORY</li>
                                <li>SUB-CATEGORY</li>
                                <li>CARBRAND</li>
                                <li>MODEL_YEAR_RANGE (مثال: BMW 3 Series E90 2004–2013; BMW 3 Series E46 1997–2006)</li>
                                <li>brand (مثال: Genuine BMW)</li>
                                <li>MANAGE STOCK (1=yes 0=No)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="submit" class="btn btn-primary" style="margin-top: 25px;">استيراد</button>
                    </div>
                </div>
            {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-info', 'title' => 'إرشادات مبسّطة'])
                <ul>
                    <li>الوحدة الافتراضية ستكون "قطعة" إذا لم توجد وحدة في الملف.</li>
                    <li>سيتم إنشاء الفئات، الفئات الفرعية، والماركات إن لم تكن موجودة.</li>
                    <li>سيتم تحويل MODEL_YEAR_RANGE إلى توافق المنتج، لكل موديل ونطاق سنوات.</li>
                    <li>تأكد من أن الصف الأول هو رؤوس الأعمدة كما في المثال.</li>
                </ul>
            @endcomponent
        </div>
    </div>
</section>

@endsection