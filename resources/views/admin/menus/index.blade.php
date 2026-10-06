@extends('admin.layouts.vertical')
@section('content')
<h2>Navigation</h2>
@foreach($menus as $menu)
  <div class="card">
    <h3>{{ $menu->name }} ({{ $menu->location }})</h3>
    <ul>@foreach($menu->allItems as $item)<li>{{ $item->label }} — {{ $item->url }}
      <form style="display:inline" method="post" action="/administrator/menu-items/{{ $item->id }}">@csrf @method('DELETE')<button>Remove</button></form>
    </li>@endforeach</ul>
    <form method="post" action="/administrator/menus/{{ $menu->id }}/items">@csrf
      <input name="label" placeholder="Label" required>
      <input name="url" placeholder="/path/" required>
      <button class="btn">Add item</button>
    </form>
  </div>
@endforeach
@endsection
