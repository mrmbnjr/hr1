// ==========================================================
// RAM-YUM — Employee Requests
// Filtering, sorting, and pagination
// ==========================================================

(function () {

    const table = document.getElementById('employeeRequestTable');

    if (!table) {
        return;
    }

    const rows = Array.from(
        table.querySelectorAll('tbody tr.request-row')
    );

    const departmentFilter =
        document.getElementById('departmentFilter');

    const requestTypeFilter =
        document.getElementById('requestTypeFilter');

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

    const STATUS_ORDER = [
        'Pending',
        'Approved',
        'Rejected',
        'Completed'
    ];

    let currentPage = 1;


    // ======================================================
    // FILTER + SORT
    // ======================================================

    function getVisibleRows() {

        const department =
            departmentFilter?.value ?? 'All';

        const requestType =
            requestTypeFilter?.value ?? 'All';

        const status =
            statusFilter?.value ?? 'All';

        const sort =
            sortFilter?.value ?? 'newest';

        const visible = rows.filter(row => {

            const matchesDepartment =
                department === 'All' ||
                row.dataset.department === department;

            const matchesRequestType =
                requestType === 'All' ||
                row.dataset.requestType === requestType;

            const matchesStatus =
                status === 'All' ||
                row.dataset.status === status;

            return (
                matchesDepartment &&
                matchesRequestType &&
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

                case 'status':
                    return compareStatus(
                        a.dataset.status,
                        b.dataset.status
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


    // ======================================================
    // DATE COMPARISON
    // ======================================================

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


    // ======================================================
    // STATUS COMPARISON
    // ======================================================

    function compareStatus(statusA, statusB) {

        const indexA = STATUS_ORDER.indexOf(statusA);
        const indexB = STATUS_ORDER.indexOf(statusB);

        return (
            (indexA === -1 ? STATUS_ORDER.length : indexA) -
            (indexB === -1 ? STATUS_ORDER.length : indexB)
        );
    }


    // ======================================================
    // APPLY TABLE STATE
    // ======================================================

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


    // ======================================================
    // RESULTS COUNT
    // ======================================================

    function updateResultsCount(totalRows, start) {

        if (!resultsCount) {
            return;
        }

        if (totalRows === 0) {
            resultsCount.textContent = 'No requests found';
            return;
        }

        const startNumber = start + 1;

        const endNumber = Math.min(
            start + PAGE_SIZE,
            totalRows
        );

        resultsCount.textContent =
            `Showing ${startNumber}-${endNumber} of ${totalRows} requests`;
    }


    // ======================================================
    // PAGINATION
    // ======================================================

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


    // ======================================================
    // PAGINATION EVENTS
    // ======================================================

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


    // ======================================================
    // FILTER EVENTS
    // ======================================================

    [
        departmentFilter,
        requestTypeFilter,
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


    // ======================================================
    // INITIALIZE TABLE
    // ======================================================

    applyFilters();

})();

document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("requestModal");
    const modalClose = document.getElementById("requestModalClose");
    const modalCancel = document.getElementById("requestModalCancel");
    const modalBackdrop = modal?.querySelector(
        ".request-modal-backdrop"
    );

    if (!modal) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | MODAL ELEMENTS
    |--------------------------------------------------------------------------
    */

    const employeeName = document.getElementById(
        "modalEmployeeName"
    );

    const employeeNumber = document.getElementById(
        "modalEmployeeNumber"
    );

    const department = document.getElementById(
        "modalDepartment"
    );

    const requestType = document.getElementById(
        "modalRequestType"
    );

    const requestDate = document.getElementById(
        "modalRequestDate"
    );

    const requestStatus = document.getElementById(
        "modalRequestStatus"
    );

    const requestSubject = document.getElementById(
        "modalRequestSubject"
    );

    const requestDescription = document.getElementById(
        "modalRequestDescription"
    );


    /*
    |--------------------------------------------------------------------------
    | OPEN MODAL
    |--------------------------------------------------------------------------
    */

    function openRequestModal(row) {

        const data = row.dataset;

        employeeName.textContent =
            data.name || "Unknown Employee";

        employeeNumber.textContent =
            data.employeeNumber || "—";

        department.textContent =
            data.department || "—";

        requestType.textContent =
            data.requestType || "—";

        requestDate.textContent =
            data.date || "—";

        requestSubject.textContent =
            data.subject || "No subject provided.";

        requestDescription.textContent =
            data.description ||
            "No additional details provided.";


        /*
        |--------------------------------------------------------------------------
        | STATUS BADGE
        |--------------------------------------------------------------------------
        */

        const status = data.status || "Pending";

        requestStatus.textContent = status;

        requestStatus.className =
            "badge " + getStatusClass(status);


        modal.classList.add("is-open");

        modal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add(
            "modal-open"
        );

    }


    /*
    |--------------------------------------------------------------------------
    | STATUS CLASS
    |--------------------------------------------------------------------------
    */

    function getStatusClass(status) {

        switch (status) {

            case "Approved":
            case "Completed":
                return "badge-green";

            case "Rejected":
                return "badge-red";

            case "Pending":
            default:
                return "badge-gray";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE MODAL
    |--------------------------------------------------------------------------
    */

    function closeRequestModal() {

        modal.classList.remove("is-open");

        modal.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.classList.remove(
            "modal-open"
        );

    }


    /*
    |--------------------------------------------------------------------------
    | REQUEST CLICK
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        function (event) {

            const button =
                event.target.closest(
                    ".request-view-btn"
                );

            if (!button) {
                return;
            }

            const row =
                button.closest(".request-row");

            if (!row) {
                return;
            }

            openRequestModal(row);

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CLOSE EVENTS
    |--------------------------------------------------------------------------
    */

    modalClose?.addEventListener(
        "click",
        closeRequestModal
    );

    modalCancel?.addEventListener(
        "click",
        closeRequestModal
    );

    modalBackdrop?.addEventListener(
        "click",
        closeRequestModal
    );


    /*
    |--------------------------------------------------------------------------
    | ESCAPE KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                modal.classList.contains("is-open")
            ) {

                closeRequestModal();

            }

        }
    );

});