<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ATHAR — Digital Forensics</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="athar-landing">
        <main>
            <nav class="athar-landing-nav">
                <span class="athar-brand"><span class="athar-brand-mark">A</span><span>ATHAR<small>Digital Forensics</small></span></span>
                <div>
                    @auth
                        <a href="{{ route('dashboard') }}" class="athar-button">الدخول إلى المنصة</a>
                    @else
                        <a href="{{ route('login') }}">تسجيل الدخول</a>
                        <a href="{{ route('register') }}" class="athar-button">إنشاء حساب</a>
                    @endauth
                </div>
            </nav>
            <section class="athar-hero">
                <p class="athar-kicker">TRACEABLE · SECURE · INVESTIGATOR-LED</p>
                <h1>كل أثرٍ رقمي<br>يستحق <em>سياقًا موثوقًا.</em></h1>
                <p>ATHAR منصة لإدارة التحقيقات والأدلة الجنائية الرقمية، تجمع سلامة الأدلة وسلسلة الحيازة ومهام التحليل ونتائج CAINE في مساحة عمل واحدة قابلة للتتبع.</p>
                <div class="athar-hero-actions">
                    @auth
                        <a class="athar-button" href="{{ route('dashboard') }}">فتح لوحة التحقيق</a>
                    @else
                        <a class="athar-button" href="{{ route('login') }}">بدء تحقيق آمن</a>
                    @endauth
                    <a class="athar-text-link" href="#controls">استكشف الضوابط ←</a>
                </div>
            </section>
            <section id="controls" class="athar-control-strip">
                <div><strong>SHA-256</strong><span>تحقق من سلامة الأدلة</span></div>
                <div><strong>CoC Ledger</strong><span>سجل حيازة متسلسل</span></div>
                <div><strong>CAINE 14.0</strong><span>تنفيذ أدوات معزول</span></div>
                <div><strong>Human Review</strong><span>المحقق صاحب القرار</span></div>
            </section>
        </main>
    </body>
</html>
