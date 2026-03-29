@extends('layouts.app')
@section('title', __('home.home'))
@section('content')
<link rel="stylesheet" href="{{ asset('resources/css/app.css') }}">
<div id="homepage-blur">

    <div class="header_top  content_icons o_web_client o_home_menu_background">
        {{--    <header class="header_bottom">--}}
        {{--        @include('layouts.partials.header')--}}
        {{--    </header>--}}

        <style>
            @keyframes skeleton-loading {
                0% {
                    background-color: #cac9c9;
                }
                50% {
                    background-color: #dddcdc;
                }
                100% {
                    background-color: #d1cece;
                }
            }

            .skeleton-loader {
                animation: skeleton-loading 1.5s infinite;
                border-radius: 12px;
            }

            .skeleton-app-box {
                width: 110px;
                height: 110px;
                border-radius: 20px;
                margin-bottom: 1rem;
                animation: skeleton-loading 1.5s infinite;
            }

            .skeleton-badge {
                width: 150px;
                height: 32px;
                border-radius: 9999px;
                margin-bottom: 1rem;
                animation: skeleton-loading 1.5s infinite;
            }

            .skeleton-container {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 1.3rem;
                justify-items: center;
                align-items: start;
                padding: 0;
            }

            .skeleton-section {
                width: 100%;
            }

            .skeleton-section-wrapper {
                display: none;
                opacity: 1;
                transition: opacity 0.3s ease;
            }

            .skeleton-section-wrapper.show {
                display: block;
            }

            /* #homepage-blur {
    filter: blur(10px); 
    opacity: 0.6;      
    transition: all 0.6s ease;
} */

#homepage-blur.loaded {
    filter: blur(0);
    opacity: 1;
}
.home-section {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.5s ease;
}

.home-section.show {
    opacity: 1;
    transform: translateY(0);
}
     .Productivity-app-box,
.Operations-app-box,
.Sales-app-box,
.Finance-app-box,
.Inventory-app-box,
.Management-app-box,
.Tools-app-box {
    border-radius: 20px;
    transition: all 0.35s ease;
}

.Productivity-app-box:hover,
.Operations-app-box:hover,
.Sales-app-box:hover,
.Finance-app-box:hover,
.Inventory-app-box:hover,
.Management-app-box:hover,
.Tools-app-box:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
}

.Productivity-app-box:hover {
    background: linear-gradient(135deg, rgba(165,66,215,0.35), rgba(165,66,215,0.15));
}

.Operations-app-box:hover {
    background: linear-gradient(135deg, rgba(11,100,244,0.35), rgba(11,100,244,0.15));
}

.Sales-app-box:hover {
    background: linear-gradient(135deg, rgba(34,195,93,0.35), rgba(34,195,93,0.15));
}

.Finance-app-box:hover {
    background: linear-gradient(135deg, rgba(231,176,8,0.35), rgba(231,176,8,0.15));
}

.Inventory-app-box:hover {
    background: linear-gradient(135deg, rgba(226,54,112,0.35), rgba(226,54,112,0.15));
}

.Management-app-box:hover {
    background: linear-gradient(135deg, rgba(26,162,230,0.35), rgba(26,162,230,0.15));
}

.Tools-app-box:hover {
    background: linear-gradient(135deg, rgba(244,123,37,0.35), rgba(244,123,37,0.15));
}
.o_caption{
    font-family: 'Cairo', sans-serif !important;
}
.Productivity-app-box:hover i,
.Productivity-app-box:hover .o_caption,
.Operations-app-box:hover i,
.Operations-app-box:hover .o_caption,
.Sales-app-box:hover i,
.Sales-app-box:hover .o_caption,
.Finance-app-box:hover i,
.Finance-app-box:hover .o_caption,
.Inventory-app-box:hover i,
.Inventory-app-box:hover .o_caption,
.Management-app-box:hover i,
.Management-app-box:hover .o_caption,
.Tools-app-box:hover i,
.Tools-app-box:hover .o_caption {
    color: #fff !important;
}
.o_apps_grid {
    display: grid !important;
    grid-template-columns: repeat(5, 1fr) !important; 
    gap: 1.3rem;
    justify-items: center;
    align-items: start;
}



.app-box {
    width: 110px;
    height: 110px;
    margin-bottom: 1rem;
    /* background-color: #f8f9fa; */
    padding: 0.25rem; 
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
}
.o_apps_grid {
    display: grid !important;
    grid-template-columns: repeat(5, 1fr) !important; 
    gap: 1.3rem;
    justify-items: center;
    align-items: start;
}

.app-menu-item {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;          
    width: 100%;         
    height: 100%;       
    padding: 0.25rem;     
    border-radius: 0.75rem; 
}


@media (min-width: 768px) {

    .app-menu-item {
        padding: 0.5rem;
    }
}
@media (max-width: 768px) {
    .o_apps_grid {
    display: grid !important;
    grid-template-columns: repeat(1, 1fr) !important; 
    gap: 1.3rem;
    justify-items: center;
    align-items: start;
}
    .app-box {
        width: 110px;
        height: 95px;
        margin-bottom: 1rem;
        /* background-color: #f8f9fa; */
        padding: 0.25rem;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
    }
.header_top {
    padding-bottom: 2rem !important;
}
}

    
.section-badge {
    margin-bottom: 1rem;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    border-radius: 9999px;
    padding: .375rem 1rem;
    font-size: .875rem;
    line-height: 1.25rem;
    font-weight: 600;
    width: 100%;
    text-align:center;
}
            </style>
        <div class="o_action_manager">
            <div class="o_home_menu h-100 overflow-auto">
                <div class="container-fluid">

                <!-- Skeleton Loader Section -->
                <div id="skeleton-loader" class="skeleton-section-wrapper show">
                    <div class="skeleton-container w-100 mt-5 mx-0">
                        <!-- Skeleton Section 1 -->
                        <div class="skeleton-section">
                            <div class="skeleton-badge"></div>
                            <div class="d-flex flex-wrap gap-2 w-100">
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                            </div>
                        </div>

                        
                        <div class="skeleton-section">
                            <div class="skeleton-badge"></div>
                            <div class="d-flex flex-wrap gap-2 w-100">
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                            </div>
                        </div>

                     
                        <div class="skeleton-section">
                            <div class="skeleton-badge"></div>
                            <div class="d-flex flex-wrap gap-2 w-100">
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                            </div>
                        </div>

                       
                        <div class="skeleton-section">
                            <div class="skeleton-badge"></div>
                            <div class="d-flex flex-wrap gap-2 w-100">
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                            </div>
                        </div>

                        
                        <div class="skeleton-section">
                            <div class="skeleton-badge"></div>
                            <div class="d-flex flex-wrap gap-2 w-100">
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                            </div>
                        </div>

                        
                        <div class="skeleton-section">
                            <div class="skeleton-badge"></div>
                            <div class="d-flex flex-wrap gap-2 w-100">
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                            </div>
                        </div>

                        
                        <div class="skeleton-section">
                            <div class="skeleton-badge"></div>
                            <div class="d-flex flex-wrap gap-2 w-100">
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                                <div class="skeleton-app-box"></div>
                            </div>
                        </div>
                    </div>
                </div>

<div class="o_apps_grid user-select-none w-100 mt-5 mx-0" role="listbox" id="actual-content">
@if(
    auth()->user()?->can('repair.dashboard') ||
    auth()->user()?->can('contacts.view') ||
    auth()->user()?->can('crm.dashboard') ||
    auth()->user()?->can('time-management.dashboard') ||
    auth()->user()?->can('vin.dashboard') ||
    auth()->user()?->can('checkcar.inspections.index')
)
        <!-- Column 1: Productivity -->
        <div class="w-100 ">
            <div class="section-badge" style="background: linear-gradient(135deg, rgba(165, 66, 215, 0.15) 0%, rgba(165, 66, 215, 0.05) 100%); color: rgb(165, 66, 215); border: 1px solid rgba(165, 66, 215, 0.2);"><div class="w-2 h-2 rounded-full animate-pulse" style="background: rgb(165, 66, 215);"></div> @lang('home.Productivity')</div>
            <div class="w-100  d-flex flex-wrap  gap-2">
                @if (auth()->user()?->can('calendar.view'))
            <div class="o_draggable mb-3 p-1 Productivity-app-box app-box"> 
                <a href="{{ route('calendar') }}" class="o_app o_menuitem app-menu-item " role="option">
                    <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/2.png') }}" alt=""> -->
                     <i class="fas fa-chart-line fa-2x" style="color: rgb(165, 66, 215);"></i>

                    <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Calendar')</div>
                </a>
            </div>
            @endif
            @if (auth()->user()?->can('bookings.view'))
            <div class="o_draggable mb-3 Productivity-app-box app-box">
                <a href="bookings" class="o_app o_menuitem app-menu-item " role="option">
                    <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/3.png') }}" alt=""> -->
                     <i class="fas fa-user-clock fa-2x" style="color: rgb(165, 66, 215);"></i>

                    <div class="o_caption w-100 text-center text-truncate mt-2 home-reservations">@lang('home.Reservation')</div>
                </a>
            </div>
            @endif
            @if (auth()->user()?->can('essentials.todo'))
            <div class="o_draggable mb-3 Productivity-app-box app-box">
                <a href="essentials/todo" class="o_app o_menuitem app-menu-item " role="option">
                    <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/4.png') }}" alt=""> -->
                     <i class="fas fa-tasks fa-2x" style="color: rgb(165, 66, 215);"></i>

                    <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.To-Do')</div>
                </a>
            </div>
            @endif
            </div>
            
        </div>
@endif


        <!-- Column 2: Operations -->
@if(
    auth()->user()?->can('repair.dashboard') ||
    auth()->user()?->can('contacts.view') ||
    auth()->user()?->can('crm.dashboard') ||
    auth()->user()?->can('time-management.dashboard') ||
    auth()->user()?->can('vin.dashboard') ||
    auth()->user()?->can('checkcar.inspections.index')
)
<div class="w-100 home-section">
     <div class="section-badge" style="background: linear-gradient(135deg, rgba(11, 100, 244, 0.15) 0%, rgba(11, 100, 244, 0.05) 100%); color: rgb(11, 100, 244); border: 1px solid rgba(11, 100, 244, 0.2);"><div class="w-2 h-2 rounded-full animate-pulse" style="background: rgb(11, 100, 244);"></div>@lang('home.Operations')</div>
    <div class="w-100 d-flex flex-wrap gap-2">
        @if (auth()->user()?->can('repair.dashboard'))
        <div class="o_draggable mb-3 Operations-app-box app-box">
            <a href="repair/dashboard" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/22.png') }}" alt=""> -->
                 <i class="fas fa-tools fa-2x text-primary"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Job Order')</div>
            </a>
        </div>
        @endif

        @if (auth()->user()?->can('contacts.view'))
        <div class="o_draggable mb-3 Operations-app-box app-box">
            <a href="{{ route('contacts.dashboard') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/6.png') }}" alt=""> -->
                 <i class="fas fa-users fa-2x text-primary"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Contacts')</div>
            </a>
        </div>
        @endif

        <!-- @if (auth()->user()?->can('crm.dashboard'))
        <div class="o_draggable mb-3 Operations-app-box app-box">
            <a href="crm/dashboard" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                 <i class="fas fa-handshake fa-2x text-primary"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.CRM')</div>
            </a>
        </div>
        @endif -->
          @if (auth()->user()?->can('time-management.dashboard'))
        <div class="o_draggable mb-3 Operations-app-box app-box">
            <a href="{{ route('timemanagement.dashboard') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/TimeMangment.png') }}" alt=""> -->
                                 <i class="fas fa-clock fa-2x text-primary"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Time Management')</div>
            </a>
        </div>
        @endif
           @if (auth()->user()?->can('vin.dashboard'))
        <div class="o_draggable mb-3 Operations-app-box app-box">
            <a href="{{ url('/vin/dashboard') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/27.jpg') }}" alt=""> -->
                 <i class="fas fa-car fa-2x text-primary"></i>
                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Vin')</div>
            </a>
        </div>
        @endif
        @if (auth()->user()?->can('checkcar.inspections.index'))
        <div class="o_draggable mb-3 Operations-app-box app-box">
            <a href="{{ url('/checkcar/inspections') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/car_inspection.jpg') }}" alt=""> -->
                 <i class="fas fa-car-side fa-2x text-primary"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('checkcar::lang.menu_check_car')</div>
            </a>
        </div>
        @endif
    </div>
</div>
@endif

        <!-- Column 3: Sales -->
@if(
    auth()->user()?->can('sell.dashboard') ||
    auth()->user()?->can('treasury.dashboard') ||
    auth()->user()?->can('dashboards.dashboard') ||
    auth()->user()?->can('point-of-sale.dashboard')
)
  <div class="w-100 home-section">
    <div class="section-badge" style="background: linear-gradient(135deg, rgba(34, 195, 93, 0.15) 0%, rgba(34, 195, 93, 0.05) 100%); color: rgb(34, 195, 93); border: 1px solid rgba(34, 195, 93, 0.2);">
        <div class="w-2 h-2 rounded-full animate-pulse" style="background: rgb(34, 195, 93);">

        </div>@lang('home.Sales')
    </div>

    <div class="w-100 d-flex flex-wrap gap-2">
        @if (auth()->user()?->can('sell.dashboard'))
        <div class="o_draggable mb-3 Sales-app-box app-box">
            <a href="{{ route('sells.dashboard') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/8.png') }}" alt=""> -->
                 <i class="fas fa-chart-line fa-2x" style="color:rgb(34, 195, 93);"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Sales')</div>
            </a>
        </div>
        @endif
  @if (auth()->user()?->can('treasury.dashboard'))
        <div class="o_draggable mb-3 Sales-app-box app-box">
            <a href="{{ url('/treasury') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/26.png') }}" alt=""> -->
                    <i class="fas fa-university fa-2x " style="color:rgb(34, 195, 93);"></i>
                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.treasury')</div>
            </a>
        </div>
        @endif
        @if (auth()->user()?->can('dashboards.dashboard'))
        <div class="o_draggable mb-3 Sales-app-box app-box">
            <a href="{{ route('dashboard_item', ['id' => app()->getLocale() == 'ar' ? 'Dashboards' : 'Dashboards']) }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/9.png') }}" alt=""> -->
                 <i class="fas fa-tachometer-alt fa-2x" style="color:rgb(34, 195, 93);"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Dashboards')</div>
            </a>
        </div>
        @endif
               <!-- @if (auth()->user()?->can('carmarket.index') || auth()->user()?->can('admin'))
                    <div class="o_draggable mb-3 Sales-app-box app-box">
                        <a href="{{ url('/carmarket') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                            <i class="fas fa-car fa-2x" style="color:rgb(34, 195, 93);"></i>
                            <div class="o_caption w-100 text-center text-truncate mt-2">@lang('carmarket::lang.module_title')</div>
                        </a>
                    </div>
                @endif -->

        @if (auth()->user()?->can('point-of-sale.dashboard'))
        <div class="o_draggable mb-3 Sales-app-box app-box">
            <a href="{{ url('/point_of_sale/dashboard') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/10.png') }}" alt=""> -->
                 <i class="fas fa-cash-register fa-2x " style="color:rgb(34, 195, 93);"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Point of Sale')</div>
            </a>
        </div>
        @endif
    </div>
</div>
@endif

        <!-- Column 4: Finance -->
@if(
    auth()->user()?->can('payment-accounts.dashboard') ||
    auth()->user()?->can('project.dashboard') ||
    auth()->user()?->can('purchases.dashboard')
)
       <div class="w-100 home-section">
 <div class="section-badge" style="background: linear-gradient(135deg, rgba(231, 176, 8, 0.15) 0%, rgba(231, 176, 8, 0.05) 100%); color: rgb(231, 176, 8); border: 1px solid rgba(231, 176, 8, 0.2);">
    <div class="w-2 h-2 rounded-full animate-pulse" style="background: rgb(231, 176, 8);">

    </div>@lang('home.Finance')</div>

    <div class="w-100 d-flex flex-wrap gap-2">
        @if (auth()->user()?->can('payment-accounts.dashboard'))
        <div class="o_draggable mb-3 Finance-app-box app-box">
            <a href="{{ url('/account/dashboard') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/11.png') }}" alt=""> -->
                <!-- <i class="fas fa-money-bill-wave fa-2x text-primary"></i> -->
                 <i class="fas fa-calculator fa-2x" style="color: rgb(231, 176, 8);"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Accounting')</div>
            </a>
        </div>
        @endif

        @if (auth()->user()?->can('project.dashboard'))
        <div class="o_draggable mb-3 Finance-app-box app-box">
            <a href="project/project?project_view=list_view" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/12.png') }}" alt=""> -->
                 <i class="fas fa-folder-open fa-2x" style="color: rgb(231, 176, 8);"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Project')</div>
            </a>
        </div>
        @endif

        @if (auth()->user()?->can('purchases.dashboard'))
        <div class="o_draggable mb-3 Finance-app-box app-box">
            <a href="{{ route('purchases.dashboard') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/15.png') }}" alt=""> -->
                    <i class="fas fa-shopping-cart fa-2x " style="color: rgb(231, 176, 8);"></i>
                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Purchase')</div>
            </a>
        </div>
        @endif
        <!-- @if (auth()->user()?->can('purchases.expenses')) -->
        <div class="o_draggable mb-3 Finance-app-box app-box">
            <a href="{{ url('/expenses') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/15.png') }}" alt=""> -->
                 <i class="fas fa-money-bill-wave fa-2x" style="color: rgb(231, 176, 8);"></i>
<div class="o_caption w-100 text-center text-truncate mt-2">
    @lang('home.expenses')
</div>
            </a>
        </div>
        <!-- @endif -->


    </div>
</div>
@endif


        <!-- Column 5: Inventory & Documents -->
@if(
    auth()->user()?->can('products.dashboard') ||
    auth()->user()?->can('barcode.dashboard') ||
    auth()->user()?->can('spreadsheet.sheets') ||
    auth()->user()?->can('surveys.dashboard')
)
       <div class="w-100 home-section">
   <div class="section-badge" style="background: linear-gradient(135deg, rgba(226, 54, 112, 0.15) 0%, rgba(226, 54, 112, 0.05) 100%); color: rgb(226, 54, 112); border: 1px solid rgba(226, 54, 112, 0.2);">
    <div class="w-2 h-2 rounded-full animate-pulse" style="background: rgb(226, 54, 112);">

    </div>@lang('home.Inventory')</div>

    <div class="w-100 d-flex flex-wrap gap-2">
        @if (auth()->user()?->can('products.dashboard'))
        <div class="o_draggable mb-3 Inventory-app-box app-box">
            <a href="{{ url('/inventory/dashboard') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/16.png') }}" alt=""> -->
                <i class="fas fa-box fa-2x" style="color: rgb(226, 54, 112);"></i>
                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Inventory')</div>
            </a>
        </div>
        @endif

        @if (auth()->user()?->can('barcode.dashboard'))
        <div class="o_draggable mb-3 Inventory-app-box app-box">
            <a href="barcodes" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/17.png') }}" alt=""> -->
                 <i class="fas fa-barcode fa-2x" style="color: rgb(226, 54, 112);"></i>
                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Barcode')</div>
            </a>
        </div>
        @endif

        @if (auth()->user()?->can('spreadsheet.sheets'))
        <div class="o_draggable mb-3 Inventory-app-box app-box">
            <a href="spreadsheet/sheets" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/23.png') }}" alt=""> -->
                 <i class="fas fa-file-alt fa-2x" style="color: rgb(226, 54, 112);"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Documents')</div>
            </a>
        </div>
        @endif

      
    </div>
</div>
@endif


        <!-- Column 6: Management -->
@if(
    auth()->user()?->can('hrm.dashboard') ||
    auth()->user()?->can('settings.dashboard')
)
        <div class="w-100 home-section">
    <div class="section-badge" style="background: linear-gradient(135deg, rgba(26, 162, 230, 0.15) 0%, rgba(26, 162, 230, 0.05) 100%); color: rgb(26, 162, 230); border: 1px solid rgba(26, 162, 230, 0.2);">
        <div class="w-2 h-2 rounded-full animate-pulse" style="background: rgb(26, 162, 230);">

    </div>@lang('home.Management')</div>

    <div class="w-100 d-flex flex-wrap gap-2">
        @if (auth()->user()?->can('hrm.dashboard'))
        <div class="o_draggable mb-3 Management-app-box app-box">
            <a href="hrm/dashboard" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/19.png') }}" alt=""> -->
                 <i class="fas fa-users fa-2x " style="color: rgb(26, 162, 230);"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Employees')</div>
            </a>
        </div>
        @endif

     

      
    </div>
</div>
@endif


        <!-- Column 7: Tools -->
@if(
    auth()->user()?->can('reports.dashboard') ||
    auth()->user()?->can('sms.dashboard') ||
    auth()->user()?->can('artificial-intelligence.dashboard')
)
<div class="w-100 home-section">
<div class="section-badge" style="background: linear-gradient(135deg, rgba(244, 123, 37, 0.15) 0%, rgba(244, 123, 37, 0.05) 100%); color: rgb(244, 123, 37); border: 1px solid rgba(244, 123, 37, 0.2);">
    <div class="w-2 h-2 rounded-full animate-pulse" style="background: rgb(244, 123, 37);">

    </div>@lang('home.Tools')</div>

    <div class="w-100 d-flex flex-wrap gap-2">
        @if (auth()->user()?->can('reports.dashboard'))
        <div class="o_draggable mb-3 Tools-app-box app-box">
            <a href="{{ route('reports.index') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/reports.png') }}" alt=""> -->
                 <i class="fas fa-chart-bar fa-2x " style="color: rgb(244, 123, 37);"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.reports')</div>
            </a>
        </div>
        @endif

        @if (auth()->user()?->can('sms.dashboard'))
        <div class="o_draggable mb-3 Tools-app-box app-box">
            <a href="{{ url('/sms/messages/dashboard') }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/sms.png') }}" alt=""> -->
<i class="fas fa-sms fa-2x " style="color: rgb(244, 123, 37);"></i>
                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.SMS')</div>
            </a>
        </div>
        @endif

        @if (auth()->user()?->can('artificial-intelligence.dashboard'))
        <div class="o_draggable mb-3 Tools-app-box app-box">
            <a href="{{ route('dashboard_item', ['id' => app()->getLocale() == 'ar' ? 'الذكاء الاصطناعي' : 'Artificial Intelligence']) }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/25.png') }}" alt=""> -->
                <!-- <i class="fas fa-brain fa-2x text-primary"></i> -->
                 <i class="fas fa-robot fa-2x " style="color: rgb(244, 123, 37);"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Ai')</div>
            </a>
        </div>
        @endif
   @if (auth()->user()?->can('settings.dashboard'))
        <div class="o_draggable mb-3 Tools-app-box app-box">
            <a href="{{ route('dashboard_item', ['id' => app()->getLocale() == 'ar' ? 'إعدادات' : 'Settings']) }}" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/21.png') }}" alt=""> -->
                 <i class="fas fa-cog fa-2x" style="color: rgb(244, 123, 37);"></i>
                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Settings')</div>
            </a>
        </div>
        @endif
        
    </div>
</div>
@endif

<!-- Column 8: Extras -->
<div class="w-100 home-section">
    <div class="section-badge" style="background: linear-gradient(135deg, rgba(128, 0, 128, 0.15) 0%, rgba(128, 0, 128, 0.05) 100%); color: rgb(128, 0, 128); border: 1px solid rgba(128, 0, 128, 0.2);">
        <div class="w-2 h-2 rounded-full animate-pulse" style="background: rgb(128, 0, 128);"></div>@lang('home.markting')
    </div>

    <div class="w-100 d-flex flex-wrap gap-2">
          @if (auth()->user()?->can('surveys.dashboard'))
        <div class="o_draggable mb-3 Inventory-app-box app-box">
            <a href="survey/dashboard" class="o_app o_menuitem app-menu-item d-flex flex-column rounded-3 justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <!-- <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/14.png') }}" alt=""> -->
                 <i class="fas fa-clipboard-list fa-2x" style="color: rgb(128,0,128);"></i>
                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Surveys')</div>
            </a>
        </div>
        @endif

        <div class="o_draggable mb-3 Inventory-app-box app-box">
            <a href="{{ url('/crm/dashboard') }}" class="o_app o_menuitem app-menu-item d-flex flex-column justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <i class="fas fa-handshake fa-2x" style="color: rgb(128,0,128);"></i>
                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.CRM')</div>
            </a>
        </div>

        <!-- <div class="o_draggable mb-3 Tools-app-box app-box">
            <a href="{{ url('/about-us') }}" class="o_app o_menuitem app-menu-item d-flex flex-column justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <i class="fas fa-info-circle fa-2x" style="color: rgb(128,0,128);"></i>
                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.AboutUs')</div>
            </a>
        </div> -->

        <!-- <div class="o_draggable mb-3 Tools-app-box app-box">
            <a href="{{ url('/blogs') }}" class="o_app o_menuitem app-menu-item d-flex flex-column justify-content-center align-items-center w-100 p-1 p-md-2" role="option">
                <i class="fas fa-blog fa-2x" style="color: rgb(128,0,128);"></i>
                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('blog.blogs')</div>
            </a>
        </div> -->
         <div class="o_draggable mb-3 Inventory-app-box app-box">

            <a href="{{ url('/about-settings') }}" class="o_app o_menuitem app-menu-item d-flex flex-column justify-content-center align-items-center w-100 p-1 p-md-2" role="option">

          <i class="fas fa-th-large fa-2x" style="color: rgb(128,0,128);"></i>

                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.AppSettings')</div>

            </a>

        </div>
    </div>
</div>

    </div>
</div>

            </div>
        </div>
    </div>
    </div>
@endsection

<style>
    

</style>


@section('javascript')
    <script src="{{ asset('js/home.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script>
        window.addEventListener("load", function() {
    document.getElementById("homepage-blur").classList.add("loaded");

    // Hide skeleton loader and show actual content
    const skeletonLoader = document.getElementById("skeleton-loader");
    if (skeletonLoader) {
        skeletonLoader.classList.add("hidden");
    }

    // وبعد كده تشغل الـ animation بتاع home-section
    const sections = document.querySelectorAll(".home-section");
    sections.forEach((section, index) => {
        setTimeout(() => {
            section.classList.add("show");
        }, index * 150); 
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const sections = document.querySelectorAll(".home-section");

    sections.forEach((section, index) => {
        setTimeout(() => {
            section.classList.add("show");
        }, index * 150); 
    });
});
</script>
    @includeIf('sales_order.common_js')
    @includeIf('purchase_order.common_js')
    @if (!empty($all_locations))
        {!! $sells_chart_1->script() !!}
        {!! $sells_chart_2->script() !!}
    @endif
    <script type="text/javascript">
        $(document).ready(function() {
            sales_order_table = $('#sales_order_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                scrollY: '75vh',
                scrollX: true,
                scrollCollapse: true,
                aaSorting: [
                    [1, 'desc'],
                ],
                'ajax': {
                    'url': '{{ action([\App\Http\Controllers\SellController::class, 'index']) }}?sale_type=sales_order',
                    'data': function(d) {
                        d.for_dashboard_sales_order = true;

                        if ($('#so_location').length > 0) {
                            d.location_id = $('#so_location').val();
                        }
                    },
                },
                columnDefs: [{
                    'targets': 7,
                    'orderable': false,
                    'searchable': false,
                }],
                columns: [{
                    data: 'action',
                    name: 'action',
                },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date',
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no',
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name',
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile',
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name',
                    },
                    {
                        data: 'status',
                        name: 'status',
                    },
                    {
                        data: 'shipping_status',
                        name: 'shipping_status',
                    },
                    {
                        data: 'so_qty_remaining',
                        name: 'so_qty_remaining',
                        'searchable': false,
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name',
                    },
                ],
            });

            @if (auth()->user()->can('account.access') && config('constants.show_payments_recovered_today') == true)

            // Cash Flow Table
            cash_flow_table = $('#cash_flow_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                'ajax': {
                    'url': "{{ action([\App\Http\Controllers\AccountController::class, 'cashFlow']) }}",
                    'data': function(d) {
                        d.type = 'credit';
                        d.only_payment_recovered = true;
                    },
                },
                'ordering': false,
                'searching': false,
                columns: [{
                    data: 'operation_date',
                    name: 'operation_date',
                },
                    {
                        data: 'account_name',
                        name: 'account_name',
                    },
                    {
                        data: 'sub_type',
                        name: 'sub_type',
                    },
                    {
                        data: 'method',
                        name: 'TP.method',
                    },
                    {
                        data: 'payment_details',
                        name: 'payment_details',
                        searchable: false,
                    },
                    {
                        data: 'credit',
                        name: 'amount',
                    },
                    {
                        data: 'balance',
                        name: 'balance',
                    },
                    {
                        data: 'total_balance',
                        name: 'total_balance',
                    },
                ],
                'fnDrawCallback': function(oSettings) {
                    __currency_convert_recursively($('#cash_flow_table'));
                },
                'footerCallback': function(row, data, start, end, display) {
                    var footer_total_credit = 0;

                    for (var r in data) {
                        footer_total_credit += $(data[r].credit).data('orig-value') ? parseFloat($(
                            data[r].credit).data('orig-value')) : 0;
                    }
                    $('.footer_total_credit').html(__currency_trans_from_en(footer_total_credit));
                },
            });
            @endif

            $('#so_location').change(function() {
                sales_order_table.ajax.reload();
            });
            @if (!empty($common_settings['enable_purchase_order']))
            //Purchase table
            purchase_order_table = $('#purchase_order_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                aaSorting: [
                    [1, 'desc'],
                ],
                scrollY: '75vh',
                scrollX: true,
                scrollCollapse: true,
                ajax: {
                    url: '{{ action([\App\Http\Controllers\PurchaseOrderController::class, 'index']) }}',
                    data: function(d) {
                        d.from_dashboard = true;

                        if ($('#po_location').length > 0) {
                            d.location_id = $('#po_location').val();
                        }
                    },
                },
                columns: [{
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date',
                    },
                    {
                        data: 'ref_no',
                        name: 'ref_no',
                    },
                    {
                        data: 'location_name',
                        name: 'BS.name',
                    },
                    {
                        data: 'name',
                        name: 'contacts.name',
                    },
                    {
                        data: 'status',
                        name: 'transactions.status',
                    },
                    {
                        data: 'po_qty_remaining',
                        name: 'po_qty_remaining',
                        'searchable': false,
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name',
                    },
                ],
            });

            $('#po_location').change(function() {
                purchase_order_table.ajax.reload();
            });
            @endif

            @if (!empty($common_settings['enable_purchase_requisition']))
            //Purchase table
            purchase_requisition_table = $('#purchase_requisition_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                aaSorting: [
                    [1, 'desc'],
                ],
                scrollY: '75vh',
                scrollX: true,
                scrollCollapse: true,
                ajax: {
                    url: '{{ action([\App\Http\Controllers\PurchaseRequisitionController::class, 'index']) }}',
                    data: function(d) {
                        d.from_dashboard = true;

                        if ($('#pr_location').length > 0) {
                            d.location_id = $('#pr_location').val();
                        }
                    },
                },
                columns: [{
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date',
                    },
                    {
                        data: 'ref_no',
                        name: 'ref_no',
                    },
                    {
                        data: 'location_name',
                        name: 'BS.name',
                    },
                    {
                        data: 'status',
                        name: 'status',
                    },
                    {
                        data: 'delivery_date',
                        name: 'delivery_date',
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name',
                    },
                ],
            });

            $('#pr_location').change(function() {
                purchase_requisition_table.ajax.reload();
            });

            $(document).on('click', 'a.delete-purchase-requisition', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    purchase_requisition_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            });
            @endif

                sell_table = $('#shipments_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                aaSorting: [
                    [1, 'desc'],
                ],
                scrollY: '75vh',
                scrollX: true,
                scrollCollapse: true,
                'ajax': {
                    'url': '{{ action([\App\Http\Controllers\SellController::class, 'index']) }}',
                    'data': function(d) {
                        d.only_pending_shipments = true;
                        if ($('#pending_shipments_location').length > 0) {
                            d.location_id = $('#pending_shipments_location').val();
                        }
                    },
                },
                columns: [{
                    data: 'action',
                    name: 'action',
                    searchable: false,
                    orderable: false,
                },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date',
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no',
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name',
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile',
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name',
                    },
                    {
                        data: 'shipping_status',
                        name: 'shipping_status',
                    },
                        @if (!empty($custom_labels['shipping']['custom_field_1']))
                    {
                        data: 'shipping_custom_field_1',
                        name: 'shipping_custom_field_1',
                    },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_2']))
                    {
                        data: 'shipping_custom_field_2',
                        name: 'shipping_custom_field_2',
                    },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_3']))
                    {
                        data: 'shipping_custom_field_3',
                        name: 'shipping_custom_field_3',
                    },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_4']))
                    {
                        data: 'shipping_custom_field_4',
                        name: 'shipping_custom_field_4',
                    },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_5']))
                    {
                        data: 'shipping_custom_field_5',
                        name: 'shipping_custom_field_5',
                    },
                        @endif {
                        data: 'payment_status',
                        name: 'payment_status',
                    },
                    {
                        data: 'waiter',
                        name: 'ss.first_name',
                        @if (empty($is_service_staff_enabled))
                        visible: false
                        @endif
                    },
                ],
                'fnDrawCallback': function(oSettings) {
                    __currency_convert_recursively($('#sell_table'));
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td:eq(4)').attr('class', 'clickable_td');
                },
            });

            $('#pending_shipments_location').change(function() {
                sell_table.ajax.reload();
            });
        });



</script>
    </script>

@endsection
