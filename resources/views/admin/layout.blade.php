<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Admin') · Guides CMS</title>
  <style>
    :root { --navy:#000040; --gold:#c9a42e; --paper:#f7f4ee; --line:#e4dfd2; }
    * { box-sizing: border-box; }
    body { margin:0; font-family: system-ui, sans-serif; background: var(--paper); color:#1a1a2e; }
    a { color: var(--navy); }
    .wrap { display:grid; grid-template-columns: 240px 1fr; min-height:100vh; }
    .side { background: var(--navy); color:#fff; padding:20px 0; }
    .side a { display:block; color:#fff; text-decoration:none; padding:10px 20px; opacity:.9; }
    .side a:hover, .side a.is-on { background: rgb(201 164 46 / 25%); }
    .side h1 { font-size:1rem; margin:0 20px 16px; color: var(--gold); }
    .main { padding: 28px; }
    .btn { display:inline-flex; align-items:center; min-height:40px; padding:0 14px; background:var(--navy); color:#fff; border:0; border-radius:8px; text-decoration:none; cursor:pointer; }
    .btn-ghost { background:#fff; color:var(--navy); border:1px solid var(--line); }
    input, textarea, select { width:100%; padding:8px 10px; border:1px solid var(--line); border-radius:8px; font: inherit; }
    label { display:block; font-weight:600; margin:12px 0 6px; }
    table { width:100%; border-collapse: collapse; background:#fff; }
    th, td { border-bottom:1px solid var(--line); padding:10px; text-align:left; }
    .flash { background:#e8f5e9; padding:10px 14px; border-radius:8px; margin-bottom:16px; }
    .grid2 { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
    .card { background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px; }
    @media (max-width: 800px) { .wrap { grid-template-columns: 1fr; } }
  </style>
  @stack('head')
</head>
<body>
<div class="wrap">
  <aside class="side">
    <h1>Guides CMS</h1>
    @php $nav = [
      '/' => 'Dashboard',
      '/articles' => 'Articles',
      '/pages' => 'Pages',
      '/categories' => 'Categories',
      '/tags' => 'Tags',
      '/authors' => 'Authors',
      '/faqs' => 'FAQs',
      '/localities' => 'Locality Guides',
      '/insights' => 'Market Insights',
      '/media' => 'Media',
      '/seo' => 'SEO',
      '/redirects' => 'Redirects',
      '/menus' => 'Navigation',
      '/settings' => 'Settings',
      '/users' => 'Users',
      '/activity' => 'Activity',
    ]; @endphp
    @foreach($nav as $href => $label)
      <a href="/administrator{{ $href }}" class="{{ request()->is('administrator'.ltrim($href,'/').'*') || ($href==='/' && request()->is('administrator')) ? 'is-on' : '' }}">{{ $label }}</a>
    @endforeach
    <form action="/administrator/logout" method="post" style="padding:20px">
      @csrf
      <button class="btn btn-ghost" type="submit">Log out</button>
    </form>
  </aside>
  <main class="main">
    @if(session('status'))<p class="flash">{{ session('status') }}</p>@endif
    @yield('content')
  </main>
</div>
</body>
</html>
