
@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">📝 @lang('artificialintelligence::lang.edit_diagnose_message')</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('diagnose-message.update') }}" method="POST">
        @csrf
        <input type="hidden" name="message" id="hidden-message">

        <div class="form-group">
            <label for="editor">✏️ @lang('artificialintelligence::lang.message'):</label>
            <div class="editor-container">
                <textarea id="editor" name="editor" class="form-control">{{ old('message', $message) }}</textarea>
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex align-items-center mb-2">
                <span class="mr-2">🎨 @lang('artificialintelligence::lang.icons'):</span>
                <div class="d-flex flex-wrap gap-2">
                    @foreach(['🔥', '🚗', '⚙️', '💡', '😃', '👍', '🌟', '❤️', '🛠️', '⚠️', '✅', '❌'] as $emoji)
                        <button type="button" class="btn btn-outline-secondary btn-sm emoji-btn" data-emoji="{{ $emoji }}">
                            {{ $emoji }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="d-flex align-items-center">
                <span class="mr-2">📌 @lang('artificialintelligence::lang.variables'):</span>
                <div class="d-flex flex-wrap gap-2">
                    @foreach(['{car_model}', '{car_brand}', '{manufacturing_year}', '{km}', '{obd_codes}', '{booking_notes}'] as $var)
                        <button type="button" class="btn btn-info btn-sm var-btn" data-var="{{ $var }}">
                            {{ $var }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#recommendedModal">
            📋 @lang('artificialintelligence::lang.recommended_message')
        </button>

        <button type="submit" class="btn btn-primary px-4">
            <i class="fas fa-save me-2"></i>@lang('artificialintelligence::lang.save')
        </button>
    </form>
</div>

<!-- Recommended Message Modal -->
<div class="modal fade" id="recommendedModal" tabindex="-1" role="dialog" aria-labelledby="recommendedModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recommendedModalLabel">📋 الرسالة الموصى بها</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="recommended-message">
                    <p class="selectable">🔧 <strong>تقرير تشخيص المركبة</strong></p>
                    <p class="selectable">🚗 <strong>طراز السيارة:</strong> {car_model}</p>
                    <p class="selectable">📌 <strong>الشركة المصنعة:</strong> {car_brand}</p>
                    <p class="selectable">📅 <strong>سنة التصنيع:</strong> {manufacturing_year}</p>
                    <p class="selectable">📋 <strong>أكواد الأعطال (OBD):</strong> {obd_codes}</p>
                    <p class="selectable">📝 <strong>ملاحظات إضافية:</strong> {booking_notes}</p>
                    <p class="selectable">📝 <strong>✨💡 مع تزيين الرد بالأيقونات والعناصر:\n"</strong></p>
                    <p class="selectable">📝 <strong>مع مراعاة التنسيق و و الحرص على كتاية قطع الغيار اذا توافرت</strong></p>
                    <p class="selectable">📝 <strong>🛠️ *اقتراحات قطع الغيار:* 🏎️\n</strong></p>

                    <hr>

                    <p class="selectable">⚙️ <strong>تشخيص الحالة:</strong> بعد الفحص، تبين أن المركبة تحتاج إلى التحقق من الأكواد المسجلة واتخاذ الإجراءات المناسبة.</p>
                    <p class="selectable">🛠️ <strong>التوصيات الفنية:</strong></p>
                    <ul>
                        <li class="selectable">🔍 مراجعة نظام {system_name} لضمان كفاءة الأداء.</li>
                        <li class="selectable">🛢️ التحقق من مستوى الزيت واستبداله إذا لزم الأمر.</li>
                        <li class="selectable">⚡ فحص نظام الكهرباء والبطارية لتجنب أي أعطال إضافية.</li>
                        <li class="selectable">📝 ينصح بإجراء اختبار قيادة بعد الصيانة للتأكد من حل المشكلة.</li>
                    </ul>

                    <hr>

                    <p class="selectable">✨💡 <strong>مقترحات إضافية:</strong></p>
                    <p class="selectable">🔹 فحص شامل لنظام الفرامل والتأكد من عدم وجود تسربات.</p>
                    <p class="selectable">🔹 التأكد من صلاحية الإطارات وضغط الهواء المناسب.</p>
                    <p class="selectable">🔹 مراجعة نظام التبريد لتفادي أي مشاكل مستقبلية.</p>

                    <hr>

                    <p class="selectable">🔄 <strong>يرجى التأكيد على الإجراءات المطلوبة قبل بدء العمل.</strong></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmSelection">✅ تأكيد الإضافة</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/35.0.1/classic/ckeditor.js"></script>
<script>
    let editor;

    ClassicEditor
        .create(document.querySelector('#editor'), {
            language: 'ar',
            toolbar: [
                'heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList',
                '|', 'undo', 'redo'
            ],
        })
        .then(newEditor => {
            editor = newEditor;
            setupAutoHeight(editor);
            editor.editing.view.change(writer => {
                writer.setAttribute('dir', 'rtl', editor.editing.view.document.getRoot());
                writer.setStyle('font-family', 'Tajawal, Arial, sans-serif', editor.editing.view.document.getRoot());
                writer.setStyle('font-size', '18px', editor.editing.view.document.getRoot());
                writer.setStyle('line-height', '1.6', editor.editing.view.document.getRoot());
            });
        })
        .catch(console.error);

    function setupAutoHeight(editorInstance) {
        const editable = editorInstance.ui.getEditableElement();
        editable.style.minHeight = '250px';
        editable.style.resize = 'vertical';
        // Ensure RTL for the editable area
        editable.style.direction = 'rtl';
        editable.style.textAlign = 'right';
        editable.style.padding = '15px';
        editable.style.borderRadius = '8px';
        editable.style.boxShadow = 'inset 0 1px 3px rgba(0,0,0,0.1)';
    }

    // Handle emoji buttons
    document.querySelectorAll('.emoji-btn').forEach(btn => {
        btn.addEventListener('click', () => insertText(btn.dataset.emoji));
        // Add hover effect
        btn.addEventListener('mouseover', function() {
            this.style.transform = 'scale(1.1)';
        });
        btn.addEventListener('mouseout', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Handle variable buttons
    document.querySelectorAll('.var-btn').forEach(btn => {
        btn.addEventListener('click', () => toggleVariable(btn.dataset.var));
        // Add hover effect
        btn.addEventListener('mouseover', function() {
            this.style.transform = 'scale(1.05)';
        });
        btn.addEventListener('mouseout', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Handle selectable text in recommended section
    document.querySelectorAll('.selectable').forEach(item => {
        item.addEventListener('click', function () {
            this.classList.toggle('selected');
        });
    });

    // Confirm selection and add selected lines to the editor once
    document.getElementById('confirmSelection').addEventListener('click', () => {
        let selectedTexts = Array.from(document.querySelectorAll('.selectable.selected'))
            .map(el => el.textContent.trim())
            .join('\n');

        if (selectedTexts) {
            insertText(selectedTexts);
        }

        document.querySelectorAll('.selectable.selected').forEach(el => el.classList.remove('selected')); // Reset selection
        $('#recommendedModal').modal('hide'); // Close modal
    });

    function toggleVariable(content) {
        const text = editor.getData();
        if (text.includes(content)) {
            editor.setData(text.replace(content, ''));
        } else {
            insertText(content);
        }
    }

    function insertText(content) {
        editor.model.change(writer => {
            const position = editor.model.document.selection.getFirstPosition();
            writer.insertText(content + ' ', position); // Changed from '\n' to ' ' for better flow
        });
    }

    document.querySelector('form').addEventListener('submit', () => {
        document.getElementById('hidden-message').value = editor.getData();
    });
</script>

<style>
    /* General styling improvements */
    body {
        font-family: 'Tajawal', Arial, sans-serif;
    }

    /* Editor container styling */
    .editor-container {
        margin-bottom: 20px;
        direction: rtl;
    }

    .tw-h-screen {
    height: 150vh !important;
}
    /* CKEditor styling */
    .ck-editor__editable {
        font-family: 'Tajawal', Arial, sans-serif !important;
        min-height: 250px !important;
    }

    .ck.ck-editor__main>.ck-editor__editable {
        background-color: #fcfcfc !important;
    }

    /* Button styling */
    .emoji-btn, .var-btn {
        transition: all 0.2s ease;
        margin: 3px;
        font-size: 16px;
    }

    .emoji-btn {
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .var-btn {
        font-family: 'Courier New', monospace;
        font-weight: bold;
    }

    /* Modal styling for Arabic text */
    #recommended-message {
        direction: rtl;
        text-align: right;
        font-family: 'Tajawal', Arial, sans-serif;
        line-height: 1.6;
    }

    .selectable {
        cursor: pointer;
        padding: 8px;
        transition: background 0.3s ease;
        border-radius: 5px;
        margin: 5px 0;
    }

    .selected {
        background-color: #d3f9d8;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* Form styling */
    .form-group label {
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 8px;
        display: block;
    }

    /* Add Tajawal font */
    @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');

    /* Headings sizing inside editor */
    .ck-editor__editable h1 { font-size: 28px; font-weight: 700; }
    .ck-editor__editable h2 { font-size: 24px; font-weight: 700; }
    .ck-editor__editable h3 { font-size: 20px; font-weight: 700; }
</style>
@endsection
