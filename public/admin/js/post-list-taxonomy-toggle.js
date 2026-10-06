/**
 * Expand/collapse long tag lists on WordPress-style post list tables.
 */
(function (window) {
    'use strict';

    function toggleTagList(button) {
        const list = button.closest('.post-wp-taxonomy-list--tags');

        if (!list) {
            return;
        }

        const extra = list.querySelector('.post-wp-taxonomy-extra');
        const moreCount = parseInt(button.getAttribute('data-more-count') || '0', 10);
        const expanded = !list.classList.contains('is-expanded');

        list.classList.toggle('is-expanded', expanded);

        if (extra) {
            extra.classList.toggle('is-collapsed', !expanded);
        }

        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        button.setAttribute('title', expanded ? 'Show fewer tags' : 'Show more tags');
        button.textContent = expanded
            ? 'Show less'
            : ('+' + Math.max(moreCount, 0) + ' more');
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.post-wp-taxonomy-more-toggle');

        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        toggleTagList(button);
    });
})(window);
