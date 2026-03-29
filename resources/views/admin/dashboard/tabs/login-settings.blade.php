@php
  $ls_bg = '#f4f5f7';
  $ls_surface = '#ffffff';
  $ls_border = '#e2e4e8';
  $ls_accent = '#5b4fc9';
  $ls_accent2 = '#7c6bff';
  $ls_text = '#1a1d24';
  $ls_muted = '#5f6369';
@endphp
<style>
.login-settings .section-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em; color: {{ $ls_accent }}; text-transform: uppercase; margin-bottom: 1rem; }
.login-settings .upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
.login-settings .upload-box {
  position: relative; height: 160px; border: 1.5px dashed rgba(91,79,201,0.35); border-radius: 18px; padding: 1rem;
  transition: 0.3s; overflow: hidden; background-size: cover; background-position: center; background-repeat: no-repeat;
  display: flex; align-items: center; justify-content: center; cursor: pointer; background-color: #f8f9fa;
}
.login-settings .upload-box.has-image::before { content: ""; position: absolute; inset: 0; background: rgba(0,0,0,0.45); }
.login-settings .upload-box.theme-box { min-height: 140px; }
.login-settings .upload-box.theme-box.selected { border-color: {{ $ls_accent }}; box-shadow: 0 0 0 2px rgba(91,79,201,0.3); }
.login-settings .upload-box:hover { border-color: {{ $ls_accent }}; transform: translateY(-2px); }
.login-settings .upload-label { position: relative; z-index: 2; width: 100%; height: 100%; cursor: pointer; }
.login-settings .upload-label input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.login-settings .upload-content { text-align: center; color: {{ $ls_text }}; }
.login-settings .upload-title { font-weight: 700; font-size: 0.9rem; }
.login-settings .upload-sub { font-size: 0.75rem; color: {{ $ls_muted }}; }
.login-settings .color-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
.login-settings .color-field {
  border: 1px solid {{ $ls_border }}; border-radius: 16px; padding: 1rem; display: flex; align-items: center; gap: 1rem;
  cursor: pointer; transition: 0.3s; background: #fff;
}
.login-settings .color-field:hover { border-color: rgba(91,79,201,0.4); background: rgba(91,79,201,0.04); }
.login-settings .color-swatch-wrap { position: relative; width: 44px; height: 44px; flex-shrink: 0; }
.login-settings .color-swatch-wrap input[type="color"] { position: absolute; inset: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
.login-settings .color-swatch-circle { width: 44px; height: 44px; border-radius: 50%; border: 3px solid {{ $ls_border }}; pointer-events: none; }
.login-settings .color-info label { font-size: 0.82rem; font-weight: 600; color: {{ $ls_text }}; display: block; margin-bottom: 2px; }
.login-settings .color-hex { font-size: 0.75rem; color: {{ $ls_muted }}; font-family: monospace; }
.login-settings .text-input {
  width: 100%; border: 1px solid {{ $ls_border }}; border-radius: 14px; padding: 0.8rem 1.1rem;
  font-size: 0.95rem; background: {{ $ls_surface }}; color: {{ $ls_text }};
}
.login-settings .text-input:focus { outline: none; border-color: {{ $ls_accent }}; box-shadow: 0 0 0 3px rgba(91,79,201,0.15); }
.login-settings .save-btn {
  background: linear-gradient(135deg, {{ $ls_accent }}, {{ $ls_accent2 }}); color: #fff; border: none; border-radius: 14px;
  padding: 0.85rem 2rem; font-weight: 700; cursor: pointer; transition: 0.3s;
}
.login-settings .save-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(91,79,201,0.35); }
@media (max-width: 580px) { .login-settings .upload-grid, .login-settings .color-grid { grid-template-columns: 1fr; } }
</style>

<form action="{{ route('admin.dashboard.save') }}" method="POST" enctype="multipart/form-data" class="login-settings">
  @csrf

  <div class="section mb-4">
    <div class="section-label">ثيم الصفحة (Canvas Theme)</div>
    <div class="upload-grid">
      <div class="upload-box theme-box {{ ($settings['login_canvas'] ?? '') === 'SoftAuroraLines' ? 'selected' : '' }}" data-theme="SoftAuroraLines" style="display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;">
        @include('Animations.SoftAuroraLines')
      </div>
      <div class="upload-box theme-box {{ ($settings['login_canvas'] ?? '') === 'SoftParticleGlow' ? 'selected' : '' }}" data-theme="SoftParticleGlow" style="display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;">
        @include('Animations.SoftParticleGlow')
      </div>
      <div class="upload-box theme-box {{ ($settings['login_canvas'] ?? '') === 'FloatingGradientWaves' ? 'selected' : '' }}" data-theme="FloatingGradientWaves" style="display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;">
        @include('Animations.FloatingGradientWaves')
      </div>
    </div>
    <input type="hidden" name="login_canvas" id="loginCanvasInput" value="{{ $settings['login_canvas'] ?? '' }}">
  </div>

  <div class="section mb-4">
    <div class="section-label">الصور</div>
    <div class="upload-grid">
      <div class="upload-box {{ !empty($settings['login_image']) ? 'has-image' : '' }}" @if(!empty($settings['login_image'])) style="background-image:url('{{ asset('storage/'.$settings['login_image']) }}')" @endif>
        <label class="upload-label">
          <input type="file" name="login_image" accept="image/*">
          <div class="upload-content">
            @if(empty($settings['login_image']))<span class="upload-icon">🖼️</span>@endif
            <div><div class="upload-title">صورة الخلفية</div><div class="upload-sub">Background Image</div></div>
          </div>
        </label>
      </div>
      <div class="upload-box {{ !empty($settings['login_logo']) ? 'has-image' : '' }}" @if(!empty($settings['login_logo'])) style="background-image:url('{{ asset('storage/'.$settings['login_logo']) }}')" @endif>
        <label class="upload-label">
          <input type="file" name="login_logo" accept="image/*">
          <div class="upload-content">
            @if(empty($settings['login_logo']))<span class="upload-icon">✨</span>@endif
            <div><div class="upload-title">شعار الموقع</div><div class="upload-sub">Logo Image</div></div>
          </div>
        </label>
      </div>
      <div class="upload-box {{ !empty($settings['carserv_logo']) ? 'has-image' : '' }}" @if(!empty($settings['carserv_logo'])) style="background-image:url('{{ asset('storage/'.$settings['carserv_logo']) }}')" @endif>
        <label class="upload-label">
          <input type="file" name="carserv_logo" accept="image/*">
          <div class="upload-content">
            @if(empty($settings['carserv_logo']))<span class="upload-icon">✨</span>@endif
            <div><div class="upload-title">شعار Car Service Pro</div><div class="upload-sub">Car Service Pro Logo</div></div>
          </div>
        </label>
      </div>
    </div>
  </div>

  <div class="section mb-4">
    <div class="section-label">الألوان</div>
    <div class="color-grid">
      <div class="color-field" onclick="this.querySelector('input').click()">
        <div class="color-swatch-wrap">
          <input type="color" name="login_bg_color" value="{{ $settings['login_bg_color'] ?? '#f8f9fa' }}" id="bgColor" oninput="document.getElementById('bgCircle').style.background=this.value;document.getElementById('bgHex').textContent=this.value">
          <div class="color-swatch-circle" id="bgCircle" style="background:{{ $settings['login_bg_color'] ?? '#f8f9fa' }}"></div>
        </div>
        <div class="color-info">
          <label>لون خلفية الصفحة</label>
          <div class="color-hex" id="bgHex">{{ $settings['login_bg_color'] ?? '#f8f9fa' }}</div>
        </div>
      </div>
      <div class="color-field" onclick="this.querySelector('input').click()">
        <div class="color-swatch-wrap">
          <input type="color" name="login_button_color" value="{{ $settings['login_button_color'] ?? '#0d6efd' }}" id="btnColor" oninput="document.getElementById('btnCircle').style.background=this.value;document.getElementById('btnHex').textContent=this.value">
          <div class="color-swatch-circle" id="btnCircle" style="background:{{ $settings['login_button_color'] ?? '#0d6efd' }}"></div>
        </div>
        <div class="color-info">
          <label>لون الأزرار</label>
          <div class="color-hex" id="btnHex">{{ $settings['login_button_color'] ?? '#0d6efd' }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="section mb-4">
    <div class="section-label">النصوص</div>
    <div class="mb-3">
      <label class="form-label" style="margin-bottom:8px;color:{{ $ls_muted }};">العنوان الرئيسي (Title)</label>
      <input type="text" name="login_title" class="text-input" value="{{ $settings['login_title'] ?? '' }}" placeholder="Login page title">
    </div>
    <div class="mb-3">
      <label class="form-label" style="margin-bottom:8px;color:{{ $ls_muted }};">العنوان الفرعي (Subtitle)</label>
      <input type="text" name="login_subtitle" class="text-input" value="{{ $settings['login_subtitle'] ?? '' }}" placeholder="Login page subtitle">
    </div>
  </div>

  <div class="mt-4">
    <button type="submit" class="save-btn"><i class="fa-solid fa-floppy-disk me-2"></i>Save Login Settings</button>
  </div>
</form>

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
  const themeBoxes = document.querySelectorAll('.login-settings .theme-box');
  const input = document.getElementById('loginCanvasInput');
  if (themeBoxes.length && input) {
    themeBoxes.forEach(box => {
      box.addEventListener('click', function() {
        themeBoxes.forEach(b => b.classList.remove('selected'));
        box.classList.add('selected');
        input.value = box.dataset.theme;
      });
    });
  }
  document.querySelectorAll('.login-settings .upload-box input[type="file"]').forEach(inp => {
    inp.addEventListener("change", function() {
      const box = this.closest(".upload-box");
      if (this.files && this.files[0]) {
        const r = new FileReader();
        r.onload = e => { box.style.backgroundImage = "url('" + e.target.result + "')"; box.classList.add("has-image"); };
        r.readAsDataURL(this.files[0]);
      }
    });
  });
});
</script>
@endpush
