<x-app-layout>
    <section class="athar-page-header"><div><p class="athar-kicker">OPERATIONAL OVERVIEW</p><h1>لوحة التحقيق</h1><p>مرحبًا {{ Auth::user()->name }}. راقب القضايا والأدلة وحالة التنفيذ من مصدر واحد.</p></div><div class="athar-security-chip"><span></span> سلسلة الحيازة مفعّلة</div></section>
    <section class="athar-stat-grid" aria-label="ملخص التحقيقات">
        <article class="athar-stat-card"><span>القضايا النشطة</span><strong>{{ number_format($summary['cases']) }}</strong><small>ضمن نطاق صلاحياتك</small></article>
        <article class="athar-stat-card"><span>الأدلة الخاصة</span><strong>{{ number_format($summary['evidence']) }}</strong><small>محفوظة مع SHA-256</small></article>
        <article class="athar-stat-card accent"><span>مهام قيد التنفيذ</span><strong>{{ number_format($summary['activeJobs']) }}</strong><small>Queued · Claimed · Running</small></article>
        <article class="athar-stat-card"><span>نتائج بانتظار المراجعة</span><strong>{{ number_format($summary['reviewFindings']) }}</strong><small>المحقق هو صاحب القرار</small></article>
    </section>
    <section class="athar-dashboard-grid">
        <article class="athar-panel athar-job-panel"><div class="athar-panel-heading"><div><p>LIVE QUEUE</p><h2>آخر مهام التحليل</h2></div><a href="{{ route('workspace', 'jobs') }}">عرض الكل ←</a></div>
            @forelse($recentJobs as $job)
                <div class="athar-job-row"><div><strong>{{ $job->tool?->name ?? 'أداة غير معروفة' }}</strong><span>{{ $job->investigationCase?->case_number }} · {{ $job->public_id }}</span></div><span class="athar-status status-{{ $job->status }}">{{ $job->status }}</span></div>
            @empty
                <div class="athar-empty"><span>◌</span><strong>لا توجد مهام تحليل حتى الآن</strong><p>أنشئ قضية وسجل دليلًا خاصًا لبدء مسار التحليل الآمن.</p></div>
            @endforelse
        </article>
        <aside class="athar-panel athar-readiness"><p>CAINE 14.0 READINESS</p><h2>حالة التكامل</h2><div class="athar-readiness-meter"><span style="width: 20%"></span></div><strong>{{ $summary['verifiedTools'] }} أدوات متحققة من النهاية إلى النهاية</strong><small>يُعرض المسار كمتاح فقط بعد دليل تثبيت وتشغيل وتكامل موثق.</small><a href="{{ route('workspace', 'tools') }}">مراجعة سجل الأدوات</a></aside>
    </section>
</x-app-layout>
