// ==========================================================
// RAM-YUM — Employee Records
// Table filtering, sorting, and pagination
// ==========================================================

(function () {

    const table = document.getElementById('employeeRecordsTable');

    // Only run on Employee Records page
    if (!table) {
        return;
    }


    // ======================================================
    // ELEMENTS
    // ======================================================

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


    // ======================================================
    // SETTINGS
    // ======================================================

    const PAGE_SIZE = 8;

    let currentPage = 1;


    // ======================================================
    // TABLE FILTERING + SORTING + PAGINATION
    // ======================================================

    function applyFilters() {

        const department =
            departmentFilter
                ? departmentFilter.value
                : 'All';

        const employmentType =
            employmentTypeFilter
                ? employmentTypeFilter.value
                : 'All';

        const status =
            statusFilter
                ? statusFilter.value
                : 'All';

        const sort =
            sortFilter
                ? sortFilter.value
                : 'newest';


        // --------------------------------------------------
        // FILTER
        // --------------------------------------------------

        let visible = rows.filter(row => {

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


        // --------------------------------------------------
        // SORT
        // --------------------------------------------------

        visible.sort((a, b) => {

            switch (sort) {

                case 'oldest':

                    return compareDates(
                        a.dataset.date,
                        b.dataset.date
                    );


                case 'name-az':

                    return (
                        a.dataset.name || ''
                    ).localeCompare(
                        b.dataset.name || ''
                    );


                case 'name-za':

                    return (
                        b.dataset.name || ''
                    ).localeCompare(
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


        // --------------------------------------------------
        // REORDER TABLE
        // --------------------------------------------------

        const tbody =
            table.querySelector('tbody');

        visible.forEach(row => {
            tbody.appendChild(row);
        });


        // --------------------------------------------------
        // HIDE ALL ORIGINAL ROWS
        // --------------------------------------------------

        rows.forEach(row => {
            row.style.display = 'none';
        });


        // --------------------------------------------------
        // PAGINATION
        // --------------------------------------------------

        const totalPages =
            Math.max(
                1,
                Math.ceil(
                    visible.length / PAGE_SIZE
                )
            );


        if (currentPage > totalPages) {
            currentPage = totalPages;
        }


        const start =
            (currentPage - 1) * PAGE_SIZE;


        const pageRows =
            visible.slice(
                start,
                start + PAGE_SIZE
            );


        pageRows.forEach(row => {
            row.style.display = '';
        });


        // --------------------------------------------------
        // RESULTS COUNT
        // --------------------------------------------------

        if (resultsCount) {

            if (visible.length === 0) {

                resultsCount.textContent =
                    'No employees found';

            } else {

                const startNumber =
                    start + 1;

                const endNumber =
                    Math.min(
                        start + PAGE_SIZE,
                        visible.length
                    );

                resultsCount.textContent =
                    `Showing ${startNumber}-${endNumber} of ${visible.length} employees`;

            }

        }


        // --------------------------------------------------
        // PAGINATION UI
        // --------------------------------------------------

        renderPagination(totalPages);

    }


    // ======================================================
    // DATE COMPARISON
    // ======================================================

    function compareDates(dateA, dateB) {

        const timeA =
            parseDate(dateA);

        const timeB =
            parseDate(dateB);


        return timeA - timeB;

    }


    function parseDate(dateString) {

        if (!dateString) {
            return 0;
        }


        const parsed =
            new Date(dateString).getTime();


        return Number.isNaN(parsed)
            ? 0
            : parsed;

    }


    // ======================================================
    // PAGINATION
    // ======================================================

    function renderPagination(totalPages) {

        if (!pageNumbers) {
            return;
        }


        pageNumbers.innerHTML = '';


        // --------------------------------------------------
        // PAGE BUTTON
        // --------------------------------------------------

        const addButton = (page) => {

            const button =
                document.createElement('button');


            button.type = 'button';

            button.className =
                'page-num' +
                (
                    page === currentPage
                        ? ' active'
                        : ''
                );


            button.textContent = page;


            button.addEventListener(
                'click',
                () => {

                    currentPage = page;

                    applyFilters();

                }
            );


            pageNumbers.appendChild(button);

        };


        // --------------------------------------------------
        // ELLIPSIS
        // --------------------------------------------------

        const addDots = () => {

            const span =
                document.createElement('span');

            span.textContent = '...';

            pageNumbers.appendChild(span);

        };


        // --------------------------------------------------
        // PAGINATION LAYOUT
        // --------------------------------------------------

        if (totalPages <= 7) {

            for (
                let page = 1;
                page <= totalPages;
                page++
            ) {

                addButton(page);

            }

        } else {

            // First page

            addButton(1);


            // Left dots

            if (currentPage > 3) {
                addDots();
            }


            // Middle pages

            const start =
                Math.max(
                    2,
                    currentPage - 1
                );


            const end =
                Math.min(
                    totalPages - 1,
                    currentPage + 1
                );


            for (
                let page = start;
                page <= end;
                page++
            ) {

                addButton(page);

            }


            // Right dots

            if (
                currentPage <
                totalPages - 2
            ) {

                addDots();

            }


            // Last page

            addButton(totalPages);

        }


        // --------------------------------------------------
        // PREVIOUS / NEXT
        // --------------------------------------------------

        if (prevPageBtn) {

            prevPageBtn.disabled =
                currentPage === 1;

        }


        if (nextPageBtn) {

            nextPageBtn.disabled =
                currentPage === totalPages;

        }

    }


    // ======================================================
    // PREVIOUS PAGE
    // ======================================================

    if (prevPageBtn) {

        prevPageBtn.addEventListener(
            'click',
            () => {

                if (currentPage > 1) {

                    currentPage--;

                    applyFilters();

                }

            }
        );

    }


    // ======================================================
    // NEXT PAGE
    // ======================================================

    if (nextPageBtn) {

        nextPageBtn.addEventListener(
            'click',
            () => {

                const visibleRows =
                    getFilteredRows();


                const totalPages =
                    Math.max(
                        1,
                        Math.ceil(
                            visibleRows.length /
                            PAGE_SIZE
                        )
                    );


                if (currentPage < totalPages) {

                    currentPage++;

                    applyFilters();

                }

            }
        );

    }


    // ======================================================
    // FILTER EVENTS
    // ======================================================

    if (departmentFilter) {

        departmentFilter.addEventListener(
            'change',
            () => {

                currentPage = 1;

                applyFilters();

            }
        );

    }


    if (employmentTypeFilter) {

        employmentTypeFilter.addEventListener(
            'change',
            () => {

                currentPage = 1;

                applyFilters();

            }
        );

    }


    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            () => {

                currentPage = 1;

                applyFilters();

            }
        );

    }


    if (sortFilter) {

        sortFilter.addEventListener(
            'change',
            () => {

                currentPage = 1;

                applyFilters();

            }
        );

    }


    // ======================================================
    // GET FILTERED ROWS
    // ======================================================

    function getFilteredRows() {

        const department =
            departmentFilter
                ? departmentFilter.value
                : 'All';

        const employmentType =
            employmentTypeFilter
                ? employmentTypeFilter.value
                : 'All';

        const status =
            statusFilter
                ? statusFilter.value
                : 'All';


        return rows.filter(row => {

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

    }


    // ======================================================
    // INITIALIZE
    // ======================================================

    applyFilters();

})();