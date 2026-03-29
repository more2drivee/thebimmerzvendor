@extends('layouts.admin')

@section('content')
<div class="admin-card">
    <div class="page-header mb-4">
        <h2 class="mb-1" style="font-size: 1.6rem; font-weight: 800; color: var(--admin-text);">
            <i class="fa-solid fa-sliders me-2" style="color: var(--admin-accent);"></i>
            لوحة تحكم الإدارة
        </h2>
        <p class="mb-0" style="font-size: 0.9rem; color: var(--admin-muted);">إعدادات النظام وصفحة تسجيل الدخول</p>
    </div>

    {{-- Chrome-style Tabs --}}
    <div class="chrome-tabs-container">
        <div class="chrome-tabs" role="tablist">
            <button class="chrome-tab active" data-bs-toggle="tab" data-bs-target="#login-settings" type="button" role="tab" aria-selected="true">
                <span class="chrome-tab-icon"><i class="fa-solid fa-right-to-bracket"></i></span>
                <span class="chrome-tab-title">Login Settings</span>
                <span class="chrome-tab-close"><i class="fa-solid fa-xmark"></i></span>
            </button>
            <button class="chrome-tab" data-bs-toggle="tab" data-bs-target="#users-settings" type="button" role="tab" aria-selected="false">
                <span class="chrome-tab-icon"><i class="fa-solid fa-qrcode"></i></span>
                <span class="chrome-tab-title">QR Settings</span>
                <span class="chrome-tab-close"><i class="fa-solid fa-xmark"></i></span>
            </button>
            <button class="chrome-tab" data-bs-toggle="tab" data-bs-target="#modules-settings" type="button" role="tab" aria-selected="false">
                <span class="chrome-tab-icon"><i class="fa-solid fa-puzzle-piece"></i></span>
                <span class="chrome-tab-title">Modules Settings</span>
                <span class="chrome-tab-close"><i class="fa-solid fa-xmark"></i></span>
            </button>
            <button class="chrome-tab" data-bs-toggle="tab" data-bs-target="#version-settings" type="button" role="tab" aria-selected="false">
                <span class="chrome-tab-icon"><i class="fa-solid fa-code-branch"></i></span>
                <span class="chrome-tab-title">Version Update</span>
                <span class="chrome-tab-close"><i class="fa-solid fa-xmark"></i></span>
            </button>
            <button class="chrome-tab" data-bs-toggle="tab" data-bs-target="#notifications-settings" type="button" role="tab" aria-selected="false">
                <span class="chrome-tab-icon"><i class="fa-solid fa-bell"></i></span>
                <span class="chrome-tab-title">Notifications</span>
                <span class="chrome-tab-close"><i class="fa-solid fa-xmark"></i></span>
            </button>
            <button class="chrome-tab" data-bs-toggle="tab" data-bs-target="#sociallogin-settings" type="button" role="tab" aria-selected="false">
                <span class="chrome-tab-icon"><i class="fa-solid fa-users"></i></span>
                <span class="chrome-tab-title">Social Login</span>
                <span class="chrome-tab-close"><i class="fa-solid fa-xmark"></i></span>
            </button>
            <div class="chrome-tabs-bottom-line"></div>
        </div>
    </div>

    <div class="tab-content chrome-tab-content">
        {{-- Login Settings Tab --}}
        <div class="tab-pane fade show active" id="login-settings" role="tabpanel">
            @include('admin.dashboard.tabs.login-settings', ['settings' => $settings])
        </div>

        {{-- QR Settings Tab --}}
        <div class="tab-pane fade" id="users-settings" role="tabpanel">
            @include('admin.dashboard.settings_scanqrcode', ['common_settings' => $common_settings, 'urls' => $urls])
        </div>

        <div class="tab-pane fade" id="modules-settings" role="tabpanel">
            @include('admin.dashboard.settings_modules', ['modules' => $modules, 'enabled_modules' => $enabled_modules])
        </div>

        <div class="tab-pane fade" id="version-settings" role="tabpanel">
            @include('admin.dashboard.settings_version', ['version_settings' => $version_settings])
        </div>

        <div class="tab-pane fade" id="notifications-settings" role="tabpanel">
            @include('admin.dashboard.settings_notifications', ['notification_settings' => $notification_settings])
        </div>

        <div class="tab-pane fade" id="sociallogin-settings" role="tabpanel">
            @include('admin.dashboard.settings_social_login', ['social_login_settings' => $social_login_settings])
        </div>
    </div>
</div>

<style>
/* Chrome Tabs Container */
.chrome-tabs-container {
    background: #dee1e6;
    border-radius: 12px 12px 0 0;
    padding: 10px 10px 0 10px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.chrome-tabs {
    display: flex;
    position: relative;
    gap: 2px;
}

/* Individual Tab */
.chrome-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    background: transparent;
    border: none;
    padding: 8px 16px;
    padding-right: 8px;
    border-radius: 10px 10px 0 0;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 120px;
    max-width: 200px;
    position: relative;
    color: #5f6368;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
}

.chrome-tab:hover {
    background: rgba(0, 0, 0, 0.05);
}

.chrome-tab.active {
    background: #ffffff;
    color: #3c4043;
}

/* Tab Icon */
.chrome-tab-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    font-size: 12px;
    flex-shrink: 0;
}

.chrome-tab-icon i {
    color: inherit;
}

/* Tab Title */
.chrome-tab-title {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: left;
}

/* Tab Close Button */
.chrome-tab-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    font-size: 10px;
    opacity: 0;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.chrome-tab-close:hover {
    background: rgba(0, 0, 0, 0.1);
}

.chrome-tab:hover .chrome-tab-close {
    opacity: 0.7;
}

.chrome-tab-close:hover {
    opacity: 1 !important;
    background: rgba(0, 0, 0, 0.15);
}

/* Bottom Line */
.chrome-tabs-bottom-line {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: transparent;
    pointer-events: none;
}

.chrome-tabs-container .tab-pane {
    display: none;
}

.chrome-tabs-container .tab-pane.show {
    display: block;
}

/* Tab Content */
.chrome-tab-content {
    background: #ffffff;
    border-radius: 0 12px 12px 12px;
    padding: 1.5rem;
    border: 1px solid #dee1e6;
    border-top: none;
}

.chrome-tab-content input,
.chrome-tab-content select,
.chrome-tab-content textarea {
    border-radius: 8px !important;
}

/* Responsive */
@media (max-width: 768px) {
    .chrome-tabs-container {
        padding: 8px 8px 0 8px;
        border-radius: 8px 8px 0 0;
    }
    
    .chrome-tab {
        min-width: 100px;
        max-width: 150px;
        padding: 6px 10px;
        padding-right: 6px;
    }
    
    .chrome-tab-title {
        font-size: 12px;
    }
    
    .chrome-tab-icon {
        width: 16px;
        height: 16px;
        font-size: 10px;
    }
    
    .chrome-tab-close {
        width: 16px;
        height: 16px;
        font-size: 9px;
    }
    
    .chrome-tab-content {
        padding: 1rem;
        border-radius: 0 8px 8px 8px;
    }
}

@media (max-width: 576px) {
    .chrome-tabs-container {
        padding: 6px 6px 0 6px;
    }
    
    .chrome-tab {
        min-width: 80px;
        max-width: 120px;
        padding: 5px 8px;
        padding-right: 5px;
        gap: 4px;
    }
    
    .chrome-tab-title {
        font-size: 11px;
    }
    
    .chrome-tab-icon {
        display: none;
    }
    
    .chrome-tab-content {
        padding: 0.75rem;
    }
}
</style>
@endsection
