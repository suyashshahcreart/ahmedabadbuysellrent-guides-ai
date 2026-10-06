@extends('public.layout')
@section('content')
<section class="hero hero-compact">
  <div class="container">
    <p class="eyebrow">{{ $siteShort }}</p>
    <h1>{{ $category->name }}</h1>
    <p class="lede">{{ $category->description }}</p>
  </div>
</section>
<section class="section">
  <div class="container">
    @if($localities->count())
    <div class="locality-grid" style="margin-bottom:32px">
      @foreach($localities as $zone)
        <a class="locality-card" href="{{ $zone->publicUrl() }}">
          <div class="thumb"><img src="{{ optional($zone->featuredMedia)->url() ?? '/assets/photos/sg-highway.jpg' }}" alt="{{ $zone->name }}" loading="lazy" width="640" height="360"></div>
          <div class="body"><h3>{{ $zone->name }}</h3><p>{{ $zone->summary }}</p></div>
        </a>
      @endforeach
    </div>
    @endif
    <div class="card-grid cols-3">
      @forelse($articles as $item)
        @include('public.partials.guide-card', ['item' => $item])
      @empty
        <p class="empty-copy">Guides for this topic are coming soon.</p>
      @endforelse
    </div>
  </div>
</section>
@endsection
