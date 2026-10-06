@extends('admin.layouts.vertical')
@section('content')
<h2>Profile</h2>
<form method="post" action="/administrator/profile">@csrf
<label>Name</label><input name="name" value="{{ $user->name }}">
<label>Email</label><input name="email" value="{{ $user->email }}">
<label>New password</label><input type="password" name="password">
<label>Confirm</label><input type="password" name="password_confirmation">
<p><button class="btn">Save</button></p>
</form>
@endsection
