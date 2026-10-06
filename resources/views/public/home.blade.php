@extends('public.layout')
@section('bodyClass', 'page-home')
@section('content')
<section class="hero hero-home">
  <div class="container hero-grid">
    <div class="hero-content">
      <p class="eyebrow">Ahmedabad</p>
      <h1>Your trusted guide to <em>buying, selling and renting</em> property in Ahmedabad.</h1>
      <p class="lede">Locality reviews, comparisons, and practical advice for the city’s seven municipal zones — from the walled city to SG Highway.</p>
      <div class="search-box">
        <form action="/search/" method="get" role="search">
          <label class="visually-hidden" for="hero-q">Search guides</label>
          <input id="hero-q" name="q" type="search" placeholder="Search: Bodakdev, rent, home loans…" autocomplete="off">
          <button type="submit">Search</button>
        </form>
      </div>
      <div class="hero-actions">
        <a class="btn btn-navy" href="/guides/">Explore guides</a>
        @if($ctaUrl)<a class="btn btn-outline" href="{{ $ctaUrl }}">{{ $ctaLabel }}</a>@endif
      </div>
      <div class="hero-stat-bar">
        <div class="hero-stat"><strong>{{ \App\Models\Article::published()->count() }}+</strong><span>Property guides</span></div>
        <div class="hero-stat"><strong>{{ $localities->count() }}</strong><span>Municipal zones</span></div>
        <div class="hero-stat"><strong>{{ $categories->count() }}</strong><span>Guide categories</span></div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-card-stack">
        <a class="hero-float-card card-main" href="/guides/new-west-zone/">
          <img src="/assets/photos/sg-highway.jpg" alt="SG Highway, Ahmedabad" width="640" height="360">
          <div class="card-label"><span>Area guide</span><strong>New West Zone</strong></div>
        </a>
        <a class="hero-float-card card-secondary" href="/compare/bodakdev-vs-satellite/">
          <img src="/assets/photos/iim.jpg" alt="IIM Ahmedabad campus" width="640" height="360">
          <div class="card-label"><span>Comparison</span><strong>Bodakdev vs Satellite</strong></div>
        </a>
      </div>
      <div class="hero-visual-mobile">
        <a href="/guides/new-west-zone/">
          <img src="/assets/photos/sg-highway.jpg" alt="SG Highway in Ahmedabad" width="640" height="360">
          <span>New West Zone</span>
        </a>
        <a href="/compare/bodakdev-vs-satellite/">
          <img src="/assets/photos/iim.jpg" alt="IIM Ahmedabad campus" width="640" height="360">
          <span>Bodakdev vs Satellite</span>
        </a>
      </div>
    </div>
  </div>
</section>

<section class="trust-strip">
  <div class="container trust-grid">
    <p><strong>Ahmedabad-focused</strong> Local zones, not generic advice.</p>
    <p><strong>Updated guides</strong> Practical steps for buyers, tenants and sellers.</p>
    <p><strong>Clear comparisons</strong> Choose between localities with context.</p>
    <p><strong>Editorial CMS</strong> Guides maintained from the newsroom admin.</p>
  </div>
</section>

<section class="section section-warm">
  <div class="container">
    <div class="section-head"><div>
      <h2>Popular categories</h2>
      <div class="accent-line"></div>
      <p class="section-intro">Start with the decision you are making, then drill into the area that matters.</p>
    </div></div>
    <div class="category-grid">
      @foreach($categories as $cat)
        <a class="category-tile" href="{{ $cat->publicUrl() }}">
          <div class="icon" aria-hidden="true">@include('public.partials.category-icon', ['icon' => $cat->icon])</div>
          <h3>{{ $cat->name }}</h3>
          <p>{{ $cat->description }}</p>
          <span class="count">Explore {{ strtolower($cat->name) }}</span>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="section section-surface">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Featured guides</h2>
        <div class="accent-line"></div>
      </div>
      <a class="link-text" href="/guides/">See all guides</a>
    </div>
    <div class="card-grid cols-3">
      @foreach($featured as $item)
        @include('public.partials.guide-card', ['item' => $item, 'variant' => 'is-featured'])
      @endforeach
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head"><div>
      <h2>Trending guides</h2>
      <div class="accent-line"></div>
    </div></div>
    <div class="card-grid cols-3 trending-row">
      @foreach($trending as $item)
        @include('public.partials.guide-card', ['item' => $item, 'variant' => 'is-compact'])
      @endforeach
    </div>
  </div>
</section>

<section class="section section-locality">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Ahmedabad locality guides</h2>
        <div class="accent-line"></div>
      </div>
      <a class="link-text" href="/areas/">All area guides</a>
    </div>
    <div class="locality-grid">
      @foreach($localities as $i => $zone)
        <a class="locality-card{{ $loop->first ? ' is-lead' : '' }}" href="{{ $zone->publicUrl() }}">
          <div class="thumb">
            <img src="{{ optional($zone->featuredMedia)->url() ?? '/assets/photos/sg-highway.jpg' }}" alt="{{ optional($zone->featuredMedia)->alt ?? $zone->name }}" loading="lazy" width="640" height="360">
            <span class="zone-num">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</span>
            <h3>{{ $zone->name }}</h3>
          </div>
          <div class="body">
            <p>{{ $zone->summary }}</p>
            <p class="locality-tags">@foreach($zone->tags ?? [] as $t)<span>{{ $t }}</span>@endforeach</p>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div><h2>Latest articles</h2><div class="accent-line"></div></div>
      <a class="link-text" href="/guides/">All guides</a>
    </div>
    <div class="latest-layout">
      @if($latest->first())
        @include('public.partials.guide-card', ['item' => $latest->first(), 'variant' => 'is-featured'])
      @endif
      <div class="latest-stack">
        @foreach($latest->skip(1) as $item)
          @include('public.partials.guide-card', ['item' => $item, 'variant' => 'is-compact'])
        @endforeach
      </div>
    </div>
  </div>
</section>

<section class="section section-warm">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Real estate market insights</h2>
        <div class="accent-line"></div>
      </div>
      <a class="link-text" href="/market/">Market guides</a>
    </div>
    <div class="insight-grid">
      @foreach($insights as $insight)
        <article class="insight-card">
          <p class="tag">{{ $insight->label }}</p>
          <p class="insight-stat">{{ $insight->stat }}</p>
          <h3>{{ $insight->title }}</h3>
          <p>{{ $insight->body }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

@include('public.partials.faqs', ['faqs' => $faqs])
@endsection
