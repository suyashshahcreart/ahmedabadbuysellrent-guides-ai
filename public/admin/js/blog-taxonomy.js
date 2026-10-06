/**
 * Guide Tags UI (Proppik-style chips + suggest)
 * Config: window.blogTaxonomyConfig = { tagSuggestUrl, csrfToken, initialTags:[{id,name}] }
 */
(function () {
    'use strict';

    function boot() {
        initTags(window.blogTaxonomyConfig || {});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    function initTags(cfg) {
        var tagInput = document.getElementById('tagInput');
        var tagSuggestions = document.getElementById('tagSuggestions');
        var selectedTagsWrap = document.getElementById('selectedTagsWrap');
        var mostUsedList = document.getElementById('mostUsedTagsList');
        var toggleMostUsed = document.getElementById('toggleMostUsed');
        if (!tagInput || !selectedTagsWrap) return;

        var selectedTags = {};
        var suggestTimeout = null;

        if (Array.isArray(cfg.initialTags)) {
            cfg.initialTags.forEach(function (t) {
                addTag(t.id || null, t.name);
            });
        }

        function addTag(id, name) {
            name = (name || '').trim();
            if (!name) return;
            var key = name.toLowerCase();
            if (selectedTags[key]) return;
            selectedTags[key] = { id: id, name: name };
            renderTagBadge(id, name, key);
        }

        function removeTag(key) {
            delete selectedTags[key];
            var el = selectedTagsWrap.querySelector('[data-tag-key="' + CSS.escape(key) + '"]');
            if (el) el.remove();
        }

        function renderTagBadge(id, name, key) {
            var span = document.createElement('span');
            span.className = 'badge rounded-pill text-bg-primary d-inline-flex align-items-center gap-1 me-1 mb-1 px-2 py-1';
            span.dataset.tagKey = key;

            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = id ? 'tag_ids[]' : 'tag_names[]';
            inp.value = id ? id : name;

            var txt = document.createTextNode(name + ' ');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn-close btn-close-white';
            btn.style.cssText = 'font-size:.55rem;';
            btn.setAttribute('aria-label', 'Remove ' + name);
            btn.addEventListener('click', function () { removeTag(key); });

            span.appendChild(txt);
            span.appendChild(btn);
            span.appendChild(inp);
            selectedTagsWrap.appendChild(span);
        }

        tagInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                this.value.split(',').forEach(function (p) { addTag(null, p); });
                this.value = '';
                hideSuggestions();
            }
        });

        tagInput.addEventListener('input', function () {
            var q = this.value.trim();
            clearTimeout(suggestTimeout);
            if (q.length < 1) { hideSuggestions(); return; }
            suggestTimeout = setTimeout(function () { fetchSuggestions(q); }, 280);
        });

        document.addEventListener('click', function (e) {
            if (!tagInput.contains(e.target) && !tagSuggestions.contains(e.target)) {
                hideSuggestions();
            }
        });

        tagSuggestions.addEventListener('click', function (e) {
            var item = e.target.closest('[data-suggest-id]');
            if (item) {
                addTag(item.dataset.suggestId, item.dataset.suggestName);
                tagInput.value = '';
                hideSuggestions();
            }
        });

        function fetchSuggestions(q) {
            if (!cfg.tagSuggestUrl) return;
            fetch(cfg.tagSuggestUrl + '?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (tags) {
                    tagSuggestions.innerHTML = '';
                    if (!Array.isArray(tags) || !tags.length) { hideSuggestions(); return; }
                    tags.forEach(function (t) {
                        if (selectedTags[(t.name || '').toLowerCase()]) return;
                        var a = document.createElement('button');
                        a.type = 'button';
                        a.className = 'dropdown-item small py-1 text-start w-100 border-0 bg-transparent';
                        a.dataset.suggestId = t.id;
                        a.dataset.suggestName = t.name;
                        a.textContent = t.name;
                        tagSuggestions.appendChild(a);
                    });
                    tagSuggestions.classList.remove('d-none');
                })
                .catch(function () { hideSuggestions(); });
        }

        function hideSuggestions() {
            tagSuggestions.classList.add('d-none');
            tagSuggestions.innerHTML = '';
        }

        if (toggleMostUsed && mostUsedList) {
            toggleMostUsed.addEventListener('click', function (e) {
                e.preventDefault();
                mostUsedList.classList.toggle('d-none');
            });
            mostUsedList.addEventListener('click', function (e) {
                var chip = e.target.closest('[data-tag-id], [data-suggest-id]');
                if (!chip) return;
                addTag(chip.dataset.tagId || chip.dataset.suggestId, chip.dataset.tagName || chip.dataset.suggestName);
            });
        }
    }
})();
