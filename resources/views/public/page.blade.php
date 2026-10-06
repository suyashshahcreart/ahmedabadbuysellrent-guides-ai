@extends('public.layout')
@section('content')
<section class="hero hero-compact">
  <div class="container">
    <h1>{{ $page->title }}</h1>
    @if($page->excerpt)<p class="lede">{{ $page->excerpt }}</p>@endif
  </div>
</section>
<section class="section">
  <div class="container content prose-page">
    {!! $page->content !!}
  </div>
</section>
@include('public.partials.faqs', ['faqs' => $page->publishedFaqs])
@endsection
