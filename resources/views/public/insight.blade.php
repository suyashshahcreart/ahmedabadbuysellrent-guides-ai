@extends('public.layout')
@section('content')
<section class="hero hero-compact"><div class="container"><p class="eyebrow">{{ $insight->label }}</p><h1>{{ $insight->title }}</h1><p class="lede">{{ $insight->body }}</p></div></section>
<section class="section"><div class="container content prose-page">{!! $insight->content !!}</div></section>
@endsection
