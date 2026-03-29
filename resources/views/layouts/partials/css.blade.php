<link href="{{ asset('css/tailwind/app.css?v='.$asset_v) }}" rel="stylesheet">

<link rel="stylesheet" href="{{ asset('css/vendor.css?v='.$asset_v) }}">

@if( in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) )
	<link rel="stylesheet" href="{{ asset('css/rtl.css?v='.$asset_v) }}">
@endif

@yield('css')

<!-- app css -->
<link rel="stylesheet" href="{{ asset('css/app.css?v='.$asset_v) }}">

@if(isset($pos_layout) && $pos_layout)
	<style type="text/css">
		.content{
			padding-bottom: 0px !important;
		}
	</style>
@endif
<style type="text/css">
	/*
	* Pattern lock css
	* Pattern direction
	* http://ignitersworld.com/lab/patternLock.html
	*/
	.patt-wrap {
	  z-index: 10;
	}
	.patt-circ.hovered {
	  background-color: #cde2f2;
	  border: none;
	}
	.patt-circ.hovered .patt-dots {
	  display: none;
	}
	.patt-circ.dir {
	  background-image: url("{{asset('/img/pattern-directionicon-arrow.png')}}");
	  background-position: center;
	  background-repeat: no-repeat;
	}
	.patt-circ.e {
	  -webkit-transform: rotate(0);
	  transform: rotate(0);
	}
	.patt-circ.s-e {
	  -webkit-transform: rotate(45deg);
	  transform: rotate(45deg);
	}
	.patt-circ.s {
	  -webkit-transform: rotate(90deg);
	  transform: rotate(90deg);
	}
	.patt-circ.s-w {
	  -webkit-transform: rotate(135deg);
	  transform: rotate(135deg);
	}
	.patt-circ.w {
	  -webkit-transform: rotate(180deg);
	  transform: rotate(180deg);
	}
	.patt-circ.n-w {
	  -webkit-transform: rotate(225deg);
	   transform: rotate(225deg);
	}
	.patt-circ.n {
	  -webkit-transform: rotate(270deg);
	  transform: rotate(270deg);
	}
	.patt-circ.n-e {
	  -webkit-transform: rotate(315deg);
	  transform: rotate(315deg);
	}


	.headerr {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 10px 20px;
		background-color: #f8f9fa;
		border-radius: 5px;
	}

	.logo {
		font-size: 18px;
		font-weight: bold;
	}

	.nav {
		display: flex;
	}

	.nav-list {
		display: flex;
		list-style: none;
		gap: 15px;
	}

	.nav-list a {
		text-decoration: none;
		color: #333;
		font-size: 14px;
	}

	.nav-list a:hover {
		color: #007bff;
	}

	.menu-toggle {
		display: none;
		font-size: 20px;
		background: none;
		border: none;
		cursor: pointer;
	}

	@media (max-width: 768px) {
		.nav {
			display: none;
			flex-direction: column;
			position: absolute;
			top: 60px;
			left: 0;
			right: 0;
			background-color: #f8f9fa;
			padding: 10px;
			border-radius: 5px;
		}

		.nav.show {
			display: flex;
		}

		.menu-toggle {
			display: block;
		}
	}
</style>
@if(!empty($__system_settings['additional_css']))
    {!! $__system_settings['additional_css'] !!}
@endif

