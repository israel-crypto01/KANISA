<!doctype html>
<html lang="sw">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #17213d; font-size: 10px; }
        h1 { margin: 0 0 5px; font-size: 20px; }
        .subtitle { margin: 0 0 16px; color: #5e6d87; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 5px; margin-bottom: 18px; }
        .summary td { width: 16.66%; background: #f3f6fa; padding: 9px; }
        .summary .label { display: block; color: #5e6d87; margin-bottom: 4px; font-size: 9px; }
        .summary .success { color: #12633d; background: #e9f8ef; }
        .summary .warning { color: #9a4d00; background: #fff5df; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #dfe5ef; padding: 7px 5px; text-align: left; }
        th { background: #f3f6fa; color: #53627d; font-size: 9px; }
        .amount { text-align: right; font-weight: bold; }
        .complete { color: #12633d; font-weight: bold; }
        .partial { color: #4f46e5; font-weight: bold; }
        .none { color: #9a4d00; font-weight: bold; }
        .footer { margin-top: 18px; color: #71809a; font-size: 9px; }
    </style>
</head>
<body>
    <h1>Ripoti ya Mchango: {{ $campaign->title }}</h1>
    <p class="subtitle">Idara: {{ $campaign->department?->name_sw ?? 'Idara' }} · Kiasi cha kila mwanachama: TZS {{ number_format($amountPerMember, 2) }} · Mwisho: {{ $campaign->due_date?->format('d/m/Y') }}</p>

    <table class="summary"><tr>
        <td><span class="label">Wanachama</span><strong>{{ $rows->count() }}</strong></td>
        <td><span class="label">Lengo lote</span><strong>TZS {{ number_format($summary['target'], 2) }}</strong></td>
        <td class="success"><span class="label">Kiasi kilichopatikana</span><strong>TZS {{ number_format($summary['paid'], 2) }}</strong></td>
        <td class="warning"><span class="label">Kilichobaki kwa ujumla</span><strong>TZS {{ number_format($summary['balance'], 2) }}</strong></td>
        <td><span class="label">Asilimia ya jumla</span><strong>{{ $summary['percent'] }}%</strong></td>
        <td><span class="label">Hali ya wanachama</span><strong>{{ $summary['completeCount'] }} wamemaliza · {{ $summary['partialCount'] }} wamepunguza · {{ $summary['notStartedCount'] }} hawajaanza</strong></td>
    </tr></table>

    <table>
        <thead><tr><th>Mwanachama</th><th>Kiasi alichopangiwa</th><th>Kiasi alichotoa</th><th>Kilichobaki</th><th>Asilimia</th><th>Hali</th></tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['member']->name }}@if($row['member']->member_id)<br><span style="color:#71809a">{{ $row['member']->member_id }}</span>@endif</td>
                    <td class="amount">TZS {{ number_format($amountPerMember, 2) }}</td>
                    <td class="amount">TZS {{ number_format($row['paid'], 2) }}</td>
                    <td class="amount">TZS {{ number_format($row['balance'], 2) }}</td>
                    <td class="amount">{{ $row['percent'] }}%</td>
                    <td class="{{ $row['status'] === 'Amemaliza' ? 'complete' : ($row['status'] === 'Amepunguza' ? 'partial' : 'none') }}">{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#71809a;padding:16px;">Hakuna wanachama kwenye idara hii.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="footer">Ripoti hii ni ya mchango huu pekee na inaonesha kiasi, salio na asilimia ya kila mwanachama.</p>
</body>
</html>
