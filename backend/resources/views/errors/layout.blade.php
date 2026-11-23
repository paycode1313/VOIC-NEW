{{--
    Error pages in VOIC's own voice (Indonesian), instead of Laravel's English defaults.
    Self-contained on purpose: inline styles, no @vite, no database, no auth, so the page still shows when the
    error came from the build, the database or the session. A booth kiosk sends "Beranda" back to the welcome screen.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>@yield('title') · VOIC</title>
    <link rel="icon" type="image/svg+xml" href="/icon.svg">
    <style>
        *, ::before, ::after { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            font-family: 'Figtree', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: #0f172a;
            background:
                radial-gradient(34rem 34rem at -6rem -6rem, rgb(165 180 252 / 0.35), transparent 70%),
                radial-gradient(28rem 28rem at 105% 35%, rgb(186 230 253 / 0.45), transparent 70%),
                radial-gradient(30rem 30rem at 40% 110%, rgb(253 230 138 / 0.35), transparent 70%),
                #f6f6f3;
        }
        .card {
            width: 100%;
            max-width: 30rem;
            padding: 36px 32px 32px;
            border-radius: 28px;
            background: rgb(255 255 255 / 0.85);
            box-shadow: 0 30px 60px -30px rgb(15 23 42 / 0.3), 0 0 0 1px rgb(15 23 42 / 0.06);
            text-align: center;
        }
        .logo { height: 28px; width: auto; }
        .code {
            display: inline-block;
            margin-top: 28px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.08em;
            background: var(--mark, #fde68a);
            transform: rotate(-3deg);
            box-shadow: 0 6px 14px -8px rgb(15 23 42 / 0.45);
        }
        h1 {
            margin: 18px 0 0;
            font-size: 28px;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        p { margin: 12px 0 0; font-size: 15px; line-height: 1.6; color: #475569; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 26px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 22px;
            border-radius: 999px;
            border: 1px solid rgb(15 23 42 / 0.1);
            font: inherit;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            background: #fff;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }
        .btn:hover { transform: translateY(-1px); background: #f8fafc; }
        .btn-primary { border-color: transparent; color: #fff; background: #0f172a; }
        .btn-primary:hover { background: #1e293b; }
        .foot { margin-top: 22px; font-size: 13px; color: #94a3b8; }
        @media (prefers-reduced-motion: reduce) { .btn { transition: none; } .btn:hover { transform: none; } }
    </style>
</head>
<body>
    <main class="card">
        <a href="/" data-kiosk-href="/sambut" aria-label="VOIC"><img src="/images/logo/voic-logo-horizontal.svg" alt="VOIC" class="logo" width="99" height="28"></a>
        <div><span class="code" style="--mark: @yield('mark', '#fde68a')">@yield('code')</span></div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <a href="/" data-kiosk-href="/sambut" class="btn btn-primary">Ke beranda</a>
                <a href="/latihan" class="btn">Mulai latihan</a>
            @endif
        </div>
        @hasSection('foot')
            <p class="foot">@yield('foot')</p>
        @endif
    </main>
    <script>
        // Booth kiosk (set by the welcome screen): "home" is the welcome screen, not the public home page.
        try {
            if (sessionStorage.getItem('voic_kiosk') === '1') {
                document.querySelectorAll('[data-kiosk-href]').forEach(function (a) { a.href = a.dataset.kioskHref; });
            }
        } catch (e) {}
    </script>
</body>
</html>
