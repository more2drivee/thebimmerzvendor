<!doctype html>
<html lang="en">

<head>
    <title>Survey</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: linear-gradient(135deg, #f8fafc 0%, #eef2ff 45%, #e0f2fe 100%);
            --card: rgba(255, 255, 255, 0.9);
            --text: #0f172a;
            --muted: #475569;
            --accent: #0ea5e9;
            --accent-2: #10b981;
            --radius: 16px;
            --shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }

        body.theme-dark {
            --bg: linear-gradient(135deg, #0f172a 0%, #111827 45%, #0b2637 100%);
            --card: rgba(255, 255, 255, 0.06);
            --text: #e5e7eb;
            --muted: #9ca3af;
            --shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
        }

        body {
            font-family: 'Poppins', 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .card-shell {
            max-width: 520px;
            width: 100%;
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 32px;
            text-align: center;
        }

        .icon-wrap {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            margin: 0 auto 16px;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(16, 185, 129, 0.2));
            color: var(--accent);
            font-size: 30px;
        }

        .muted {
            color: var(--muted);
        }

        .rate-card {
            margin-top: 24px;
            padding: 18px;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.06);
        }

        body.theme-dark .rate-card {
            background: rgba(255, 255, 255, 0.08);
        }

        .social-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 14px;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            text-decoration: none;
            color: inherit;
            font-weight: 600;
        }

        body.theme-dark .social-links a {
            border-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body class="{{ (optional($surveySettings)->active_theme ?? 'light') === 'dark' ? 'theme-dark' : '' }}">
    <div class="card-shell">
        <div class="icon-wrap"><i class="fa-solid fa-circle-check"></i></div>
        <h2>@lang('survey::lang.thanks_title')</h2>
        <p class="muted">@lang('survey::lang.thanks_subtitle')</p>

        @if($showRatingPrompt ?? false)
            <div class="rate-card">
                <h5>@lang('survey::lang.rate_us_title')</h5>
                <p class="muted">@lang('survey::lang.rate_us_body')</p>
                <div class="social-links">
                    @if(!empty(optional($surveySettings)->google_review_url))
                        <a href="{{ $surveySettings->google_review_url }}" target="_blank" rel="noopener"><i class="fa-brands fa-google"></i> Google</a>
                    @endif
                    @if(!empty(optional($surveySettings)->facebook_url))
                        <a href="{{ $surveySettings->facebook_url }}" target="_blank" rel="noopener"><i class="fa-brands fa-facebook"></i> Facebook</a>
                    @endif
                    @if(!empty(optional($surveySettings)->instagram_url))
                        <a href="{{ $surveySettings->instagram_url }}" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i> Instagram</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</body>
</html>
