@php $selected = collect(old('permissions', $role?->permissions->pluck('name')->all() ?? [])); @endphp
<div class="mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input class="form-control" name="name" value="{{ old('name', $role->name ?? '') }}" required pattern="[A-Za-z0-9_-]+">
    <div class="form-text">Letters, numbers, underscores or hyphens.</div>
</div>
<div class="mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0">Permissions</label>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="select-all-permissions">
            <label class="form-check-label" for="select-all-permissions">Select all</label>
        </div>
    </div>
    <div class="row">
        @foreach($groupedPermissions as $group => $perms)
        <div class="col-md-6 mb-3">
            <div class="border rounded p-3 h-100">
                <h6 class="fw-semibold">{{ $group }}</h6>
                @foreach($perms as $perm)
                    <div class="form-check">
                        <input class="form-check-input js-perm" type="checkbox" name="permissions[]" value="{{ $perm }}" id="p-{{ md5($perm) }}" @checked($selected->contains($perm))>
                        <label class="form-check-label small" for="p-{{ md5($perm) }}">{{ $perm }}</label>
                    </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
<script>
document.getElementById('select-all-permissions')?.addEventListener('change', function () {
    document.querySelectorAll('.js-perm').forEach(c => c.checked = this.checked);
});
</script>
