<script>
window.appConfig = window.appConfig || { baseUrl: @json(rtrim((string) config('app.url'), '/')) };
window.adminBasePath = 'administrator';
window.getAdminRouteBase = function () {
    const appBase = (window.appConfig && window.appConfig.baseUrl)
        ? String(window.appConfig.baseUrl).replace(/\/$/, '')
        : String(window.location.origin).replace(/\/$/, '');
    return `${appBase}/administrator`;
};
</script>
@yield('script')
@vite(['resources/js/admin-app.js','resources/js/admin-layout.js'])
@include('admin.partials.media-library-script')
@stack('script-bottom')
@yield('script-bottom')
@stack('scripts')
