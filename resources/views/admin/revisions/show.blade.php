@extends('admin.layouts.vertical')
@section('content')
<h2>Revision</h2>
<pre style="white-space:pre-wrap">{{ json_encode($revision->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
<form method="post" action="/administrator/revisions/{{ $revision->id }}/restore">@csrf<button class="btn">Restore this snapshot</button></form>
@endsection
