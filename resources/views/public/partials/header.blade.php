<header class="site-header{{ request()->is('/') ? ' is-overlay' : '' }}">
  <div class="container header-inner">
    <a class="wordmark" href="/">
      @if(!empty($siteFavicon))
      <img src="{{ $siteFavicon }}" alt="" width="28" height="28" style="width:28px;height:28px;object-fit:contain;margin-right:.45rem;vertical-align:middle;border-radius:6px">
      @endif
      {{ $siteName ?? $siteShort }}
      <span>Buy · Rent · Sell</span>
    </a>
    <form class="header-search" action="/search/" method="get" role="search">
      <label class="visually-hidden" for="header-q">Search guides</label>
      <input id="header-q" name="q" type="search" placeholder="Search guides" autocomplete="off">
    </form>
    <nav class="nav-desktop" aria-label="Primary">
      @if($headerMenu)
        @foreach($headerMenu->items as $item)
          <a href="{{ $item->url }}" @if($item->target === '_blank') target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
        @endforeach
      @endif
    </nav>
    @if($ctaUrl)
      <a class="btn btn-navy header-cta" href="{{ $ctaUrl }}">{{ $ctaLabel }}</a>
    @endif
    <button class="menu-btn" type="button" data-menu aria-expanded="false" aria-controls="site-nav">Menu</button>
  </div>
  <nav id="site-nav" class="nav-mobile" data-nav-mobile aria-label="Mobile">
    <div class="container">
      @if($headerMenu)
        @foreach($headerMenu->items as $item)
          <a href="{{ $item->url }}">{{ $item->label }}</a>
        @endforeach
      @endif
      <a href="/search/">Search</a>
      <a href="/about/">About</a>
    </div>
  </nav>
</header>
