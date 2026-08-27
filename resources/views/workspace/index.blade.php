<x-app-layout>
    @php($labels = ['cases' => 'القضايا', 'evidence' => 'الأدلة', 'jobs' => 'مهام التحليل', 'tools' => 'سجل الأدوات', 'findings' => 'النتائج', 'reports' => 'التقارير', 'custody' => 'سجل التدقيق'])
    <section class="athar-page-header"><div><p class="athar-kicker">ATHAR WORKSPACE</p><h1>{{ $labels[$section] }}</h1><p>العمليات المعروضة محكومة بالصلاحيات الخادمية وتعيد استخدام مسارات السجل والخدمات الجنائية نفسها.</p></div><span class="athar-record-count">{{ $data->total() }} سجل</span></section>

    @if($section === 'cases' && $workspace['canCreateCase'])
        <section class="athar-panel athar-action-panel"><div class="athar-action-heading"><p class="athar-kicker">NEW CASE</p><h2>فتح قضية تحقيق</h2><p>يُسند منشئ القضية قائدًا تلقائيًا، وتُسجل العملية في سجل التدقيق.</p></div><form method="POST" action="{{ route('workspace.cases.store') }}" class="athar-form-grid">@csrf<label>اسم القضية<input required maxlength="255" name="name" value="{{ old('name') }}"></label><label>الأولوية<select required name="priority"><option value="medium">متوسطة</option><option value="low">منخفضة</option><option value="high">عالية</option><option value="critical">حرجة</option></select></label><label class="athar-form-full">وصف مختصر<textarea name="description" maxlength="10000" rows="3">{{ old('description') }}</textarea></label><div class="athar-form-full athar-form-actions"><button type="submit">إنشاء القضية</button></div></form></section>
    @endif

    @if($section === 'evidence' && $workspace['editableCases']->isNotEmpty())
        <section class="athar-panel athar-action-panel"><div class="athar-action-heading"><p class="athar-kicker">PRIVATE EVIDENCE</p><h2>تسجيل دليل جديد</h2><p>يُحفظ الملف خارج المسار العام ويُسجل SHA-256 وسلسلة الحيازة عند الاستلام.</p></div><form method="POST" action="{{ route('workspace.evidence.store') }}" enctype="multipart/form-data" class="athar-form-grid">@csrf<label>القضية<select required name="case_id">@foreach($workspace['editableCases'] as $case)<option value="{{ $case->id }}">{{ $case->case_number }} — {{ $case->name }}</option>@endforeach</select></label><label>اسم الدليل<input required maxlength="255" name="name" value="{{ old('name') }}"></label><label>النوع<select required name="type"><option value="pcap">PCAP</option><option value="pcapng">PCAPNG</option><option value="disk_image">Disk image</option><option value="memory_image">Memory image</option><option value="file_archive">File archive</option><option value="document">Document</option><option value="other">Other</option></select></label><label>المصدر<input maxlength="255" name="source" value="{{ old('source') }}" placeholder="مصدر الاستلام أو المرجع"></label><label class="athar-form-full">الملف<input required type="file" name="evidence"></label><div class="athar-form-full athar-form-actions"><button type="submit">حفظ الدليل الخاص</button></div></form></section>
    @endif

    @if($section === 'jobs' && $workspace['jobEvidence']->isNotEmpty() && $workspace['tools']->isNotEmpty())
        <section class="athar-panel athar-action-panel"><div class="athar-action-heading"><p class="athar-kicker">CONTROLLED ANALYSIS</p><h2>طلب تحليل مسموح</h2><p>لا تظهر هنا إلا أدلة لها نسخة عمل. الحالة المسجلة للأداة لا تعني تحققًا شاملًا في CAINE.</p></div><form method="POST" action="{{ route('workspace.jobs.store') }}" class="athar-form-grid">@csrf<label>القضية<select required name="case_id">@foreach($workspace['editableCases'] as $case)<option value="{{ $case->id }}">{{ $case->case_number }} — {{ $case->name }}</option>@endforeach</select></label><label>دليل بنسخة عمل<select required name="evidence_id">@foreach($workspace['jobEvidence'] as $evidence)<option value="{{ $evidence->id }}">{{ $evidence->evidence_number }} — {{ $evidence->name }} ({{ $evidence->type }})</option>@endforeach</select></label><label>الأداة<select required name="tool_id">@foreach($workspace['tools'] as $tool)<option value="{{ $tool->id }}">{{ $tool->name }} — {{ $tool->verification_state }}</option>@endforeach</select></label><label>العملية<select required name="operation_id">@foreach($workspace['tools'] as $tool)@foreach($tool->operations as $operation)<option value="{{ $operation->id }}">{{ $tool->name }} · {{ $operation->operation_key }}</option>@endforeach @endforeach</select></label><div class="athar-form-full athar-form-actions"><button type="submit">إنشاء مهمة التحليل</button></div></form></section>
    @endif

    @if($section === 'findings' && $workspace['editableCases']->isNotEmpty())
        <section class="athar-panel athar-action-panel"><div class="athar-action-heading"><p class="athar-kicker">INVESTIGATOR FINDING</p><h2>تسجيل نتيجة قابلة للمراجعة</h2><p>تظل النتيجة مسودة حتى يتخذ مستخدم مخول قرار المراجعة.</p></div><form method="POST" action="{{ route('workspace.findings.store') }}" class="athar-form-grid">@csrf<label>القضية<select required name="case_id">@foreach($workspace['editableCases'] as $case)<option value="{{ $case->id }}">{{ $case->case_number }} — {{ $case->name }}</option>@endforeach</select></label><label>الشدة<select required name="severity"><option value="informational">معلوماتي</option><option value="low">منخفضة</option><option value="medium">متوسطة</option><option value="high">عالية</option><option value="critical">حرجة</option></select></label><label class="athar-form-full">العنوان<input required maxlength="255" name="title" value="{{ old('title') }}"></label><label class="athar-form-full">وصف مبني على المراجعة البشرية<textarea required maxlength="20000" rows="4" name="description">{{ old('description') }}</textarea></label><div class="athar-form-full athar-form-actions"><button type="submit">حفظ النتيجة كمسودة</button></div></form></section>
    @endif

    @if($section === 'reports' && $workspace['editableCases']->isNotEmpty())
        <section class="athar-panel athar-action-panel"><div class="athar-action-heading"><p class="athar-kicker">PRIVATE REPORT</p><h2>إنشاء إصدار تقرير</h2><p>ينشأ التقرير بصيغة Markdown خاصة ويضم النتائج المراجعة فقط مع بصمة للمحتوى.</p></div><form method="POST" action="{{ route('workspace.reports.store') }}" class="athar-inline-form">@csrf<label>القضية<select required name="case_id">@foreach($workspace['editableCases'] as $case)<option value="{{ $case->id }}">{{ $case->case_number }} — {{ $case->name }}</option>@endforeach</select></label><button type="submit">إنشاء إصدار جديد</button></form></section>
    @endif

    <section class="athar-panel athar-table-panel">
        @forelse($data as $record)
            <article class="athar-record-row">
                @if($section === 'cases')
                    <div><span class="athar-record-code">{{ $record->case_number }}</span><strong>{{ $record->name }}</strong><small>{{ $record->description ?: 'لا يوجد وصف إضافي.' }}</small></div><span class="athar-status status-{{ $record->status }}">{{ $record->status }}</span>
                @elseif($section === 'evidence')
                    <div><span class="athar-record-code">{{ $record->evidence_number }}</span><strong>{{ $record->name }}</strong><small>{{ $record->type }} · {{ number_format($record->size_bytes) }} bytes · {{ $record->investigationCase?->case_number }}</small>@if($record->working_copy_key)<small class="athar-working-copy">نسخة العمل متاحة للتحليل</small>@endif</div>
                    <div class="athar-row-actions"><span class="athar-status status-{{ $record->status }}">{{ $record->status }}</span>
                    @can('download', $record)
                        <form method="POST" action="{{ route('workspace.evidence.verify', $record) }}">@csrf<button type="submit">تحقق</button></form><form method="POST" action="{{ route('workspace.evidence.working-copy', $record) }}">@csrf<button type="submit">نسخة عمل</button></form>
                    @endcan
                    </div>
                @elseif($section === 'jobs')
                    <div><span class="athar-record-code">{{ $record->public_id }}</span><strong>{{ $record->tool?->name }}</strong><small>{{ $record->investigationCase?->case_number }} · المحاولة {{ $record->attempt_count }}/{{ $record->max_attempts }}</small></div>
                    <div class="athar-row-actions"><span class="athar-status status-{{ $record->status }}">{{ $record->status }}</span>
                    @can('update', $record->investigationCase)
                        @if(in_array($record->status, ['created', 'queued', 'claimed', 'running', 'parsing']))
                            <form method="POST" action="{{ route('workspace.jobs.cancel', $record) }}">@csrf<button type="submit">إلغاء</button></form>
                        @endif
                        @if(in_array($record->status, ['failed', 'timed_out']) && $record->attempt_count < $record->max_attempts)
                            <form method="POST" action="{{ route('workspace.jobs.retry', $record) }}">@csrf<button type="submit">إعادة</button></form>
                        @endif
                    @endcan
                    </div>
                @elseif($section === 'tools')
                    <div><span class="athar-record-code">{{ $record->category?->name }}</span><strong>{{ $record->name }}</strong><small>{{ $record->adapter_key }} · {{ $record->operations->count() }} عمليات مسجلة</small></div><span class="athar-status status-{{ $record->verification_state }}">{{ $record->verification_state }}</span>
                @elseif($section === 'findings')
                    <div><span class="athar-record-code">{{ $record->severity }}</span><strong>{{ $record->title }}</strong><small>{{ $record->investigationCase?->case_number }} · {{ $record->evidence?->evidence_number ?? 'نتيجة يدوية' }}</small></div>
                    <div class="athar-row-actions"><span class="athar-status status-{{ $record->status }}">{{ $record->status }}</span>
                    @can('review-findings')
                        @if($record->status === 'draft')
                            <form method="POST" action="{{ route('workspace.findings.review', $record) }}" class="athar-review-form">@csrf<select name="status"><option value="reviewed">مراجعة</option><option value="confirmed">تأكيد</option><option value="rejected">رفض</option></select><button type="submit">حفظ</button></form>
                        @endif
                    @endcan
                    </div>
                @elseif($section === 'reports')
                    <div><span class="athar-record-code">{{ $record->investigationCase?->case_number }}</span><strong>تقرير الإصدار {{ $record->version }}</strong><small>{{ $record->content_hash ? 'مؤشر سلامة المحتوى محفوظ' : 'لم يولّد بعد' }}</small></div>
                    <div class="athar-row-actions"><span class="athar-status status-{{ $record->status }}">{{ $record->status }}</span>
                    @can('review-findings')
                        @if($record->status === 'draft')
                            <form method="POST" action="{{ route('workspace.reports.approve', $record) }}">@csrf<button type="submit">اعتماد</button></form>
                        @endif
                    @endcan
                    </div>
                @else
                    <div><span class="athar-record-code">{{ $record->action }}</span><strong>{{ class_basename($record->entity_type) }}</strong><small>{{ $record->created_at?->format('Y-m-d H:i:s') }} · {{ $record->ip_address ?? 'System event' }}</small></div><span class="athar-status status-completed">audited</span>
                @endif
            </article>
        @empty
            <div class="athar-empty"><span>◌</span><strong>لا توجد سجلات ضمن هذا القسم</strong><p>ستظهر هنا السجلات فور إنشائها عبر واجهة ATHAR المعتمدة.</p></div>
        @endforelse
        <div class="athar-pagination">{{ $data->links() }}</div>
    </section>
</x-app-layout>
