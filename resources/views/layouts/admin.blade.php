<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        :root {
            --admin-bg: #f4f5f7;
            --admin-surface: #ffffff;
            --admin-card: #ffffff;
            --admin-border: #e2e4e8;
            --admin-accent: #5b4fc9;
            --admin-accent2: #7c6bff;
            --admin-text: #1a1d24;
            --admin-muted: #5f6369;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Cairo', 'Segoe UI', sans-serif;
            background: var(--admin-bg);
            color: var(--admin-text);
            min-height: 100vh;
            margin: 0;
        }
        .admin-header {
            background: var(--admin-surface);
            border-bottom: 1px solid var(--admin-border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .admin-header h1 {
            font-size: 1.4rem;
            font-weight: 800;
            margin: 0;
            color: var(--admin-text);
        }
        .admin-header h1 i { color: var(--admin-accent); }
        .admin-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .admin-nav a {
            color: var(--admin-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .admin-nav a:hover {
            color: var(--admin-accent);
            background: rgba(91,79,201,0.08);
        }
        .admin-nav a.active {
            color: #fff;
            background: linear-gradient(135deg, var(--admin-accent), var(--admin-accent2));
        }
        .admin-main {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .admin-card {
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .flash-success {
            background: #e8f5e9;
            border: 1px solid #a5d6a7;
            color: #2e7d32;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
    </style>
    @stack('styles')
</head>
<body>
    <header class="admin-header">
        <h1><i class="fa-solid fa-gauge-high me-2"></i>Admin Panel</h1>
        <nav class="admin-nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-sliders me-1"></i> Dashboard
            </a>
            <a href="{{ route('logout.get') }}">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
            </a>
        </nav>
    </header>

    <main class="admin-main">
        @if(session('success'))
            <div class="flash-success">
                <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="{{ asset('js/app.js') }}"></script> -->
    @stack('scripts')
</body>
</html>
