<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Mesale</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ request()->url() }}">
    <style>
        :root {
            color-scheme: light;
            --accent: #ee4d2d;
            --accent-deep: #c7351b;
            --ink: #172033;
            --muted: #5f6878;
            --line: #e8e1da;
            --paper: #fffdf9;
            --wash: #fff2e9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 15% 0%, rgba(238, 77, 45, .13), transparent 34rem),
                linear-gradient(180deg, #fffaf4 0%, #f8f5f1 100%);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.65;
        }
        a { color: var(--accent-deep); }
        .shell { width: min(920px, calc(100% - 32px)); margin: 0 auto; }
        .topbar { padding: 22px 0 14px; }
        .brand { color: var(--ink); font-size: 20px; font-weight: 850; letter-spacing: -.03em; text-decoration: none; }
        .brand span { color: var(--accent); }
        .nav { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .nav a {
            border: 1px solid var(--line);
            border-radius: 999px;
            background: rgba(255,255,255,.72);
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            padding: 7px 12px;
            text-decoration: none;
        }
        .nav a[aria-current="page"] { background: var(--ink); border-color: var(--ink); color: white; }
        main { padding: 20px 0 64px; }
        .hero { margin-bottom: 20px; padding: 34px 34px 28px; border-radius: 22px; background: var(--ink); color: white; box-shadow: 0 24px 70px rgba(42, 32, 25, .14); }
        .eyebrow { color: #ffab91; font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 8px 0 10px; font-size: clamp(30px, 5vw, 48px); line-height: 1.08; letter-spacing: -.045em; }
        .hero p { margin: 0; color: #dce2ed; max-width: 720px; }
        article { border: 1px solid rgba(232, 225, 218, .85); border-radius: 22px; background: rgba(255,253,249,.94); padding: 12px 34px 34px; box-shadow: 0 16px 50px rgba(56, 45, 36, .08); }
        section { padding-top: 26px; }
        section + section { border-top: 1px solid var(--line); margin-top: 24px; }
        h2 { margin: 0 0 12px; font-size: 21px; line-height: 1.3; letter-spacing: -.02em; }
        p { margin: 0 0 12px; color: var(--muted); }
        ul { margin: 0; padding-left: 21px; color: var(--muted); }
        li + li { margin-top: 8px; }
        .contacts { display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-top: 16px; }
        .contact { border: 1px solid var(--line); border-radius: 14px; background: white; padding: 13px 15px; }
        .contact small { display: block; color: var(--muted); font-weight: 700; margin-bottom: 2px; }
        .contact a { font-weight: 750; overflow-wrap: anywhere; }
        .owner-input { border: 1px solid #f0b38f; border-radius: 14px; background: var(--wash); color: #7c301c; font-weight: 700; padding: 14px 16px; }
        footer { color: var(--muted); font-size: 12px; padding: 18px 0 34px; text-align: center; }
        @media (max-width: 620px) {
            .shell { width: min(100% - 20px, 920px); }
            .hero, article { border-radius: 16px; padding-left: 20px; padding-right: 20px; }
            .hero { padding-top: 26px; }
        }
    </style>
</head>
<body>
    <header class="shell topbar">
        <a class="brand" href="{{ route('home') }}">Mê <span>Sale</span></a>
        <nav class="nav" aria-label="Trang pháp lý và hỗ trợ">
            <a href="{{ route('legal.privacy') }}" @if($slug === 'privacy') aria-current="page" @endif>Chính sách bảo mật</a>
            <a href="{{ route('legal.terms') }}" @if($slug === 'terms') aria-current="page" @endif>Điều khoản</a>
            <a href="{{ route('support') }}" @if($slug === 'support') aria-current="page" @endif>Hỗ trợ</a>
            <a href="{{ route('account-deletion') }}" @if($slug === 'account-deletion') aria-current="page" @endif>Xóa tài khoản</a>
        </nav>
    </header>

    <main class="shell">
        <header class="hero">
            <div class="eyebrow">Mesale · Store compliance</div>
            <h1>{{ $title }}</h1>
            <p>{{ $description }}</p>
        </header>

        <article>
            @foreach($sections as $section)
                <section>
                    <h2>{{ $section['heading'] }}</h2>
                    @foreach($section['paragraphs'] ?? [] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                    @if(!empty($section['items']))
                        <ul>
                            @foreach($section['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach

            @if($show_contacts ?? false)
                <section>
                    <h2>Kênh liên hệ chính thức</h2>
                    @if($supportConfigured)
                        <div class="contacts">
                            @foreach($contacts as $contact)
                                @if($contact['value'])
                                    <div class="contact">
                                        <small>{{ $contact['label'] }}</small>
                                        <a href="{{ $contact['href'] }}" @if(str_starts_with((string) $contact['href'], 'http')) target="_blank" rel="noopener noreferrer" @endif>{{ $contact['value'] }}</a>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="owner-input">OWNER INPUT REQUIRED: cấu hình ít nhất một kênh hỗ trợ đã được xác minh trong Admin trước khi phát hành ứng dụng.</div>
                    @endif
                </section>
            @endif
        </article>
    </main>

    <footer class="shell">Cập nhật: 09/08/2026 · Mesale</footer>
</body>
</html>
