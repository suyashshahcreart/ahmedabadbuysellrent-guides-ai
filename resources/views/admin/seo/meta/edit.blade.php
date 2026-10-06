@extends('admin.layouts.vertical', ['title' => 'Edit SEO Meta'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'Edit SEO Meta',
    'crumbs' => ['SEO Management' => null, 'SEO Meta' => route('administrator.seo.meta.index'), 'Edit' => null],
    'back' => route('administrator.seo.meta.index'),
])
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold">Linked record</div>
            <div class="card-body">
                @if($seoable)
                    <p class="mb-1"><strong>Type</strong><br>{{ class_basename($seoable) }}</p>
                    <p class="mb-1"><strong>Title</strong><br>{{ $seoable->title ?? $seoable->name ?? '—' }}</p>
                    @if(method_exists($seoable, 'publicUrl'))
                        <a href="{{ $seoable->publicUrl() }}" target="_blank">View public page</a>
                    @endif
                @else
                    <p class="text-muted mb-0">Orphaned meta record.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card panel-card border-primary border-top" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'SEO fields'])
            <div class="card-body">
                <form method="POST" action="{{ route('administrator.seo.meta.update', $meta) }}">
                    @csrf @method('PUT')
                    @include('admin.seo._seo_form', ['seoMeta' => $meta, 'embeddedMode' => false])
                    <button class="btn btn-primary" type="submit">Save meta</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
