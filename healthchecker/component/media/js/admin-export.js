/**
 * Health Checker Component - Export Page JavaScript
 *
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

(function(window, document) {
    'use strict';

    const container = document.getElementById('healthchecker-export');
    if (!container) return;

    const metadataUrl = container.dataset.metadataUrl;
    const exportUrls = {
        json: container.dataset.exportJsonUrl,
        htmlexport: container.dataset.exportHtmlUrl,
        markdown: container.dataset.exportMarkdownUrl
    };

    const form = document.getElementById('exportForm');
    const categoryFiltersEl = document.getElementById('categoryFilters');
    const checkFiltersEl = document.getElementById('checkFilters');
    const exportButton = document.getElementById('exportButton');
    const exportSummary = document.getElementById('exportSummary');

    let metadata = null;

    /**
     * Safely parse a JSON response
     */
    async function parseJsonResponse(response) {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error(Joomla.Text._('COM_HEALTHCHECKER_ERROR') || 'Error loading data');
        }
    }

    /**
     * Load metadata from AJAX endpoint
     */
    async function loadMetadata() {
        try {
            const response = await fetch(metadataUrl);
            const json = await parseJsonResponse(response);

            if (!json.success) {
                throw new Error(json.message || 'Failed to load metadata');
            }

            metadata = json.data;
            renderCategoryFilters();
            renderCheckFilters();
            updateSummary();
            exportButton.disabled = false;
        } catch (error) {
            categoryFiltersEl.innerHTML = '<div class="alert alert-danger">' +
                (Joomla.Text._('COM_HEALTHCHECKER_EXPORT_LOADING_ERROR') || 'Failed to load check data.') +
                '</div>';
            checkFiltersEl.innerHTML = '';
        }
    }

    /**
     * Render category checkboxes
     */
    function renderCategoryFilters() {
        if (!metadata || !metadata.categories) return;

        // Categories come as an object keyed by slug, sorted by sortOrder
        const categories = Object.values(metadata.categories)
            .sort(function(a, b) { return (a.sortOrder || 0) - (b.sortOrder || 0); });
        let html = '<div class="export-category-grid">';

        for (const cat of categories) {
            const slug = cat.slug;
            const label = Joomla.Text._(cat.label) || cat.label;
            const checkCount = metadata.checks.filter(c => c.category === slug).length;

            html += '<label class="export-category-item">' +
                '<input type="checkbox" name="export_categories[]" value="' + slug + '" checked> ' +
                '<span class="export-category-label">' + escapeHtml(label) + '</span> ' +
                '<span class="badge bg-secondary">' + checkCount + '</span>' +
                '</label>';
        }

        html += '</div>';
        categoryFiltersEl.innerHTML = html;

        // Bind change events
        categoryFiltersEl.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
            cb.addEventListener('change', function() {
                updateCheckVisibility();
                updateSummary();
            });
        });
    }

    /**
     * Render per-check toggles grouped by category
     */
    function renderCheckFilters() {
        if (!metadata || !metadata.checks) return;

        const categories = Object.values(metadata.categories)
            .sort(function(a, b) { return (a.sortOrder || 0) - (b.sortOrder || 0); });
        let html = '';

        for (const cat of categories) {
            const slug = cat.slug;
            const label = Joomla.Text._(cat.label) || cat.label;
            const checks = metadata.checks.filter(c => c.category === slug);

            if (checks.length === 0) continue;

            html += '<div class="export-check-category" data-category="' + slug + '">' +
                '<div class="export-check-category-header-wrapper">' +
                '<div class="export-check-category-header-toggle" data-bs-toggle="collapse" data-bs-target="#checks-' + slug + '">' +
                '<span class="icon-chevron-down"></span> ' +
                escapeHtml(label) +
                ' <span class="badge bg-secondary">' + checks.length + '</span>' +
                '</div>' +
                '<span class="export-check-category-actions">' +
                '<button type="button" class="btn btn-sm btn-link check-select-all" data-category="' + slug + '">' +
                (Joomla.Text._('COM_HEALTHCHECKER_EXPORT_SELECT_ALL') || 'All') +
                '</button>' +
                '<button type="button" class="btn btn-sm btn-link check-select-none" data-category="' + slug + '">' +
                (Joomla.Text._('COM_HEALTHCHECKER_EXPORT_SELECT_NONE') || 'None') +
                '</button>' +
                '</span>' +
                '</div>' +
                '<div class="collapse" id="checks-' + slug + '">' +
                '<div class="export-check-list">';

            for (const check of checks) {
                const checkLabel = check.title || check.slug;
                html += '<label class="export-check-item">' +
                    '<input type="checkbox" name="export_checks[]" value="' + check.slug + '" ' +
                    'data-category="' + slug + '" checked> ' +
                    escapeHtml(checkLabel) +
                    '</label>';
            }

            html += '</div></div></div>';
        }

        checkFiltersEl.innerHTML = html;

        // Bind per-category select all/none
        checkFiltersEl.querySelectorAll('.check-select-all').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const cat = this.dataset.category;
                checkFiltersEl.querySelectorAll('input[data-category="' + cat + '"]')
                    .forEach(function(cb) { cb.checked = true; });
                updateSummary();
            });
        });

        checkFiltersEl.querySelectorAll('.check-select-none').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const cat = this.dataset.category;
                checkFiltersEl.querySelectorAll('input[data-category="' + cat + '"]')
                    .forEach(function(cb) { cb.checked = false; });
                updateSummary();
            });
        });

        // Bind individual check changes
        checkFiltersEl.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
            cb.addEventListener('change', updateSummary);
        });
    }

    /**
     * Show/hide per-check groups based on category selection
     */
    function updateCheckVisibility() {
        if (!checkFiltersEl) return;

        const checkedCategories = new Set();
        categoryFiltersEl.querySelectorAll('input[type="checkbox"]:checked').forEach(function(cb) {
            checkedCategories.add(cb.value);
        });

        checkFiltersEl.querySelectorAll('.export-check-category').forEach(function(group) {
            const cat = group.dataset.category;
            group.style.display = checkedCategories.has(cat) ? '' : 'none';

            // Uncheck all checks in hidden categories
            if (!checkedCategories.has(cat)) {
                group.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
                    cb.checked = false;
                });
            } else {
                group.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
                    cb.checked = true;
                });
            }
        });
    }

    /**
     * Update the export summary text
     */
    function updateSummary() {
        if (!metadata) return;

        const selectedCategories = categoryFiltersEl.querySelectorAll('input[type="checkbox"]:checked').length;
        const totalCategories = categoryFiltersEl.querySelectorAll('input[type="checkbox"]').length;
        const selectedChecks = checkFiltersEl.querySelectorAll('input[type="checkbox"]:checked').length;
        const totalChecks = checkFiltersEl.querySelectorAll('input[type="checkbox"]').length;
        const format = form.querySelector('input[name="export_format"]:checked').value;
        const statusFilter = form.querySelector('input[name="export_status"]:checked').value;

        const formatLabels = {
            htmlexport: 'HTML',
            json: 'JSON',
            markdown: 'Markdown'
        };

        let summary = formatLabels[format] + ' format';
        if (statusFilter === 'issues') {
            summary += ', issues only';
        }
        summary += '<br>' + selectedCategories + '/' + totalCategories + ' categories';
        summary += ', ' + selectedChecks + '/' + totalChecks + ' checks';

        exportSummary.innerHTML = summary;
    }

    /**
     * Handle form submission via POST to avoid 414 Request-URI Too Long errors
     * when many checks/categories are selected (GitHub issue #58)
     */
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const format = form.querySelector('input[name="export_format"]:checked').value;
        const baseUrl = exportUrls[format];

        if (!baseUrl) return;

        // Build a temporary POST form to submit filter data in the request body
        // instead of query string params, which can exceed server URI length limits
        const postForm = document.createElement('form');
        postForm.method = 'POST';
        postForm.action = baseUrl;
        postForm.style.display = 'none';

        function addHidden(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            postForm.appendChild(input);
        }

        addHidden('export_filtered', '1');
        addHidden('export_status', form.querySelector('input[name="export_status"]:checked').value);

        form.querySelectorAll('input[name="export_categories[]"]:checked').forEach(function(cb) {
            addHidden('export_categories[]', cb.value);
        });

        form.querySelectorAll('input[name="export_checks[]"]:checked').forEach(function(cb) {
            addHidden('export_checks[]', cb.value);
        });

        document.body.appendChild(postForm);
        postForm.submit();
        postForm.remove();
    });

    // Format card selection
    document.querySelectorAll('.export-format-card input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.export-format-card').forEach(function(card) {
                card.classList.remove('active');
            });
            this.closest('.export-format-card').classList.add('active');
            updateSummary();
        });
    });

    // Status filter change
    document.querySelectorAll('input[name="export_status"]').forEach(function(radio) {
        radio.addEventListener('change', updateSummary);
    });

    // Select All/None category buttons
    document.getElementById('selectAllCategories').addEventListener('click', function() {
        categoryFiltersEl.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
            cb.checked = true;
        });
        updateCheckVisibility();
        updateSummary();
    });

    document.getElementById('selectNoCategories').addEventListener('click', function() {
        categoryFiltersEl.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
            cb.checked = false;
        });
        updateCheckVisibility();
        updateSummary();
    });

    /**
     * Escape HTML special characters
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    // Load metadata on page ready
    loadMetadata();

})(window, document);
