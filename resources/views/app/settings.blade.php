@extends('layouts.app')
@section('title', 'About Settings')

@section('content')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Pages
    </h1>
</section>

<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
       

            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav flex-row">

                    <li class="active">
                        <a href="{{ route('about.settings') }}">
                            <i class="fa fa-info-circle"></i> About Us
                        </a>
                    </li>

                    <li>
                        <a href="{{ url('/blogs') }}" >
                            <i class="fa fa-book"></i> @lang('blog.blogs')
                        </a>
                    </li>
                @if (auth()->user()?->can('carmarket.index') || auth()->user()?->can('admin'))

                    <li>
                        <a href="{{ url('/carmarket') }}" >
                            <i class="fa fa-car"></i> @lang('carmarket::lang.module_title')
                        </a>
                    </li>
@endif
                </ul>
            </div>
        </div>
    </nav>
</section>

<section class="content no-print">

    {!! Form::open([
        'route' => 'about.update',
        'method' => 'post',
        'id' => 'about_form'
    ]) !!}

    @component('components.widget', ['class' => 'box-primary', 'title' => 'About Us Content'])

        <div class="form-group">
            {!! Form::label('about_us', 'About Us') !!}
            {!! Form::textarea('about_us', $about_us ?? null, [
                'class' => 'form-control',
                'rows' => 6
            ]) !!}
        </div>

        @slot('tool')
            <div class="box-tools">
                <button type="submit"
                    class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-rounded-full">
                    <i class="fa fa-save"></i> Save
                </button>
            </div>
        @endslot

    @endcomponent

    {!! Form::close() !!}

</section>

@stop