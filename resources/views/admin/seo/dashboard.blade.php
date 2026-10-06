@extends('admin.layouts.vertical', ['title' => 'SEO audit'])
@section('content')
<h3 class="mb-3">SEO editorial checklist</h3>
<p>This is an editorial checklist, not Google’s ranking score. Score: <strong>{{ $score }}/100</strong> ({{ $errorCount }} errors, {{ $warningCount }} warnings)</p>
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Issues'])
    <div class="table-responsive">
        <table class="table mb-0">
            <tr><th>Level</th><th>Rule</th><th>Item</th></tr>
            @foreach($issues as $i)
            <tr><td>{{ $i['level'] }}</td><td>{{ $i['rule'] }}</td><td>{{ $i['label'] }}</td></tr>
            @endforeach
        </table>
    </div>
</div>
@endsection
