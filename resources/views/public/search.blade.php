@extends('public.layout')
@section('content')
<section class="hero hero-compact">
  <div class="container">
    <h1>Search guides</h1>
    <div class="search-box">
      <form action="/search/" method="get">
        <input name="q" type="search" value="{{ $q }}" placeholder="Search guides…" aria-label="Search">
        <button type="submit">Search</button>
      </form>
    </div>
  </div>
</section>
<section class="section">
  <div class="container">
    @if($q === '')
      <p class="empty-copy">Enter a search term to find guides.</p>
    @elseif($results->isEmpty())
      <p class="empty-copy">No results for “{{ $q }}”.</p>
    @else
      <p class="search-status">{{ $results->count() }} result{{ $results->count() === 1 ? '' : 's' }} for “{{ $q }}”</p>
      <div class="card-grid cols-3">
        @foreach($results as $hit)
          <a class="guide-card" href="{{ $hit['url'] }}">
            <div class="body">
              <span class="tag">{{ $hit['label'] ?? ucfirst($hit['type']) }}</span>
              <h3>{{ $hit['title'] }}</h3>
              <p class="excerpt">{{ \Illuminate\Support\Str::limit(strip_tags((string)$hit['excerpt']), 160) }}</p>
            </div>
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>
@endsection
