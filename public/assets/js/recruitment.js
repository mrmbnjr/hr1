// ==========================================================
// RAM-YUM — Recruitment Management
// Filtering, sorting, pagination, and job posting view navigation
// ==========================================================

(function () {

    const departmentSelect =
        document.getElementById('department');

    const positionSelect =
        document.getElementById('position');

    if (departmentSelect && positionSelect) {
        const positionOptions = Array.from(positionSelect.options)
            .filter(option => option.dataset.department);

        const existingPlaceholder = Array.from(positionSelect.options)
            .find(option => option.value === '' && !option.dataset.department && !option.disabled);

        const positionPlaceholder = existingPlaceholder
            ? existingPlaceholder.cloneNode(true)
            : new Option('Select Position', '', true, true);

        function filterPositions(preserveSelection) {
            const selectedPosition = preserveSelection
                ? positionSelect.value
                : '';

            const matchingPositions = positionOptions.filter(
                option => option.dataset.department === departmentSelect.value
            );

            positionSelect.replaceChildren(positionPlaceholder.cloneNode(true));

            matchingPositions.forEach(option => {
                positionSelect.appendChild(option.cloneNode(true));
            });

            if (matchingPositions.length === 0 && departmentSelect.value) {
                const emptyOption = new Option(
                    'No positions available',
                    '',
                    false,
                    false
                );
                emptyOption.disabled = true;
                positionSelect.appendChild(emptyOption);
            }

            positionSelect.value = matchingPositions.some(
                option => option.value === selectedPosition
            ) ? selectedPosition : '';
        }

        departmentSelect.addEventListener('change', () => {
            filterPositions(false);
        });

        filterPositions(true);
    }

    // ======================================================
    // RECRUITMENT JOBS TABLE
    // ======================================================

    const table = document.querySelector('.data-table');

    if (table) {

        const rows = Array.from(
            table.querySelectorAll('tbody tr.job-row')
        );

        const jobFilter =
            document.getElementById('jobFilter');

        const departmentFilter =
            document.getElementById('departmentFilter');

        const employmentFilter =
            document.getElementById('employmentFilter');

        const statusFilter =
            document.getElementById('statusFilter');

        const resultsCount =
            document.getElementById('resultsCount');

        const pageNumbers =
            document.getElementById('pageNumbers');

        const prevPageBtn =
            document.getElementById('prevPage');

        const nextPageBtn =
            document.getElementById('nextPage');

        const PAGE_SIZE = 8;

        let currentPage = 1;


        // ==================================================
        // FILTER
        // ==================================================

        function getVisibleRows() {

            const job =
                jobFilter?.value ?? '';

            const department =
                departmentFilter?.value ?? '';

            const employmentType =
                employmentFilter?.value ?? '';

            const status =
                statusFilter?.value ?? '';

            const visible = rows.filter(row => {

                const matchesJob =
                    job === '' ||
                    row.dataset.job.includes(job.toLowerCase());

                const matchesDepartment =
                    department === '' ||
                    row.dataset.department === department;

                const matchesEmploymentType =
                    employmentType === '' ||
                    row.dataset.employmentType === employmentType;

                const matchesStatus =
                    status === '' ||
                    row.dataset.status === status;

                return (
                    matchesJob &&
                    matchesDepartment &&
                    matchesEmploymentType &&
                    matchesStatus
                );
            });

            return visible;
        }


        // ==================================================
        // APPLY TABLE STATE
        // ==================================================

        function applyFilters() {

            const visible = getVisibleRows();

            const totalPages = Math.max(
                1,
                Math.ceil(visible.length / PAGE_SIZE)
            );

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const start =
                (currentPage - 1) * PAGE_SIZE;

            const pageRows = visible.slice(
                start,
                start + PAGE_SIZE
            );

            const tbody = table.querySelector('tbody');

            visible.forEach(row => {
                tbody.appendChild(row);
            });

            rows.forEach(row => {
                row.style.display = 'none';
            });

            pageRows.forEach(row => {
                row.style.display = '';
            });

            updateResultsCount(
                visible.length,
                start
            );

            renderPagination(totalPages);
        }


        // ==================================================
        // RESULTS COUNT
        // ==================================================

        function updateResultsCount(totalRows, start) {

            if (!resultsCount) {
                return;
            }

            if (totalRows === 0) {
                resultsCount.textContent = 'No job postings found';
                return;
            }

            const startNumber = start + 1;

            const endNumber = Math.min(
                start + PAGE_SIZE,
                totalRows
            );

            resultsCount.textContent =
                `Showing ${startNumber}-${endNumber} of ${totalRows} postings`;
        }


        // ==================================================
        // PAGINATION
        // ==================================================

        function renderPagination(totalPages) {
            window.renderTablePagination({
                pageNumbers,
                prevPageBtn,
                nextPageBtn,
                totalPages,
                currentPage,
                onSelect: (page) => {
                    currentPage = page;
                    applyFilters();
                },
            });
        }


        // ==================================================
        // PAGINATION EVENTS
        // ==================================================

        if (prevPageBtn) {

            prevPageBtn.addEventListener('click', () => {

                if (currentPage <= 1) {
                    return;
                }

                currentPage--;
                applyFilters();

            });
        }

        if (nextPageBtn) {

            nextPageBtn.addEventListener('click', () => {

                const visibleRows = getVisibleRows();

                const totalPages = Math.max(
                    1,
                    Math.ceil(visibleRows.length / PAGE_SIZE)
                );

                if (currentPage >= totalPages) {
                    return;
                }

                currentPage++;
                applyFilters();

            });
        }


        // ==================================================
        // FILTER EVENTS
        // ==================================================

        [
            jobFilter,
            departmentFilter,
            employmentFilter,
            statusFilter
        ].forEach(filter => {

            if (!filter) {
                return;
            }

            filter.addEventListener('change', () => {

                currentPage = 1;
                applyFilters();

            });
        });


        // ==================================================
        // INITIALIZE TABLE
        // ==================================================

        applyFilters();
    }

})();


// ==========================================================
// APPLICATION LINK COPY
// ==========================================================

function copyApplicationLink(path) {

    const fullUrl =
        window.location.origin + path;

    navigator.clipboard.writeText(fullUrl)
        .then(() => {

            alert('Application link copied!');

        })
        .catch(() => {

            prompt(
                'Copy this application link:',
                fullUrl
            );

        });
}

// Keep the candidate-facing card useful while the posting is being written.
(function initPostingPreview() {
    const form = document.querySelector('.posting-page .form-card');
    const preview = document.querySelector('.posting-preview');

    if (!form || !preview) {
        return;
    }

    const title = form.querySelector('#postingTitle');
    const department = form.querySelector('#department');
    const employment = form.querySelector('#employmentType');
    const vacancies = form.querySelector('#vacancies');
    const deadline = form.querySelector('#deadline');
    const description = form.querySelector('#description');
    const requirements = form.querySelector('#requirements');
    const output = name => preview.querySelector(`[data-preview="${name}"]`);

    const update = () => {
        output('title').textContent = title?.value.trim() || 'Untitled position';
        output('department').textContent = department?.selectedOptions[0]?.textContent.trim() || 'Select a department';
        output('employment').textContent = employment?.value || 'Full-Time';
        const count = Number(vacancies?.value || 1);
        output('vacancies').textContent = `${count} ${count === 1 ? 'vacancy' : 'vacancies'}`;
        output('deadline').textContent = deadline?.value || 'Choose a deadline';

        const checks = {
            title: title?.value.trim().length >= 3,
            description: description?.value.trim().length >= 3,
            requirements: requirements?.value.trim().length >= 3,
            deadline: Boolean(deadline?.value)
        };

        Object.entries(checks).forEach(([name, complete]) => {
            const item = preview.querySelector(`[data-check="${name}"]`);
            item?.classList.toggle('is-complete', complete);
            const icon = item?.querySelector('i');
            if (icon) {
                icon.className = complete ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle';
            }
        });
    };

    [title, department, employment, vacancies, deadline, description, requirements].forEach(field => {
        field?.addEventListener('input', update);
        field?.addEventListener('change', update);
    });

    update();
})();

// ==========================================================
// ROW ACTION DROPDOWNS (click to toggle)
// ==========================================================

document.addEventListener('click', function (e) {

    const btn = e.target.closest('.dropdown-btn');

    // Close all other open dropdowns
    document.querySelectorAll('.dropdown.open').forEach(dropdown => {
        if (!btn || dropdown !== btn.closest('.dropdown')) {
            dropdown.classList.remove('open');
        }
    });

    // Toggle the clicked one
    if (btn) {
        btn.closest('.dropdown').classList.toggle('open');
    }
});

// Close with the Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.dropdown.open')
            .forEach(d => d.classList.remove('open'));
    }
});