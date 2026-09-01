// ==========================================================
// RAM-YUM — Recruitment Management
// Filtering, sorting, pagination, and job posting view navigation
// ==========================================================

(function () {

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

            if (!pageNumbers) {
                return;
            }

            pageNumbers.innerHTML = '';

            const addButton = page => {

                const button =
                    document.createElement('button');

                button.type = 'button';
                button.className =
                    `page-num${page === currentPage ? ' active' : ''}`;

                button.textContent = page;

                button.addEventListener('click', () => {

                    currentPage = page;
                    applyFilters();

                });

                pageNumbers.appendChild(button);
            };

            const addDots = () => {

                const span =
                    document.createElement('span');

                span.textContent = '...';

                pageNumbers.appendChild(span);
            };


            if (totalPages <= 7) {

                for (let page = 1; page <= totalPages; page++) {
                    addButton(page);
                }

            } else {

                addButton(1);

                if (currentPage > 3) {
                    addDots();
                }

                const start = Math.max(
                    2,
                    currentPage - 1
                );

                const end = Math.min(
                    totalPages - 1,
                    currentPage + 1
                );

                for (let page = start; page <= end; page++) {
                    addButton(page);
                }

                if (currentPage < totalPages - 2) {
                    addDots();
                }

                addButton(totalPages);
            }

            if (prevPageBtn) {
                prevPageBtn.disabled =
                    currentPage === 1;
            }

            if (nextPageBtn) {
                nextPageBtn.disabled =
                    currentPage === totalPages;
            }
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