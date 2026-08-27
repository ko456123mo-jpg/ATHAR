<x-app-layout>
    @php($labels = ['cases' => 'القضايا', 'evidence' => 'الأدلة', 'jobs' => 'مهام التحليل', 'tools' => 'سجل الأدوات', 'findings' => 'النتائج', 'reports' => 'التقارير', 'custody' => 'سجل التدقيق'])
    <section class="athar-page-header"><div><p class="athar-kicker">ATHAR WORKSPACE</p><h1>{{ $labels[$section] }}</h1><p>البيانات الظاهرة محكومة بالصلاحيات الخادمية وسجل التدقيق.</p></div><span class="athar-record-count">{{ $data->total() }} سجل</span></section>
    <section class="athar-panel athar-table-panel">
        @forelse($data as $record)
            <article class="athar-record-row">
                @if($section === 'cases')<div><span class="athar-record-code">{{ $record->case_number }}</span><strong>{{ $record->name }}</strong><small>{{ $record->description ?: 'لا يوجد وصف إضافي.' }}</small></div><span class="athar-status status-{{ $record->status }}">{{ $record->status }}</span>
                @elseif($section === 'evidence')<div><span class="athar-record-code">{{ $record->evidence_number }}</span><strong>{{ $record->name }}</strong><small>{{ $record->type }} · {{ number_format($record->size_bytes) }} bytes · {{ $record->investigationCase?->case_number }}</small></div><span class="athar-status status-{{ $record->status }}">{{ $record->status }}</span>
                @elseif($section === 'jobs')<div><span class="athar-record-code">{{ $record->public_id }}</span><strong>{{ $record->tool?->name }}</strong><small>{{ $record->investigationCase?->case_number }} · المحاولة {{ $record->attempt_count }}/{{ $record->max_attempts }}</small></div><span class="athar-status status-{{ $record->status }}">{{ $record->status }}</span>
                @elseif($section === 'tools')<div><span class="athar-record-code">{{ $record->category?->name }}</span><strong>{{ $record->name }}</strong><small>{{ $record->adapter_key }} · {{ $record->operations->count() }} عمليات مسجلة</small></div><span class="athar-status status-{{ $record->verification_state }}">{{ $record->verification_state }}</span>
                @elseif($section === 'findings')<div><span class="athar-record-code">{{ $record->severity }}</span><strong>{{ $record->title }}</strong><small>{{ $record->investigationCase?->case_number }} · {{ $record->evidence?->evidence_number ?? 'نتيجة يدوية' }}</small></div><span class="athar-status status-{{ $record->status }}">{{ $record->status }}</span>
                @elseif($section === 'reports')<div><span class="athar-record-code">{{ $record->investigationCase?->case_number }}</span><strong>تقرير الإصدار {{ $record->version }}</strong><small>{{ $record->content_hash ? 'مؤشر سلامة المحتوى محفوظ' : 'لم يولّد بعد' }}</small></div><span class="athar-status status-{{ $record->status }}">{{ $record->status }}</span>
                @else<div><span class="athar-record-code">{{ $record->action }}</span><strong>{{ class_basename($record->entity_type) }}</strong><small>{{ $record->created_at?->format('Y-m-d H:i:s') }} · {{ $record->ip_address ?? 'System event' }}</small></div><span class="athar-status status-completed">audited</span>
                @endif
            </article>
        @empty
            <div class="athar-empty"><span>◌</span><strong>لا توجد سجلات ضمن هذا القسم</strong><p>ستظهر هنا السجلات فور إنشائها عبر واجهة ATHAR المعتمدة.</p></div>
        @endforelse
        <div class="athar-pagination">{{ $data->links() }}</div>
    </section>
</x-app-layout>
