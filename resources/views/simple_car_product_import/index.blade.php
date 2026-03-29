@extends('layouts.app')
@section('title', 'Simple Car Product Import')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">استيراد منتجات السيارات (مبسّط)</h1>
    <p class="text-muted">ارفع ملف إكسل بالترتيب المبين، وسيتم إدخال المنتج وتهيئة التوافق والمخزون.</p>
</section>

<section class="content">
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary'])
            {!! Form::open(['url' => action([\App\Http\Controllers\SimpleCarProductImportController::class, 'store']), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('excel', 'ملف الإكسل المراد استيراده:') !!}
                            {!! Form::file('excel', ['accept'=> '.xls, .xlsx, .csv', 'required' => 'required']); !!}
                            <small class="text-muted">ترتيب الأعمدة المتوقع:</small>
                            <ul style="margin-top: 5px;">
                                <li>اسم الصنف</li>
                                <li>الكمية</li>
                                <li>كود الصنف (SKU)</li>
                                <li>السيارة (مثال: BMW 3 Series E90 2004-2013)</li>
                                <li>الفئة</li>
                                <li>التكلفة</li>
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
            @component('components.widget', ['class' => 'box-info', 'title' => 'إرشادات الاستيراد'])
                <ul>
                    <li>الوحدة الافتراضية ستكون "قطعة" إذا لم توجد وحدة في الملف.</li>
                    <li>سيتم إنشاء الفئات والماركات إن لم تكن موجودة.</li>
                    <li>سيتم تحويل بيانات السيارة إلى توافق المنتج مع الماركة والموديل ونطاق السنوات.</li>
                    <li>إذا كانت الكمية أكبر من 0، سيتم إنشاء مخزون افتتاحي تلقائياً.</li>
                    <li>تأكد من أن الصف الأول هو رؤوس الأعمدة كما في المثال.</li>
                    <li>صيقة السيارة: "اسم الماركة اسم الموديل سنة البداية-سنة النهاية"</li>
                </ul>
            @endcomponent
        </div>
    </div>

    @if (session('status'))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }}">
                    {{ session('status.msg') }}
                </div>
            </div>
        </div>
    @endif
</section>

@endsection
