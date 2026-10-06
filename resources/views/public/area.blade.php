@extends('public.layout')
@section('content')
<section class="hero hero-compact">
  <div class="container">
    <nav class="crumbs"><ol><li><a href="/">Home</a></li><li><a href="/areas/">Areas</a></li><li>{{ $locality->name }}</li></ol></nav>
    <p class="eyebrow">Locality guide</p>
    <h1>{{ $locality->name }}</h1>
    <p class="lede">{{ $locality->summary }}</p>
    <p class="locality-tags">@foreach($locality->tags ?? [] as $t)<span>{{ $t }}</span>@endforeach</p>
  </div>
</section>
@if($locality->featuredMedia)
<div class="container article-hero-image">
  <img src="{{ $locality->featuredMedia->url() }}" alt="{{ $locality->featuredMedia->alt ?? $locality->name }}" width="1120" height="480">
</div>
@endif
<section class="section">
  <div class="container article-layout">
    <div class="article-body content">
      {!! $locality->overview !!}
      @foreach(['property_market'=>'Property market','rental_information'=>'Rental information','connectivity'=>'Connectivity','schools'=>'Schools','hospitals'=>'Hospitals','shopping'=>'Shopping','lifestyle'=>'Lifestyle','investment_potential'=>'Investment potential'] as $field=>$label)
        @if($locality->{$field})
          <h2>{{ $label }}</h2>
          {!! $locality->{$field} !!}
        @endif
      @endforeach
    </div>
  </div>
</section>
@include('public.partials.faqs', ['faqs' => $locality->publishedFaqs])
@endsection
