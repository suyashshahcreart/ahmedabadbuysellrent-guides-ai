<div class="row">
    <div class="col-lg-6 mb-3">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input class="form-control" name="name" value="{{ old('name', $user?->name) }}" required minlength="2">
    </div>
    <div class="col-lg-6 mb-3">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control" name="email" value="{{ old('email', $user?->email) }}" required>
    </div>
    <div class="col-lg-6 mb-3">
        <label class="form-label">Password @if(!$user)<span class="text-danger">*</span>@endif</label>
        <input type="password" class="form-control" name="password" @if(!$user) required @endif minlength="8">
        @if($user)<div class="form-text">Leave blank to keep the current password.</div>@endif
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Roles</label>
        <div class="d-flex flex-wrap gap-3">
            @foreach($roles as $role)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" id="role-{{ $role->id }}"
                           @checked(in_array($role->name, old('roles', $user?->roles->pluck('name')->all() ?? []), true))>
                    <label class="form-check-label" for="role-{{ $role->id }}">{{ $role->name }}</label>
                </div>
            @endforeach
        </div>
    </div>
</div>
