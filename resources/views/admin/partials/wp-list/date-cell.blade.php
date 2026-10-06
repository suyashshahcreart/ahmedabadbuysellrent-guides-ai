@php
    $status = $status ?? 'draft';
    $badgeMap = [
        'published' => 'published',
        'draft' => 'draft',
        'scheduled' => 'scheduled',
        'pending_review' => 'scheduled',
        'archived' => 'archived',
        'trash' => 'archived',
    ];
    $badge = $badgeMap[$status] ?? 'draft';
    $when = $when ?? null;
    $tz = config('app.timezone', 'Asia/Kolkata');
@endphp
<div class="post-wp-date-cell">
    <span class="post-wp-status-badge post-wp-status-badge--{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
    @if($when)
        <span class="post-wp-date-time" title="Timezone: {{ $tz }}">{{ $when->timezone($tz)->format('d M Y H:i:s') }}</span>
    @endif
</div>
