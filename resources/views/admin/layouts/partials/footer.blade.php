@php
    $site = $site ?? \App\Support\SiteBranding::forView();
@endphp
<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 text-center">
                <script>document.write(new Date().getFullYear())</script> &copy; {{ $site['name'] }}
            </div>
        </div>
    </div>
</footer>
