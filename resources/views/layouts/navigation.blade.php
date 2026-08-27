<aside class="athar-sidebar">
    <a href="{{ route('dashboard') }}" class="athar-brand"><span class="athar-brand-mark">A</span><span>ATHAR<small>Digital Forensics</small></span></a>
    <div class="athar-user"><span class="athar-avatar">{{ mb_substr(Auth::user()->name, 0, 1) }}</span><div><strong>{{ Auth::user()->name }}</strong><small>{{ strtoupper(Auth::user()->role) }}</small></div></div>
    <nav class="athar-nav" aria-label="التنقل الرئيسي">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>◈</span> لوحة التحقيق</a>
        <a class="{{ request('section') === 'cases' ? 'active' : '' }}" href="{{ route('workspace', 'cases') }}"><span>▣</span> القضايا</a>
        <a class="{{ request('section') === 'evidence' ? 'active' : '' }}" href="{{ route('workspace', 'evidence') }}"><span>◉</span> الأدلة</a>
        <a class="{{ request('section') === 'jobs' ? 'active' : '' }}" href="{{ route('workspace', 'jobs') }}"><span>↻</span> مهام التحليل</a>
        <a class="{{ request('section') === 'findings' ? 'active' : '' }}" href="{{ route('workspace', 'findings') }}"><span>✦</span> النتائج</a>
        <a class="{{ request('section') === 'reports' ? 'active' : '' }}" href="{{ route('workspace', 'reports') }}"><span>▤</span> التقارير</a>
        <a class="{{ request('section') === 'tools' ? 'active' : '' }}" href="{{ route('workspace', 'tools') }}"><span>⌁</span> سجل الأدوات</a>
        <a class="{{ request('section') === 'custody' ? 'active' : '' }}" href="{{ route('workspace', 'custody') }}"><span>⌬</span> سجل التدقيق</a>
    </nav>
    <div class="athar-sidebar-footer"><a href="{{ route('profile.edit') }}">إعدادات الحساب</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">تسجيل الخروج</button></form></div>
</aside>
