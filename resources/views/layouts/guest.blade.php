<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>ATHAR — الوصول الآمن</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="athar-auth-body antialiased">
        <main class="athar-auth-shell">
            <section class="athar-auth-intro" aria-label="تعريف المنصة">
                <a href="{{ url('/') }}" class="athar-brand">
                    <span class="athar-brand-mark">A</span>
                    <span>ATHAR<small>Digital Forensics</small></span>
                </a>
                <div>
                    <p class="athar-kicker">ACCESS CONTROL · EVIDENCE FIRST</p>
                    <h1>الوصول إلى مساحة<br>تحقيق <em>موثوقة.</em></h1>
                    <p>دخول محكوم بالصلاحيات إلى القضايا والأدلة وسجل الحيازة، مع بقاء قرار التحليل والمراجعة بيد الفريق المختص.</p>
                </div>
                <div class="athar-auth-control">
                    <span>SHA-256</span><span>Chain of Custody</span><span>Human Review</span>
                </div>
            </section>

            <section class="athar-auth-panel" aria-label="المصادقة">
                <div class="athar-auth-panel-heading">
                    <p class="athar-kicker">AUTHENTICATED ACCESS</p>
                    <h2>تسجيل الدخول</h2>
                    <p>أدخل بيانات الاعتماد المصرح بها للمتابعة.</p>
                </div>
                {{ $slot }}
                <a class="athar-auth-return" href="{{ url('/') }}">العودة إلى ATHAR ←</a>
            </section>
        </main>
    </body>
</html>
