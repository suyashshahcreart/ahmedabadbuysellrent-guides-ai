@php
  $href = $item->publicUrl();
  $img = optional($item->featuredMedia)->url();
  $alt = optional($item->featuredMedia)->alt ?? $item->title;
  $category = $item->category;
  $catLabel = optional($category)->displayLabel();
  $catUrl = optional($category)->publicUrl();
@endphp
<article class="guide-card {{ $variant ?? '' }}">
  <a class="guide-card-hit" href="{{ $href }}" aria-label="{{ $item->title }}"></a>
  @if($img)
  <div class="thumb {{ ($variant ?? '') === 'is-compact' ? 'thumb-sm' : '' }}">
    <img src="{{ $img }}" alt="{{ $alt }}" loading="lazy" width="960" height="540">
  </div>
  @endif
  <div class="body">
    @if($catLabel && $catUrl)
      <a class="tag" href="{{ $catUrl }}">{{ $catLabel }}</a>
    @elseif($catLabel)
      <span class="tag">{{ $catLabel }}</span>
    @endif
    <h3>{{ $item->title }}</h3>
    @if(!empty($item->excerpt) && ($variant ?? '') !== 'is-compact')<p class="excerpt">{{ $item->excerpt }}</p>@endif
    <span class="meta">{{ optional($item->published_at)?->timezone(config('app.timezone', 'Asia/Kolkata'))->format('d M Y H:i:s') }}@if($item->reading_time) · {{ $item->reading_time }} min read @endif</span>
  </div>
</article>
