(function () {
    function initFormStickySave() {
        var primarySubmit = document.querySelector('.js-form-primary-submit');
        var saveBar = document.getElementById('formStickySaveBar');

        if (!saveBar) {
            return;
        }

        // Contact form settings (and similar pages): always show footer, no scroll gate
        if (saveBar.getAttribute('data-always-visible') === '1') {
            saveBar.classList.remove('d-none');
            saveBar.setAttribute('aria-hidden', 'false');
            document.body.classList.add('form-sticky-save-visible');
            return;
        }

        if (!primarySubmit) {
            return;
        }

        if (typeof IntersectionObserver === 'undefined') {
            saveBar.classList.remove('d-none');
            saveBar.setAttribute('aria-hidden', 'false');
            document.body.classList.add('form-sticky-save-visible');
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            var visible = entries[0] && entries[0].isIntersecting;
            saveBar.classList.toggle('d-none', visible);
            saveBar.setAttribute('aria-hidden', visible ? 'true' : 'false');
            document.body.classList.toggle('form-sticky-save-visible', !visible);
        }, {
            root: null,
            threshold: 0,
            rootMargin: '-10px 0px 0px 0px',
        });

        observer.observe(primarySubmit);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFormStickySave);
    } else {
        initFormStickySave();
    }
})();
