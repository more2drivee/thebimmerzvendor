<form action="{{ route('admin.qr.save') }}" method="POST" class="qr-settings-form">
    @csrf

    <div class="qr-section mb-4">
        <div class="qr-section-label"><i class="fa-solid fa-qrcode me-2"></i>QR Code Settings</div>
        <div class="qr-grid">
            <div class="qr-field">
                {!! Form::label('qrcodesettings[workshop_app_domain]', 'Workshop App Domain:') !!}
                {!! Form::text(
                    'qrcodesettings[workshop_app_domain]',
                    old('qrcodesettings.workshop_app_domain', $qrcodesettings['workshop_app_domain'] ?? ''),
                    ['placeholder' => 'e.g. workshop.carserv.pro', 'class' => 'qr-input']
                ) !!}
            </div>
            <div class="qr-field">
                {!! Form::label('qrcodesettings[workshop_app_api_token]', 'Workshop App API Token:') !!}
                {!! Form::text(
                    'qrcodesettings[workshop_app_api_token]',
                    old('qrcodesettings.workshop_app_api_token', $qrcodesettings['workshop_app_api_token'] ?? ''),
                    ['placeholder' => 'Paste API token', 'class' => 'qr-input']
                ) !!}
            </div>
            <div class="qr-field">
                {!! Form::label('qrcodesettings[customer_app_domain]', 'Customer App Domain:') !!}
                {!! Form::text(
                    'qrcodesettings[customer_app_domain]',
                    old('qrcodesettings.customer_app_domain', $qrcodesettings['customer_app_domain'] ?? ''),
                    ['placeholder' => 'e.g. https://carserv.com', 'class' => 'qr-input']
                ) !!}
            </div>
            <div class="qr-field">
                {!! Form::label('qrcodesettings[workshop_business_scope]', 'Business Scope:') !!}
                {!! Form::select(
                    'qrcodesettings[workshop_business_scope]',
                    ['car' => 'Car', 'motorcycle' => 'Motorcycle'],
                    old('qrcodesettings.workshop_business_scope', $qrcodesettings['workshop_business_scope'] ?? 'car'),
                    ['class' => 'qr-input']
                ) !!}
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="qr-save-btn"><i class="fa-solid fa-floppy-disk me-2"></i>Save QR Settings</button>
    </div>
</form>

<style>
.qr-settings-form .qr-section-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em; color: #5b4fc9; text-transform: uppercase; margin-bottom: 1rem; }
.qr-settings-form .qr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; }
.qr-settings-form .qr-field label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; color: #1a1d24; }
.qr-settings-form .qr-input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e4e8; border-radius: 12px; font-size: 0.95rem; background: #fff; color: #1a1d24; transition: 0.3s; }
.qr-settings-form .qr-input:focus { outline: none; border-color: #5b4fc9; box-shadow: 0 0 0 3px rgba(91,79,201,0.15); }
.qr-settings-form .qr-save-btn { background: linear-gradient(135deg, #5b4fc9, #7c6bff); color: #fff; border: none; border-radius: 14px; padding: 0.85rem 2rem; font-weight: 700; cursor: pointer; transition: 0.3s; }
.qr-settings-form .qr-save-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(91,79,201,0.35); }
</style>
