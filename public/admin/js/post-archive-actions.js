/**
 * Single SweetAlert confirm for archive / recover / permanent delete on post lists.
 */
window.confirmPostFormAction = function (form, options) {
    if (!form) {
        return;
    }

    options = options || {};

    var submit = function () {
        form.submit();
    };

    if (typeof Swal === 'undefined') {
        if (window.confirm(options.text || 'Are you sure?')) {
            submit();
        }
        return;
    }

    Swal.fire({
        title: options.title || 'Confirm',
        text: options.text || '',
        icon: options.icon || 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmText || 'Confirm',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: (options.confirmClass || 'btn btn-primary') + ' me-2',
            cancelButton: 'btn btn-outline-secondary',
        },
        buttonsStyling: false,
    }).then(function (result) {
        if (result.isConfirmed) {
            submit();
        }
    });
};

window.initPostArchiveActions = function (tableSelector) {
    if (!window.jQuery) {
        return;
    }

    var $ = window.jQuery;
    var table = $(tableSelector);
    if (!table.length) {
        return;
    }

    table.on('click', '.btn-post-archive', function (event) {
        event.preventDefault();
        var button = $(this);
        var name = button.data('item-name') || 'this item';
        window.confirmPostFormAction(button.closest('form')[0], {
            title: 'Archive post?',
            text: 'Move "' + name + '" to Archived Posts. You can recover it later.',
            confirmText: 'Archive',
            confirmClass: 'btn btn-warning',
            icon: 'question',
        });
    });

    table.on('click', '.btn-post-recover', function (event) {
        event.preventDefault();
        var button = $(this);
        var name = button.data('item-name') || 'this item';
        window.confirmPostFormAction(button.closest('form')[0], {
            title: 'Recover post?',
            text: 'Restore "' + name + '" to Draft status.',
            confirmText: 'Recover',
            confirmClass: 'btn btn-success',
            icon: 'question',
        });
    });

    table.on('click', '.btn-post-delete', function (event) {
        event.preventDefault();
        var button = $(this);
        var name = button.data('item-name') || 'this item';
        window.confirmPostFormAction(button.closest('form')[0], {
            title: 'Delete permanently?',
            text: 'Permanently delete "' + name + '"? This cannot be undone.',
            confirmText: 'Delete',
            confirmClass: 'btn btn-danger',
            icon: 'warning',
        });
    });
};
