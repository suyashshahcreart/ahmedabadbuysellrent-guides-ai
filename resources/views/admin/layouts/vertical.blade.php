<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.layouts.partials.title-meta', ['title' => $title ?? 'Admin'])
    @include('admin.layouts.partials.head-css')
</head>
<body>
<div class="wrapper">
    @include('admin.layouts.partials.topbar')
    @include('admin.layouts.partials.main-nav')
    <div class="page-content">
        <div class="container-fluid">
            @include('admin.layouts.partials.page-title')
            @include('admin.layouts.partials.alerts')
            @yield('content')
        </div>
        @include('admin.layouts.partials.footer')
        @include('admin.components.media-library-modal', [
            'modalId' => 'editorContentMediaModal',
            'modalTitle' => 'Insert Media',
            'setButtonText' => 'Insert',
        ])
        @stack('modals')
        @yield('modal')
    </div>
</div>
@include('admin.layouts.partials.right-sidebar')
@include('admin.layouts.partials.footer-scripts')
</body>
</html>
