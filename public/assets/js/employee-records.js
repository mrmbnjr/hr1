// ==========================================================
// RAM-YUM — Employee Records
// Filtering, sorting, pagination, and employee view navigation
// ==========================================================

(function () {

    // ======================================================
    // EMPLOYEE RECORDS TABLE
    // ======================================================

    const table = document.getElementById('employeeRecordsTable');

    if (table) {

        const rows = Array.from(
            table.querySelectorAll('tbody tr.employee-row')
        );

        const departmentFilter =
            document.getElementById('departmentFilter');

        const employmentTypeFilter =
            document.getElementById('employmentTypeFilter');

        const statusFilter =
            document.getElementById('statusFilter');

        const sortFilter =
            document.getElementById('sortFilter');

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
        // FILTER + SORT
        // ==================================================

        function getVisibleRows() {

            const department =
                departmentFilter?.value ?? 'All';

            const employmentType =
                employmentTypeFilter?.value ?? 'All';

            const status =
                statusFilter?.value ?? 'All';

            const sort =
                sortFilter?.value ?? 'newest';

            const visible = rows.filter(row => {

                const matchesDepartment =
                    department === 'All' ||
                    row.dataset.department === department;

                const matchesEmploymentType =
                    employmentType === 'All' ||
                    row.dataset.employmentType === employmentType;

                const matchesStatus =
                    status === 'All' ||
                    row.dataset.status === status;

                return (
                    matchesDepartment &&
                    matchesEmploymentType &&
                    matchesStatus
                );
            });

            visible.sort((a, b) => {

                switch (sort) {

                    case 'oldest':
                        return compareDates(
                            a.dataset.date,
                            b.dataset.date
                        );

                    case 'name-az':
                        return (a.dataset.name || '').localeCompare(
                            b.dataset.name || ''
                        );

                    case 'name-za':
                        return (b.dataset.name || '').localeCompare(
                            a.dataset.name || ''
                        );

                    case 'newest':
                    default:
                        return compareDates(
                            b.dataset.date,
                            a.dataset.date
                        );
                }
            });

            return visible;
        }


        // ==================================================
        // DATE COMPARISON
        // ==================================================

        function compareDates(dateA, dateB) {

            const timeA = parseDate(dateA);
            const timeB = parseDate(dateB);

            return timeA - timeB;
        }

        function parseDate(dateString) {

            if (!dateString) {
                return 0;
            }

            const parsed = new Date(dateString).getTime();

            return Number.isNaN(parsed)
                ? 0
                : parsed;
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
                resultsCount.textContent = 'No employees found';
                return;
            }

            const startNumber = start + 1;

            const endNumber = Math.min(
                start + PAGE_SIZE,
                totalRows
            );

            resultsCount.textContent =
                `Showing ${startNumber}-${endNumber} of ${totalRows} employees`;
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
            departmentFilter,
            employmentTypeFilter,
            statusFilter,
            sortFilter
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


    // ======================================================
    // EMPLOYEE DETAIL VIEW
    // ======================================================

    const tabs =
        document.querySelectorAll('.employee-view-tab');

    const slider =
        document.querySelector('.employee-view-slider');

    if (!tabs.length || !slider) {
        return;
    }

    const views = [
        'overview',
        'employment',
        'documents',
        'notes',
        'activity'
    ];


    // ======================================================
    // ACTIVATE VIEW
    // ======================================================

    function activateView(viewName, updateHash = true) {

        const index = views.indexOf(viewName);

        if (index === -1) {
            return;
        }

        slider.style.transform =
            `translateX(-${index * 20}%)`;

        tabs.forEach(tab => {

            const isActive =
                tab.dataset.view === viewName;

            tab.classList.toggle(
                'active',
                isActive
            );

            tab.setAttribute(
                'aria-selected',
                isActive ? 'true' : 'false'
            );
        });

        if (updateHash) {

            try {

                history.replaceState(
                    null,
                    '',
                    `#${viewName}`
                );

            } catch (error) {

                console.warn(
                    'Unable to update employee record URL.',
                    error
                );
            }
        }

        const activeTab =
            document.querySelector(
                `.employee-view-tab[data-view="${viewName}"]`
            );

        if (activeTab) {

            activeTab.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center'
            });
        }
    }


    // ======================================================
    // TAB CLICK
    // ======================================================

    tabs.forEach(tab => {

        tab.addEventListener('click', () => {

            activateView(
                tab.dataset.view
            );

        });
    });


    // ======================================================
    // KEYBOARD NAVIGATION
    // ======================================================

    tabs.forEach((tab, index) => {

        tab.addEventListener('keydown', event => {

            let nextIndex = index;

            if (event.key === 'ArrowRight') {

                event.preventDefault();

                nextIndex =
                    index + 1 >= tabs.length
                        ? 0
                        : index + 1;
            }

            if (event.key === 'ArrowLeft') {

                event.preventDefault();

                nextIndex =
                    index - 1 < 0
                        ? tabs.length - 1
                        : index - 1;
            }

            if (event.key === 'Home') {

                event.preventDefault();
                nextIndex = 0;
            }

            if (event.key === 'End') {

                event.preventDefault();
                nextIndex = tabs.length - 1;
            }

            if (nextIndex === index) {
                return;
            }

            tabs[nextIndex].focus();

            activateView(
                tabs[nextIndex].dataset.view
            );
        });
    });


    // ======================================================
    // INITIAL VIEW
    // ======================================================

    const hash =
        window.location.hash
            .replace('#', '')
            .trim();

    if (views.includes(hash)) {

        activateView(
            hash,
            false
        );

    } else {

        activateView(
            'overview',
            false
        );
    }


    // ======================================================
    // DOCUMENT REQUEST
    // ======================================================

    const requestDocumentButton =
        document.getElementById(
            'requestEmployeeDocument'
        );

    if (requestDocumentButton) {

        requestDocumentButton.addEventListener(
            'click',
            () => {

                console.log(
                    'Employee document request clicked.'
                );

            }
        );
    }


    // ======================================================
    // ADD NOTE
    // ======================================================

    const addNoteButton =
        document.getElementById(
            'addEmployeeNote'
        );

    if (addNoteButton) {

        addNoteButton.addEventListener(
            'click',
            () => {

                console.log(
                    'Add employee note clicked.'
                );

            }
        );
    }

})();