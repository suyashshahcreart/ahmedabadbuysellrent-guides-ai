@extends('public.layout')
@php $meta = $meta ?? ['title'=>'Page not found | Ahmedabad Guides','description'=>'','canonical'=>url('/404'),'robots'=>'noindex']; @endphp
@section('content')
<section class="hero hero-compact"><div class="container"><h1>Page not found</h1><p class="lede">Try search or browse all guides.</p><a class="btn btn-navy" href="/guides/">All guides</a></div></section>
@endsection
