@extends('admin.layouts.vertical', ['title' => 'AI Credits'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'AI Credit Management',
    'subheading' => 'Manage monthly AI credit limits for each user.',
    'crumbs' => ['System' => null, 'AI Credits' => null],
    'actions' => auth()->user()->can('ai.credits.manage')
        ? '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCreditModal"><i class="ri-add-circle-line me-1"></i>Add AI Credit</button>'
        : '',
])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'Users & Credits',
        'cardSubtitle' => 'Each AI action costs 1 credit. Credits reset each calendar month.',
    ])
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge bg-soft-info text-info">Credits reset each calendar month</span>
            <span class="badge bg-soft-secondary text-secondary">Admin &amp; superAdmin are excluded from credit limits</span>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Filter by role</label>
                <select class="form-select form-select-sm js-dt-filter" data-table="#creditsTable" data-column="3">
                    <option value="">All roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 js-datatable" id="creditsTable" data-search="Search credits...">
                <thead class="table-light">
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Role(s)</th>
                        <th>Monthly Limit</th><th>Used This Month</th><th>Remaining</th>
                        <th class="text-end no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    @php
                        $used = (int) ($user->credits_used_this_month ?? 0);
                        $left = (int) $user->ai_monthly_credit - $used;
                        $color = $left <= 0 ? 'danger' : ($left < 5 ? 'warning' : 'success');
                    @endphp
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>@foreach($user->roles as $role)<span class="badge bg-soft-primary text-primary">{{ $role->name }}</span> @endforeach</td>
                        <td>{{ $user->ai_monthly_credit }}</td>
                        <td>{{ $used }}</td>
                        <td><span class="badge bg-{{ $color }}">{{ $left }} left</span></td>
                        <td class="text-end">
                            @if($user->requiresAiCreditCheck())
                                @can('ai.credits.manage')
                                <button type="button" class="btn btn-sm btn-outline-primary me-1 js-add-credit"
                                    data-bs-toggle="modal" data-bs-target="#addMoreCreditModal"
                                    data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}"
                                    data-credit="{{ $user->ai_monthly_credit }}">
                                    <i class="ri-add-circle-line"></i>
                                </button>
                                @endcan
                            @else
                                <span class="btn btn-sm btn-light border-0 text-muted me-1 disabled pe-none" title="Administrators are excluded from AI credit limits"><i class="ri-lock-line"></i></span>
                            @endif
                            <a href="{{ route('administrator.ai.credits.history', $user) }}" class="btn btn-sm btn-outline-secondary me-1"><i class="ri-history-line"></i></a>
                            <a href="{{ route('administrator.ai.credits.usage', $user) }}" class="btn btn-sm btn-outline-info"><i class="ri-robot-line"></i></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createCreditModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Add AI Credit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Select User</label>
                <select id="selectUser" class="form-select"><option value="">Select user</option></select>
            </div>
            <div class="mb-3">
                <label class="form-label">Monthly Credit Limit</label>
                <input type="number" id="createCreditAmount" class="form-control" min="0" max="99999">
            </div>
            <div class="mb-0">
                <label class="form-label">Note (optional)</label>
                <textarea id="createCreditNote" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="submitCreateCredit">Add Credit</button>
        </div>
    </div></div>
</div>
<div class="modal fade" id="addMoreCreditModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Set AI Credit Limit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p class="text-muted">Set the monthly AI credit limit for <strong id="selectedUserName"></strong>.</p>
            <input type="hidden" id="selectedUserId">
            <div class="mb-3">
                <label class="form-label">Monthly Credit Limit</label>
                <input type="number" id="additionalCredit" class="form-control" min="0" max="99999">
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="forcedOverride">
                <label class="form-check-label" for="forcedOverride">Forced override</label>
            </div>
            <textarea id="rowCreditNote" class="form-control" rows="2" placeholder="Note"></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="submitRowCredit">Save</button>
        </div>
    </div></div>
</div>
@endsection
@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const updateUrl = @json(route('administrator.ai.credits.update'));
    function postCredit(payload) {
        return fetch(updateUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
            body: JSON.stringify(payload)
        }).then(r => r.json()).then(d => { if (!d.success) throw new Error(d.message || 'Failed'); location.reload(); });
    }
    document.getElementById('createCreditModal')?.addEventListener('show.bs.modal', function () {
        fetch(@json(route('administrator.ai.credits.users')))
            .then(r => r.json())
            .then(users => {
                const sel = document.getElementById('selectUser');
                sel.innerHTML = '<option value="">Select user</option>';
                users.forEach(u => { const o = document.createElement('option'); o.value = u.id; o.textContent = u.name + ' (' + u.email + ')'; sel.appendChild(o); });
            });
    });
    document.getElementById('submitCreateCredit')?.addEventListener('click', function () {
        postCredit({
            user_id: document.getElementById('selectUser').value,
            credit_amount: document.getElementById('createCreditAmount').value,
            note: document.getElementById('createCreditNote').value,
            forced: false
        }).catch(e => alert(e.message));
    });
    document.querySelectorAll('.js-add-credit').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('selectedUserId').value = this.dataset.userId;
            document.getElementById('selectedUserName').textContent = this.dataset.userName;
            document.getElementById('additionalCredit').value = this.dataset.credit;
        });
    });
    document.getElementById('submitRowCredit')?.addEventListener('click', function () {
        postCredit({
            user_id: document.getElementById('selectedUserId').value,
            credit_amount: document.getElementById('additionalCredit').value,
            note: document.getElementById('rowCreditNote').value,
            forced: document.getElementById('forcedOverride').checked
        }).catch(e => alert(e.message));
    });
});
</script>
@endsection
