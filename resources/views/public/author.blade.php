@extends('public.layout')
@section('content')
<section class="hero hero-compact"><div class="container"><h1>{{ $author->name }}</h1><p class="lede">{{ $author->bio }}</p></div></section>
<section class="section"><div class="container"><div class="card-grid cols-3">@foreach($articles as $item)@include('public.partials.guide-card', ['item'=>$item])@endforeach</div></div></section>
@endsection
