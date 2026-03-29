<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <title>استبيان</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: linear-gradient(135deg, #f8fafc 0%, #eef2ff 45%, #e0f2fe 100%);
            --card: rgba(255, 255, 255, 0.9);
            --glass: rgba(255, 255, 255, 0.95);
            --accent: #0ea5e9;
            --accent-2: #10b981;
            --text: #0f172a;
            --muted: #475569;
            --radius: 16px;
            --shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
            --chip-bg: rgba(15, 23, 42, 0.08);
            --badge-bg: rgba(16, 185, 129, 0.15);
            --badge-border: rgba(16, 185, 129, 0.35);
            --option-bg: rgba(15, 23, 42, 0.05);
            --option-border: rgba(15, 23, 42, 0.06);
            --input-bg: rgba(255, 255, 255, 0.95);
        }

        body.theme-dark {
            --bg: linear-gradient(135deg, #0f172a 0%, #111827 45%, #0b2637 100%);
            --card: rgba(255, 255, 255, 0.04);
            --glass: rgba(255, 255, 255, 0.08);
            --accent: #10b981;
            --accent-2: #0ea5e9;
            --text: #e5e7eb;
            --muted: #9ca3af;
            --shadow: 0 18px 38px rgba(0, 0, 0, 0.35);
            --chip-bg: rgba(255, 255, 255, 0.08);
            --badge-bg: rgba(16, 185, 129, 0.18);
            --badge-border: rgba(16, 185, 129, 0.35);
            --option-bg: rgba(255, 255, 255, 0.03);
            --option-border: rgba(255, 255, 255, 0.05);
            --input-bg: rgba(255, 255, 255, 0.06);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', 'Poppins', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            position: relative;
            overflow-x: hidden;
            width: 100%;
        }

        html, body {
            max-width: 100vw;
            overflow-x: hidden;
        }

        .page-shell {
            position: relative;
            z-index: 1;
            padding: 32px 18px 42px;
            width: 100%;
            box-sizing: border-box;
        }

        .hero {
            max-width: 1080px;
            margin: 0 auto 22px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            color: var(--text);
        }

        .hero-card {
            background: var(--card);
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: var(--radius);
            padding: 24px 22px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .brand-mark {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            width: 220px;
            height: 120px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-title {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--muted);
        }

        .brand-survey {
            margin: 4px 0;
            font-size: clamp(20px, 3vw, 30px);
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .brand-desc {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
        }


        .form-shell {
            max-width: 1080px;
            margin: 0 auto;
            background: var(--glass);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 22px 22px 26px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .question-card {
            background: var(--card);
            border-radius: 14px;
            padding: 16px 16px 14px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border-color: rgba(16, 185, 129, 0.35);
        }

        .question-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 8px;
            color: var(--text);
        }

        .helper {
            color: var(--muted);
            font-size: 13px;
            margin: 0 0 8px;
        }

        .input-control, .option-control {
            width: 100%;
            padding: 12px 12px;
            border-radius: 10px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: var(--input-bg);
            color: #000;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .input-control:focus {
            border-color: rgba(14, 165, 233, 0.35);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.16);
            background: rgba(255, 255, 255, 0.98);
        }

        .option-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--option-bg);
            border: 1px solid var(--option-border);
            margin-bottom: 8px;
        }

        .option-row input {
            accent-color: var(--accent);
            transform: scale(1.08);
            cursor: pointer;
        }

        .option-label {
            margin: 0;
            color: var(--text);
            font-size: 14px;
        }

        .star-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
        }

        .star-rating .fa-star {
            font-size: 26px;
            color: #e2e8f0;
            cursor: pointer;
            transition: transform 0.15s ease, color 0.15s ease;
            text-shadow: 0 0 0 rgba(0,0,0,0);
        }

        .star-rating .fa-star.checked {
            color: #fbbf24;
            text-shadow: 0 6px 16px rgba(251, 191, 36, 0.35);
        }

        .star-rating .fa-star:hover {
            transform: translateY(-2px) scale(1.05);
        }

        .submit-btn, .btn-success {
            width: 100%;
            border: none;
            padding: 14px;
            border-radius: 12px;
            background: #198754;
            color: #fff;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 0.01em;
            cursor: pointer;
            box-shadow: 0 14px 32px rgba(14, 165, 233, 0.25);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .submit-btn:hover, .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 38px rgba(14, 165, 233, 0.32);
        }

        .submit-btn:active, .btn-success:active {
            transform: translateY(0);
        }

        .divider {
            margin: 6px 0 10px;
            height: 1px;
            background: linear-gradient(90deg, rgba(15,23,42,0), rgba(15,23,42,0.08), rgba(15,23,42,0));
            border: 0;
        }

        .tagline {
            font-size: 13px;
            color: var(--muted);
            margin-top: 6px;
        }

        .question-index {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.06);
            color: var(--text);
            font-weight: 700;
            margin-left: 10px;
        }

        @media (max-width: 640px) {
            .page-shell { padding: 12px 8px 24px; }
            .hero { margin: 0 auto 14px; }
            .hero-card { padding: 16px 14px; }
            .form-shell { padding: 12px; }
            .question-card { padding: 12px; }
            .brand-mark {
                flex-direction: column;
                text-align: center;
                gap: 10px;
                width: 100%;
            }
            .brand-logo {
                width: 200px;
                height: 80px;
            }
            .brand-survey {
                font-size: 18px;
            }
            .submit-btn, .btn-success {
                font-size: 15px;
                padding: 12px;
            }
        }
    </style>
</head>

<body class="{{ (optional($surveySettings)->active_theme ?? 'light') === 'dark' ? 'theme-dark' : '' }}">
    <div class="page-shell">
        <div class="hero">
            <div class="hero-card">
                <div class="brand-mark">
                    <div class="brand-logo">
                        @if(!empty(optional($business)->logo))
                            <img src="{{ asset('uploads/business_logos/' . $business->logo) }}" alt="Logo">
                        @else
                            <i class="fa-solid fa-screwdriver-wrench" style="font-size:48px;color:var(--muted)"></i>
                        @endif
                    </div>
                    <div>
                        <div class="brand-survey">{{ $survey->title }}</div>
                        <p class="brand-desc">{{ $survey->description }}</p>
                        <div class="tagline">ملاحظاتك تساعدنا على تحسين خدماتنا.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-shell">
            <hr class="divider">

            <form action="{{ route('survey.index') }}" method="GET">
                <input type="hidden" name="surveyId" id="surveyId" value="{{ $survey->id }}">

                @foreach($questions as $idx => $q)
                    <div class="question-card">
                        <div class="d-flex align-items-center mb-2">
                            <div class="question-index">{{ $idx + 1 }}</div>
                            <div>
                                <div class="question-title">{{ $q->text }}</div>
                                @if($q->type_id == 4)
                                    <div class="helper">اضغط لتقييم بالنجوم</div>
                                @elseif($q->type_id == 2)
                                    <div class="helper">اختر خيار واحد</div>
                                @else
                                    <div class="helper">أدخل إجابتك</div>
                                @endif
                            </div>
                        </div>

                        @if(is_null($q->description))
                            <input type="text" class="input-control" id="question_{{ $q->id }}" name="answers[{{ $q->id }}]" placeholder="اكتب إجابتك" required>

                        @elseif($q->type_id == 2)
                            @php $options = json_decode($q->description, true); @endphp
                            @foreach($options as $index => $option)
                                @php $label = is_array($option) ? ($option['label'] ?? '') : $option; @endphp
                                <label class="option-row" for="question_{{ $q->id }}option{{ $index }}">
                                    <input class="form-check-input" type="radio" name="answers[{{ $q->id }}]" id="question_{{ $q->id }}option{{ $index }}" value="{{ $label }}" required>
                                    <p class="option-label">{{ $label }}</p>
                                </label>
                            @endforeach

                        @elseif($q->type_id == 4)
                            @php $rate = json_decode($q->description, true); @endphp
                            <div class="star-rating" data-question-id="{{ $q->id }}">
                                @for($i = $rate[0]; $i <= $rate[1]; $i++)
                                    <span class="fa fa-star" data-value="{{ $i }}"></span>
                                @endfor
                                <input type="hidden" name="answers[{{ $q->id }}]" id="star-input-{{ $q->id }}" value="">
                            </div>

                        @else
                            @php $options = json_decode($q->description, true); @endphp
                            @foreach($options as $index => $option)
                                @php $label = is_array($option) ? ($option['label'] ?? '') : $option; @endphp
                                <label class="option-row" for="question_{{ $q->id }}option{{ $index }}">
                                    <input class="form-check-input" type="checkbox" name="answers[{{ $q->id }}][]" id="question_{{ $q->id }}option{{ $index }}" value="{{ $label }}">
                                    <p class="option-label">{{ $label }}</p>
                                </label>
                            @endforeach
                        @endif
                    </div>
                @endforeach

                <button type="submit" class="btn-success" readonly>إرسال التقييم</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Star rating
            document.querySelectorAll(".star-rating").forEach(starContainer => {
                const stars = starContainer.querySelectorAll(".fa-star");
                const hiddenInput = starContainer.querySelector("input[type='hidden']");

                const paint = (val) => {
                    stars.forEach((s, i) => {
                        s.classList.toggle("checked", i < val);
                    });
                };

                stars.forEach(star => {
                    star.addEventListener("click", function() {
                        const value = Number(this.getAttribute("data-value"));
                        hiddenInput.value = value;
                        paint(value);
                    });

                    star.addEventListener("mouseover", function() {
                        paint(Number(this.getAttribute("data-value")));
                    });
                });

                starContainer.addEventListener("mouseleave", function() {
                    paint(Number(hiddenInput.value || 0));
                });
            });

        });
    </script>
</body>
</html>
