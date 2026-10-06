@extends('admin.layouts.vertical', ['title' => 'FAQs'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-1">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">FAQs</li>
                    </ol>
                </nav>
                <h3 class="mb-0">FAQs</h3>
            </div>
            <a href="{{ route('administrator.dashboard') }}" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
        </div>

        @canany(['faqs.create','faq.manage'])
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', [
                'cardTitle' => 'Add global FAQ',
                'cardSubtitle' => 'Shown on the homepage when no page-specific FAQs apply',
            ])
            <div class="card-body">
                <form method="post" action="{{ route('administrator.faqs.store') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Question</label>
                        <input class="form-control" name="question" required maxlength="500" value="{{ old('question') }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Answer</label>
                        <textarea class="form-control" name="answer" rows="1">{{ old('answer') }}</textarea>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Sort</label>
                        <input class="form-control" type="number" name="sort_order" value="{{ old('sort_order', 0) }}">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="submit"><i class="ri-add-line me-1"></i>Add FAQ</button>
                    </div>
                </form>
            </div>
        </div>
        @endcanany

        <div class="card panel-card border-primary border-top" data-panel-card>
            @include('admin.master.partials.panel-card-header-list', [
                'cardTitle' => 'All FAQs',
                'cardSubtitle' => 'Global homepage FAQs and FAQs attached to guides, pages, categories, and localities',
            ])
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 js-datatable" id="faqsTable" data-search="Search FAQs...">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Entity</th>
                                <th>Question</th>
                                <th>Active</th>
                                <th>Created</th>
                                <th class="text-end no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>{{ $row['id'] }}</td>
                                <td>
                                    @if($row['entity'] === 'Global / Homepage')
                                        <span class="badge bg-soft-primary text-primary">{{ $row['entity'] }}</span>
                                    @else
                                        <span class="badge bg-soft-secondary text-secondary">{{ $row['entity'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $row['question'] }}</div>
                                    @if($row['answer'] !== '')
                                        <div class="text-muted small text-truncate" style="max-width: 420px;">{{ \Illuminate\Support\Str::limit(strip_tags($row['answer']), 90) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @canany(['faqs.edit','faq.manage'])
                                    <div class="form-check form-switch">
                                        <input class="form-check-input faq-toggle-active" type="checkbox"
                                               data-id="{{ $row['id'] }}"
                                               @checked($row['status'])>
                                    </div>
                                    @else
                                        @if($row['status'])
                                            <span class="badge bg-soft-success text-success">Active</span>
                                        @else
                                            <span class="badge bg-soft-danger text-danger">Inactive</span>
                                        @endif
                                    @endcanany
                                </td>
                                <td>{{ $row['created_at'] ?: '—' }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        @canany(['faqs.edit','faq.manage'])
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-faq"
                                                data-id="{{ $row['id'] }}"
                                                data-question="{{ rawurlencode($row['question']) }}"
                                                data-answer="{{ rawurlencode($row['answer']) }}"
                                                data-sort="{{ $row['sort_order'] }}"
                                                title="Edit">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        @endcanany
                                        @canany(['faqs.delete','faq.manage'])
                                        <form method="POST" action="{{ route('administrator.faqs.destroy', $row['id']) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-delete-master"
                                                    data-item-name="{{ e($row['question']) }}" title="Delete">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                        @endcanany
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editFaqModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="POST" id="editFaqForm">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit FAQ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Question</label>
                    <input class="form-control" name="question" id="editFaqQuestion" required maxlength="500">
                </div>
                <div class="mb-3">
                    <label class="form-label">Answer</label>
                    <textarea class="form-control" name="answer" id="editFaqAnswer" rows="5"></textarea>
                </div>
                <div class="mb-0" style="max-width: 8rem;">
                    <label class="form-label">Sort</label>
                    <input class="form-control" type="number" name="sort_order" id="editFaqSort">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script-bottom')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const modalEl = document.getElementById('editFaqModal');
    const form = document.getElementById('editFaqForm');
    const modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;

    document.querySelectorAll('.btn-edit-faq').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.action = '{{ url('/administrator/faqs') }}/' + btn.dataset.id;
            document.getElementById('editFaqQuestion').value = decodeURIComponent(btn.dataset.question || '');
            document.getElementById('editFaqAnswer').value = decodeURIComponent(btn.dataset.answer || '');
            document.getElementById('editFaqSort').value = btn.dataset.sort || 0;
            if (modal) modal.show();
        });
    });

    document.querySelectorAll('.faq-toggle-active').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            toggle.disabled = true;
            fetch('{{ url('/administrator/faqs') }}/' + toggle.dataset.id + '/toggle-status', {
                method: 'PATCH',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status: toggle.checked ? 1 : 0 })
            }).then(function (r) { return r.json(); }).then(function (res) {
                toggle.disabled = false;
                if (!res.success) {
                    toggle.checked = !toggle.checked;
                    alert(res.message || 'Could not update status.');
                }
            }).catch(function () {
                toggle.disabled = false;
                toggle.checked = !toggle.checked;
            });
        });
    });
});
</script>
@endsection
