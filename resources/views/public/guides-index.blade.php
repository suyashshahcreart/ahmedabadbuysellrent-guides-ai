@extends('public.layout')
@section('content')
<section class="hero hero-compact">
  <div class="container">
    <h1>Every property guide for Ahmedabad</h1>
    <div class="search-box">
      <form action="/search/" method="get"><input name="q" type="search" placeholder="Search guides…" aria-label="Search"><button type="submit">Search</button></form>
    </div>
  </div>
</section>
<section class="section">
  <div class="container" data-filter-root>
    <div class="filters" data-filters>
      <button type="button" class="is-on" data-group="all">All</button>
      @foreach($categories as $cat)
        <button type="button" data-group="{{ $cat->slug }}">{{ $cat->nav_label ?? $cat->name }}</button>
      @endforeach
    </div>
    <div class="card-grid cols-3">
      @foreach($articles as $item)
        <div data-item="{{ optional($item->category)->slug }}">
          @include('public.partials.guide-card', ['item' => $item])
        </div>
      @endforeach
    </div>
  </div>
</section>
@endsection
