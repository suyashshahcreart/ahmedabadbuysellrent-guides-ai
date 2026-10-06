@extends('admin.layouts.auth', ['title' => 'Sign in'])

@section('content')
<div class="col-xl-5">
    <div class="card auth-card">
        <div class="card-body px-3 py-5">
            <div class="text-center mb-3">
                <x-auth-brand-logo :home-url="url('/')" wrapper-class="mx-auto mb-0 text-center auth-logo" />
            </div>
            <h2 class="fw-bold text-uppercase text-center fs-18">Log In</h2>
            <p class="text-muted text-center mt-1 mb-4">Enter your email and password to access the admin panel.</p>
            <div class="px-4">
                <form method="POST" action="{{ route('administrator.login') }}" class="authentication-form">
                    @csrf
                    @foreach ($errors->all() as $error)
                        <p class="text-danger mb-3">{{ $error }}</p>
                    @endforeach
                    <div class="mb-3">
                        <label class="form-label" for="login-email">Email</label>
                        <input type="email" id="login-email" name="email" value="{{ old('email') }}"
                               class="form-control bg-light bg-opacity-50 border-light py-2"
                               placeholder="Enter your email" autocomplete="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="login-password">Password</label>
                        <input type="password" id="login-password"
                               class="form-control bg-light bg-opacity-50 border-light py-2"
                               placeholder="Enter your password" name="password" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="checkbox-signin" name="remember" value="1">
                            <label class="form-check-label" for="checkbox-signin">Remember me</label>
                        </div>
                    </div>
                    <div class="mb-1 text-center d-grid">
                        <button class="btn btn-danger py-2 fw-medium" type="submit">Sign In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
