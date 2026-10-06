<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-grid">
      <div>
        <h2>{{ $siteShort }}</h2>
        <p>Property guides for buying, renting and selling in Ahmedabad.</p>
      </div>
      <div>
        <h2>Guides</h2>
        <ul>
          @foreach($navCategories as $cat)
            <li><a href="{{ $cat->publicUrl() }}">{{ $cat->name }}</a></li>
          @endforeach
        </ul>
      </div>
      <div>
        <h2>Explore</h2>
        <ul>
          <li><a href="/guides/">All guides</a></li>
          <li><a href="/areas/">Area guides</a></li>
          <li><a href="/search/">Search</a></li>
          <li><a href="/about/">About</a></li>
        </ul>
      </div>
      <div>
        <h2>{{ $siteName }}</h2>
        <ul>
          @if($footerMenu)
            @foreach($footerMenu->items as $item)
              <li><a href="{{ $item->url }}" @if($item->target === '_blank') target="_blank" rel="noopener" @endif>{{ $item->label }}</a></li>
            @endforeach
          @endif
        </ul>
      </div>
    </div>
    <div class="footer-base">
      <p>© {{ date('Y') }} {{ $siteName }}</p>
    </div>
  </div>
</footer>
