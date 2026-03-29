
<div class="pos-tab-content">
    <style>
        .qr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
        .qr-card { border: 2px solid #e5e7eb; border-radius: 12px; background: #fff; box-shadow: 0 6px 18px rgba(0,0,0,0.08); overflow: hidden; cursor: pointer; transition: transform .08s ease, box-shadow .2s ease; }
        .qr-card:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(0,0,0,0.12); }
        .qr-card .header { padding: 10px 14px; font-weight: 600; font-size: 14px; letter-spacing: .2px; }
        .qr-card .content { padding: 14px; }
        .qr-card .barcode { display: flex; align-items: center; justify-content: center; background: #f9fafb; border-radius: 8px; padding: 12px; }
        .qr-card .payload { margin-top: 10px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; line-height: 1.6; background: #111827; color: #e5e7eb; padding: 10px; border-radius: 6px; word-break: break-all; }
        /* themed variants */
        .qr-card.localhost { border-color: #2563eb; }
        .qr-card.localhost .header { color: #1d4ed8; background: #eff6ff; }
        .qr-card.workshop { border-color: #059669; }
        .qr-card.workshop .header { color: #047857; background: #ecfdf5; }
        .qr-card.pos { border-color: #7c3aed; }
        .qr-card.pos .header { color: #6d28d9; background: #f5f3ff; }

        /* Modal styling */
        .qr-modal-backdrop { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(17,24,39,0.6); display: flex; align-items: center; justify-content: center; z-index: 100000; opacity: 0; pointer-events: none; transition: opacity 140ms ease-out; will-change: opacity; }
        .qr-modal-backdrop.is-open { opacity: 1; pointer-events: auto; }
        .qr-modal { background: #fff; border-radius: 14px; width: min(92vw, 780px); max-height: 92vh; overflow: hidden; box-shadow: 0 18px 38px rgba(0,0,0,0.22); }
        .qr-modal .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; }
        .qr-modal .modal-title { font-weight: 700; font-size: 15px; }
        .qr-modal .modal-close { background: transparent; border: none; font-size: 22px; line-height: 1; cursor: pointer; color: #6b7280; }
        .qr-modal .modal-body { padding: 18px; }
        .qr-modal .barcode { display: flex; align-items: center; justify-content: center; background: #f9fafb; border-radius: 10px; padding: 18px; }
        .qr-modal .barcode img, .qr-modal .barcode svg, .qr-modal .barcode canvas { width: min(80vw, 560px); height: auto; }
        .qr-modal .payload { margin-top: 12px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; line-height: 1.7; background: #111827; color: #e5e7eb; padding: 12px; border-radius: 8px; word-break: break-all; }
        /* lock scroll when open */
        body.qr-modal-open { overflow: hidden !important; }
    </style>

    <?php 
    use Milon\Barcode\Facades\DNS2DFacade;
    use Illuminate\Support\Facades\DB;

    $urls = DB::table('oauth_clients')->select('id', 'secret', 'redirect')->get();
    // Read persisted values from business common_settings
    $workshop_app_domain = '';
    $workshop_APP_API_TOKEN = '';
    $customer_app_domain = '';
    $workshop_business_scope = 'car';
    if (isset($common_settings) && is_array($common_settings)) {
        $workshop_app_domain = $common_settings['workshop_app_domain'] ?? '';
        $workshop_APP_API_TOKEN = $common_settings['workshop_app_api_token'] ?? '';
        $customer_app_domain = $common_settings['customer_app_domain'] ?? '';
        $workshop_business_scope = $common_settings['workshop_business_scope'] ?? 'car';
    }
    // // Fallbacks to preserve current behavior if not yet saved
    // if (empty($workshop_app_domain)) {
    //     $workshop_app_domain = 'workshop.carserv.pro';
    // }
    // if (empty($workshop_APP_API_TOKEN)) {
    //     $workshop_APP_API_TOKEN = '73c8145bc005372bde33e7d4af0f03176f7044c6794277d3cbf39480a04d0aae';
    // }

    $index = 0;
    ?>

    <div class="row" style="margin-bottom: 12px;">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('common_settings[workshop_app_domain]', 'Workshop App Domain:') !!}
                {!! Form::text('common_settings[workshop_app_domain]', old('common_settings.workshop_app_domain', $workshop_app_domain), ['class' => 'form-control', 'placeholder' => 'e.g. workshop.carserv.pro']) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('common_settings[workshop_app_api_token]', 'Workshop App API Token:') !!}
                {!! Form::text('common_settings[workshop_app_api_token]', old('common_settings.workshop_app_api_token', $workshop_APP_API_TOKEN), ['class' => 'form-control', 'placeholder' => 'Paste API token']) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('common_settings[customer_app_domain]', 'Customer App Domain:') !!}
                {!! Form::text('common_settings[customer_app_domain]', old('common_settings.customer_app_domain', $customer_app_domain), ['class' => 'form-control', 'placeholder' => 'e.g. https://carserv.com']) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('common_settings[workshop_business_scope]', 'Business Scope:') !!}
                {!! Form::select('common_settings[workshop_business_scope]', ['car' => 'Car', 'motorcycle' => 'Motorcycle'], old('common_settings.workshop_business_scope', $workshop_business_scope), ['class' => 'form-control', 'id' => 'workshop_business_scope']) !!}
            </div>
        </div>
    </div>

    <div class="qr-grid">
    <?php 
        // Prepare base URLs for non-localhost (Workshop/POS) and localhost
        $remoteBase = null;
        $localhostBase = null;
        foreach ($urls as $url) {
            $base = $url->redirect . '#' . $url->id . '#' . $url->secret;
            if (strpos($url->redirect, 'localhost') !== false) {
                if ($localhostBase === null) {
                    $localhostBase = $base;
                }
            } else {
                if ($remoteBase === null) {
                    $remoteBase = $base;
                }
            }
        }

        // QR with domain + token + scope (Workshop)
        $qrWorkshop = $workshop_app_domain . '#' . $workshop_APP_API_TOKEN . '#' . $workshop_business_scope;
        $index = 0;

        // 1) Workshop QR: non-localhost redirect + qrWorkshop
        if ($remoteBase !== null) {
            $index++;
            $qr = $remoteBase . '#' . $qrWorkshop;
            $primaryClass = 'workshop';
            $primaryTitle = 'Workshop QR';
        ?>
        <div class="qr-card <?= $primaryClass ?>" data-title="<?= $primaryTitle ?>" data-payload="<?= htmlspecialchars($qr, ENT_QUOTES, 'UTF-8') ?>">
            <div class="header"><?= $primaryTitle ?></div>
            <div class="content">
                <div class="barcode">{!! DNS2DFacade::getBarcodeHTML($qr, 'QRCODE') !!}</div>
                <div class="payload"><?= $qr ?></div>
            </div>
        </div>
        <?php }

        // 2) POS QR: same non-localhost redirect but WITHOUT domain & token (POS)
        // After the 2nd item, add a third one WITHOUT domain & token (POS)
        if ($remoteBase !== null) {
            $index++;
            $qrWithoutWorkshop = $remoteBase;
        ?>
        <div class="qr-card pos" data-title="POS QR" data-payload="<?= htmlspecialchars($qrWithoutWorkshop, ENT_QUOTES, 'UTF-8') ?>">
            <div class="header">POS QR</div>
            <div class="content">
                <div class="barcode">{!! DNS2DFacade::getBarcodeHTML($qrWithoutWorkshop, 'QRCODE') !!}</div>
                <div class="payload"><?= $qrWithoutWorkshop ?></div>
            </div>
        </div>
        <?php }

        // 3) Localhost QR: localhost redirect + qrWorkshop
        if ($localhostBase !== null) {
            $index++;
            $qrLocalhost = $localhostBase . '#' . $qrWorkshop;
            $primaryClass = 'localhost';
            $primaryTitle = 'Localhost QR';
        ?>
        <div class="qr-card <?= $primaryClass ?>" data-title="<?= $primaryTitle ?>" data-payload="<?= htmlspecialchars($qrLocalhost, ENT_QUOTES, 'UTF-8') ?>">
            <div class="header"><?= $primaryTitle ?></div>
            <div class="content">
                <div class="barcode">{!! DNS2DFacade::getBarcodeHTML($qrLocalhost, 'QRCODE') !!}</div>
                <div class="payload"><?= $qrLocalhost ?></div>
            </div>
        </div>
        <?php }
    ?>
    </div>

    <!-- Modal -->
    <div class="qr-modal-backdrop" id="qrModal">
        <div class="qr-modal" role="dialog" aria-modal="true" aria-labelledby="qrModalTitle">
            <div class="modal-header">
                <div class="modal-title" id="qrModalTitle">QR</div>
                <button class="modal-close" id="qrModalClose" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="barcode" id="qrModalBarcode"></div>
                <div class="payload" id="qrModalPayload"></div>
            </div>
        </div>
    </div>

    <script>
        (function(){
            var backdrop = document.getElementById('qrModal');
            // Ensure backdrop is attached to body to avoid transformed ancestors affecting position
            try { if (backdrop && backdrop.parentNode !== document.body) { document.body.appendChild(backdrop); } } catch (e) {}

            function openModal(title, barcodeHTML, payload){
                document.getElementById('qrModalTitle').textContent = title || 'QR';
                document.getElementById('qrModalBarcode').innerHTML = barcodeHTML || '';
                document.getElementById('qrModalPayload').textContent = payload || '';
                backdrop.classList.add('is-open');
                document.body.classList.add('qr-modal-open');
            }
            function closeModal(){
                backdrop.classList.remove('is-open');
                document.body.classList.remove('qr-modal-open');
            }
            document.getElementById('qrModalClose').addEventListener('click', closeModal);
            document.getElementById('qrModal').addEventListener('click', function(e){
                if (e.target === e.currentTarget) closeModal();
            });
            document.addEventListener('keydown', function(e){
                if (e.key === 'Escape') closeModal();
            });

            var cards = document.querySelectorAll('.qr-card');
            cards.forEach(function(card){
                card.addEventListener('click', function(){
                    var title = card.getAttribute('data-title');
                    var payload = card.getAttribute('data-payload');
                    var barcodeHTML = card.querySelector('.barcode').innerHTML;
                    openModal(title, barcodeHTML, payload);
                });
            });

            // Update QR codes when business scope changes
            var scopeSelect = document.getElementById('workshop_business_scope');
            if (scopeSelect) {
                scopeSelect.addEventListener('change', function(){
                    var newScope = this.value;
                    var workshopDomain = document.querySelector('input[name="common_settings[workshop_app_domain]"]').value;
                    var apiToken = document.querySelector('input[name="common_settings[workshop_app_api_token]"]').value;
                    
                    // Update QR payloads with new scope
                    cards.forEach(function(card){
                        var payload = card.getAttribute('data-payload');
                        var parts = payload.split('#');
                        
                        // Reconstruct payload with new scope
                        if (parts.length >= 3) {
                            // Workshop QR: redirect#id#secret#domain#token#scope
                            var newPayload = parts.slice(0, 3).join('#') + '#' + workshopDomain + '#' + apiToken + '#' + newScope;
                            card.setAttribute('data-payload', newPayload);
                            card.querySelector('.payload').textContent = newPayload;
                            
                            // Regenerate QR code
                            var barcodeDiv = card.querySelector('.barcode');
                            if (barcodeDiv && typeof qrcode !== 'undefined') {
                                barcodeDiv.innerHTML = '';
                                new QRCode(barcodeDiv, {
                                    text: newPayload,
                                    width: 150,
                                    height: 150
                                });
                            }
                        }
                    });
                });
            }
        })();
    </script>
</div>
