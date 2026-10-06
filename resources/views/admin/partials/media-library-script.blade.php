@php
    $mediaLibraryScript = public_path('admin/js/media-library.js');
    $mediaLibraryVersion = is_file($mediaLibraryScript) ? (string) filemtime($mediaLibraryScript) : '1';
@endphp
<script src="{{ asset('admin/js/media-library.js') }}?v={{ $mediaLibraryVersion }}"></script>
