@extends('public.layout')
@section('content')
<div class="reading-progress" data-progress aria-hidden="true"></div>
<article class="article-page">
  <header class="article-hero">
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb">
        <ol>
          <li><a href="/">Home</a></li>
          @if($article->category)
            <li><a href="{{ $article->category->publicUrl() }}">{{ $article->category->name }}</a></li>
          @endif
          <li>{{ $article->title }}</li>
        </ol>
      </nav>
      <h1>{{ $article->title }}</h1>
      @if($article->excerpt)<p class="lede">{{ $article->excerpt }}</p>@endif
      <div class="article-meta">
        <span>{{ optional($article->author)->name ?? 'Ahmedabad Guides' }}</span>
        <span>Published <time datetime="{{ optional($article->published_at)?->timezone(config('app.timezone', 'Asia/Kolkata'))->toIso8601String() }}">{{ optional($article->published_at)?->timezone(config('app.timezone', 'Asia/Kolkata'))->format('d M Y H:i:s') }}</time></span>
        @if($article->reading_time)<span>{{ $article->reading_time }} min read</span>@endif
      </div>
      @if($article->category || $article->tags->isNotEmpty())
      <div class="article-taxonomy" aria-label="Topics">
        @if($article->category)
          <a class="tag-pill is-category" href="{{ $article->category->publicUrl() }}">{{ $article->category->displayLabel() }}</a>
        @endif
        @foreach($article->tags as $tag)
          <a class="tag-pill" href="{{ $tag->publicUrl() }}">{{ $tag->name }}</a>
        @endforeach
      </div>
      @endif
    </div>
    @if($article->featuredMedia)
    <div class="article-hero-image container">
      <img src="{{ $article->featuredMedia->url() }}" alt="{{ $article->featuredMedia->alt ?? $article->title }}" width="1120" height="560">
    </div>
    @endif
  </header>
  <div class="article-layout container">
    <aside class="article-sidebar">
      @if($headings->count())
      <div class="toc-desktop">
        <p class="toc-title">On this page</p>
        <ol class="toc" data-toc>@foreach($headings as $h)<li><a href="#{{ $h['id'] }}">{{ $h['text'] }}</a></li>@endforeach</ol>
      </div>
      @endif
    </aside>
    <div class="article-body content">
      {!! $article->content !!}
    </div>
  </div>
  @if($article->publishedFaqs->count())
    @include('public.partials.faqs', ['faqs' => $article->publishedFaqs, 'faqLd' => $faqLd ?? null])
  @endif
  <section class="related-guides">
    <div class="container">
      <h2>Related guides</h2>
      <div class="card-grid cols-3">
        @foreach($related as $item)
          @include('public.partials.guide-card', ['item' => $item])
        @endforeach
      </div>
    </div>
  </section>
  <div class="container prev-next">
    @if($prev)<a class="prev-next-card" href="{{ $prev->publicUrl() }}"><span>Previous</span><strong>{{ $prev->title }}</strong></a>@endif
    @if($next)<a class="prev-next-card" href="{{ $next->publicUrl() }}"><span>Next</span><strong>{{ $next->title }}</strong></a>@endif
  </div>
</article>
@endsection
