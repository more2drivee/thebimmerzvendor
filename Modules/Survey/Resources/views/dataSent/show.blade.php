<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <title>استبيان - الردود</title>
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
            --card: rgba(255, 255, 255, 0.06);
            --glass: rgba(255, 255, 255, 0.08);
            --text: #e5e7eb;
            --muted: #9ca3af;
            --shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
            --chip-bg: rgba(255, 255, 255, 0.08);
            --badge-bg: rgba(16, 185, 129, 0.15);
            --badge-border: rgba(16, 185, 129, 0.35);
            --option-bg: rgba(255, 255, 255, 0.08);
            --option-border: rgba(255, 255, 255, 0.2);
            --input-bg: rgba(255, 255, 255, 0.08);
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

        .tagline {
            font-size: 13px;
            color: var(--muted);
            margin-top: 6px;
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

        .divider {
            margin: 6px 0 10px;
            height: 1px;
            background: linear-gradient(90deg, rgba(15,23,42,0), rgba(15,23,42,0.08), rgba(15,23,42,0));
            border: 0;
        }

        .question-card {
            background: var(--card);
            border-radius: 14px;
            padding: 16px 16px 14px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            position: relative;
            overflow: hidden;
            margin-bottom: 16px;
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

        .input-control {
            width: 100%;
            padding: 12px 12px;
            border-radius: 10px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: var(--input-bg);
            color: var(--text);
            font-size: 14px;
            outline: none;
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

        .option-row.selected {
            background: rgba(34, 197, 94, 0.1);
            border-color: #22c55e;
        }

        .option-row input {
            accent-color: var(--accent);
            transform: scale(1.08);
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
        }

        .star-rating .fa-star.checked {
            color: #fbbf24;
            text-shadow: 0 6px 16px rgba(251, 191, 36, 0.35);
        }

        .like-rating {
            display: flex;
            gap: 12px;
            padding: 10px 12px;
        }

        .like-rating .btn {
            min-width: 120px;
            border-radius: 10px;
            border-width: 1.2px;
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

        /* Make checked checkboxes green */
        .form-check-input:checked {
            background-color: #22c55e;
            border-color: #22c55e;
        }

        .form-check-input:checked:disabled {
            background-color: #22c55e;
            border-color: #22c55e;
            opacity: 1;
        }

        @media (max-width: 640px) {
            .page-shell { padding: 12px 8px 24px; }
            .hero { margin: 0 auto 14px; }
            .hero-card { padding: 16px 14px; }
            .form-shell { padding: 12px; }
            .question-card { padding: 12px; }
            .like-rating { flex-direction: column; }
            .like-rating .btn { width: 100%; }
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
        }
    </style>
</head>

<body class="{{ (optional($surveySettings)->active_theme ?? 'light') === 'dark' ? 'theme-dark' : '' }}">
    <div class="page-shell">
        <div class="hero">
            <div class="hero-card">
                <div class="brand-mark">
                    <div class="brand-logo">
                        <?php if (!empty(optional($business)->logo)) { ?>
                            <img src="<?= asset('uploads/business_logos/' . $business->logo) ?>" alt="Logo">
                        <?php } else { ?>
                            <i class="fa-solid fa-screwdriver-wrench" style="font-size:48px;color:var(--muted)"></i>
                        <?php } ?>
                    </div>
                    <div>
                        <div class="brand-survey"><?= htmlspecialchars($survey->title ?? 'استبيان') ?></div>
                        <p class="brand-desc"><?= htmlspecialchars($survey->description ?? '') ?></p>
                        <div class="tagline">ردود <?= htmlspecialchars($user->name) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-shell">
            <hr class="divider">
            <form>
                <input type="hidden" name="surveyId" id="surveyId" value="<?= $survey->id ?? '' ?>">
                <input type="hidden" name="user_id" id="user_id" value="<?= $user->id ?>">

                <?php foreach ($questions as $idx => $q) { ?>
                <div class="question-card">
                    <div class="d-flex align-items-center mb-2">
                        <div class="question-index"><?= $idx + 1 ?></div>
                        <div class="question-title"><?= htmlspecialchars($q->text) ?></div>
                    </div>
                    <?php $answer = $responses[$q->id] ?? null; ?>

                    <?php if ($q->description === null) { ?>
                    <input type="text" class="input-control"
                        placeholder="إجابة العميل" value="<?= htmlspecialchars((string) $answer) ?>" disabled>
                    <?php } elseif($q->type_id == 2) { ?>
                    <?php $options = json_decode($q->description, true); ?>
                    <?php
                    // Extract the label from JSON response for radio buttons
                    $radioAnswer = $answer;
                    if (!empty($answer)) {
                        // Try to decode as JSON array first
                        $decoded = json_decode($answer, true);
                        if (is_array($decoded) && isset($decoded[0]['label'])) {
                            $radioAnswer = $decoded[0]['label'];
                        } elseif (is_string($answer)) {
                            // Answer is already just the label string
                            $radioAnswer = $answer;
                        }
                    }
                    ?>
                    <?php foreach ($options as $index => $option) { ?>
                    <?php $label = is_array($option) ? ($option['label'] ?? '') : $option; ?>
                    <?php $isChecked = !empty($radioAnswer) && (string) $label === (string) $radioAnswer; ?>
                    <div class="option-row <?= $isChecked ? 'selected' : '' ?>">
                        <input type="radio" name="answers[<?= $q->id ?>]"
                            id="question_<?= $q->id ?>option<?= $index ?>" value="<?= htmlspecialchars($label) ?>"
                            <?= $isChecked ? 'checked' : '' ?> disabled>
                        <label class="option-label" for="question_<?= $q->id ?>option<?= $index ?>">
                            <?= htmlspecialchars($label) ?> </label>
                    </div>
                    <?php } ?>
                    <?php } elseif($q->type_id == 3) { ?>
                    <div class="mt-2">
                        @php
                            $options = json_decode($q->description, true);
                            $selectedOptions = json_decode($responses[$q->id] ?? '[]', true);
                        @endphp
                        @if(is_array($options))
                            @foreach($options as $option)
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" 
                                           @if(is_array($selectedOptions) && in_array($option['label'], $selectedOptions)) 
                                               checked 
                                           @endif
                                           disabled>
                                    <label class="form-check-label">
                                        {{ $option['label'] }}
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <?php } elseif($q->type_id == 4) { ?>
                    <?php $rate = json_decode($q->description, true); ?>
                    <div class="star-rating">
                        <?php for($i = $rate[0]; $i <= $rate[1]; $i++) { ?>
                        <span class="fa fa-star <?= $i <= (int) $answer ? 'checked' : '' ?>" data-value="<?= $i ?>"></span>
                        <?php } ?>
                    </div>
                    <?php } elseif ($q->type_id == 5) { ?>
                    <div class="like-rating">
                        <button type="button" class="btn <?= $answer === 'like' ? 'btn-success' : 'btn-outline-success' ?> like-btn" disabled>
                            <i class="fa fa-thumbs-up"></i> أعجبني
                        </button>
                        <button type="button" class="btn <?= $answer === 'dislike' ? 'btn-danger' : 'btn-outline-danger' ?> dislike-btn" disabled>
                            <i class="fa fa-thumbs-down"></i> لم يعجبني
                        </button>
                    </div>
                    <?php } else { ?>
                    <?php $options = json_decode($q->description, true); ?>
                    <?php $answers = json_decode($answer ?? '[]', true) ?: []; ?>
                    <?php foreach ($options as $index => $option) { ?>
                    <?php $label = is_array($option) ? ($option['label'] ?? '') : $option; ?>
                    <div class="option-row">
                        <input type="checkbox" name="answers[<?= $q->id ?>][]"
                            id="question_<?= $q->id ?>option<?= $index ?>" value="<?= htmlspecialchars($label) ?>"
                            <?= in_array($label, $answers) ? 'checked' : '' ?> disabled>
                        <label class="option-label" for="question_<?= $q->id ?>option<?= $index ?>">
                            <?= htmlspecialchars($label) ?> </label>
                    </div>
                    <?php } ?>
                    <?php } ?>
                </div>
                <?php } ?>
            </form>
        </div>
    </div>

</body>

</html>
