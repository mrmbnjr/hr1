// ==========================================================
// RAM-YUM — Employee Requests
// Filtering, sorting, pagination, request details modal,
// status updates, and HR remarks
// ==========================================================

(function () {

    document.addEventListener("DOMContentLoaded", function () {

        // ======================================================
        // TABLE
        // ======================================================

        const table =
            document.getElementById("employeeRequestTable");

        if (!table) {
            return;
        }


        const rows = Array.from(
            table.querySelectorAll(
                "tbody tr.request-row"
            )
        );


        // ======================================================
        // FILTERS
        // ======================================================

        const departmentFilter =
            document.getElementById(
                "departmentFilter"
            );

        const requestTypeFilter =
            document.getElementById(
                "requestTypeFilter"
            );

        const statusFilter =
            document.getElementById(
                "statusFilter"
            );

        const sortFilter =
            document.getElementById(
                "sortFilter"
            );


        // ======================================================
        // PAGINATION
        // ======================================================

        const resultsCount =
            document.getElementById(
                "resultsCount"
            );

        const pageNumbers =
            document.getElementById(
                "pageNumbers"
            );

        const prevPageBtn =
            document.getElementById(
                "prevPage"
            );

        const nextPageBtn =
            document.getElementById(
                "nextPage"
            );


        const PAGE_SIZE = 8;

        const STATUS_ORDER = [
            "Pending",
            "Approved",
            "Rejected",
            "Completed"
        ];

        let currentPage = 1;


        // ======================================================
        // MODAL
        // ======================================================

        const modal =
            document.getElementById(
                "requestModal"
            );

        const modalClose =
            document.getElementById(
                "requestModalClose"
            );

        const modalCancel =
            document.getElementById(
                "requestModalCancel"
            );

        const modalBackdrop =
            modal?.querySelector(
                ".request-modal-backdrop"
            );


        const requestModalNext =
            document.getElementById(
                "requestModalNext"
            );

        const requestModalSave =
            document.getElementById(
                "requestModalSave"
            );


        const requestStepOne =
            document.getElementById(
                "requestStepOne"
            );

        const requestStepTwo =
            document.getElementById(
                "requestStepTwo"
            );


        // ======================================================
        // MODAL FIELDS
        // ======================================================

        const employeeName =
            document.getElementById(
                "modalEmployeeName"
            );

        const employeeNumber =
            document.getElementById(
                "modalEmployeeNumber"
            );

        const department =
            document.getElementById(
                "modalDepartment"
            );

        const requestType =
            document.getElementById(
                "modalRequestType"
            );

        const requestDate =
            document.getElementById(
                "modalRequestDate"
            );

        const requestStatus =
            document.getElementById(
                "modalRequestStatus"
            );

        const requestSubject =
            document.getElementById(
                "modalRequestSubject"
            );

        const requestDescription =
            document.getElementById(
                "modalRequestDescription"
            );

        const requestRemarks =
            document.getElementById(
                "modalRequestRemarks"
            );


        // ======================================================
        // MODAL STATE
        // ======================================================

        let requestModalStep = 1;

        let currentRequestRow = null;

        let currentRequestId = null;

        let selectedStatus = "Pending";


        // ======================================================
        // FILTER + SORT
        // ======================================================

        function getVisibleRows() {

            const selectedDepartment =
                departmentFilter?.value ?? "All";

            const selectedRequestType =
                requestTypeFilter?.value ?? "All";

            const selectedStatusFilter =
                statusFilter?.value ?? "All";

            const selectedSort =
                sortFilter?.value ?? "newest";


            const visibleRows = rows.filter(row => {

                const matchesDepartment =
                    selectedDepartment === "All" ||
                    row.dataset.department ===
                    selectedDepartment;


                const matchesRequestType =
                    selectedRequestType === "All" ||
                    row.dataset.requestType ===
                    selectedRequestType;


                const matchesStatus =
                    selectedStatusFilter === "All" ||
                    row.dataset.status ===
                    selectedStatusFilter;


                return (
                    matchesDepartment &&
                    matchesRequestType &&
                    matchesStatus
                );

            });


            visibleRows.sort((a, b) => {

                switch (selectedSort) {

                    case "oldest":

                        return compareDates(
                            a.dataset.date,
                            b.dataset.date
                        );


                    case "name-az":

                        return (
                            a.dataset.name || ""
                        ).localeCompare(
                            b.dataset.name || ""
                        );


                    case "name-za":

                        return (
                            b.dataset.name || ""
                        ).localeCompare(
                            a.dataset.name || ""
                        );


                    case "status":

                        return compareStatus(
                            a.dataset.status,
                            b.dataset.status
                        );


                    case "newest":

                    default:

                        return compareDates(
                            b.dataset.date,
                            a.dataset.date
                        );

                }

            });


            return visibleRows;

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

            const parsed =
                new Date(dateString).getTime();

            return Number.isNaN(parsed)
                ? 0
                : parsed;

        }


        // ======================================================
        // STATUS COMPARISON
        // ======================================================

        function compareStatus(statusA, statusB) {

            const indexA =
                STATUS_ORDER.indexOf(statusA);

            const indexB =
                STATUS_ORDER.indexOf(statusB);


            return (
                (
                    indexA === -1
                        ? STATUS_ORDER.length
                        : indexA
                )
                -
                (
                    indexB === -1
                        ? STATUS_ORDER.length
                        : indexB
                )
            );

        }


        // ======================================================
        // APPLY TABLE STATE
        // ======================================================

        function applyFilters() {

            const visible =
                getVisibleRows();


            const totalPages =
                Math.max(
                    1,
                    Math.ceil(
                        visible.length /
                        PAGE_SIZE
                    )
                );


            if (currentPage > totalPages) {

                currentPage =
                    totalPages;

            }


            const start =
                (currentPage - 1) *
                PAGE_SIZE;


            const pageRows =
                visible.slice(
                    start,
                    start + PAGE_SIZE
                );


            const tbody =
                table.querySelector(
                    "tbody"
                );


            visible.forEach(row => {
                tbody.appendChild(row);
            });


            rows.forEach(row => {

                row.style.display =
                    "none";

            });


            pageRows.forEach(row => {

                row.style.display =
                    "";

            });


            updateResultsCount(
                visible.length,
                start
            );


            renderPagination(
                totalPages
            );

        }


        // ======================================================
        // RESULTS COUNT
        // ======================================================

        function updateResultsCount(
            totalRows,
            start
        ) {

            if (!resultsCount) {
                return;
            }


            if (totalRows === 0) {

                resultsCount.textContent =
                    "No requests found";

                return;

            }


            const startNumber =
                start + 1;


            const endNumber =
                Math.min(
                    start + PAGE_SIZE,
                    totalRows
                );


            resultsCount.textContent =
                `Showing ${startNumber}-${endNumber} of ${totalRows} requests`;

        }


        // ======================================================
        // PAGINATION
        // ======================================================

        function renderPagination(
            totalPages
        ) {

            if (!pageNumbers) {
                return;
            }


            pageNumbers.innerHTML =
                "";


            const addButton = page => {

                const button =
                    document.createElement(
                        "button"
                    );


                button.type =
                    "button";


                button.className =
                    `page-num${
                        page === currentPage
                            ? " active"
                            : ""
                    }`;


                button.textContent =
                    page;


                button.addEventListener(
                    "click",
                    () => {

                        currentPage =
                            page;

                        applyFilters();

                    }
                );


                pageNumbers.appendChild(
                    button
                );

            };


            const addDots = () => {

                const span =
                    document.createElement(
                        "span"
                    );


                span.textContent =
                    "...";


                pageNumbers.appendChild(
                    span
                );

            };


            if (totalPages <= 7) {

                for (
                    let page = 1;
                    page <= totalPages;
                    page++
                ) {

                    addButton(page);

                }

            } else {

                addButton(1);


                if (currentPage > 3) {
                    addDots();
                }


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


                if (
                    currentPage <
                    totalPages - 2
                ) {

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
        // PREVIOUS PAGE
        // ======================================================

        prevPageBtn?.addEventListener(
            "click",
            () => {

                if (currentPage <= 1) {
                    return;
                }


                currentPage--;

                applyFilters();

            }
        );


        // ======================================================
        // NEXT PAGE
        // ======================================================

        nextPageBtn?.addEventListener(
            "click",
            () => {

                const visibleRows =
                    getVisibleRows();


                const totalPages =
                    Math.max(
                        1,
                        Math.ceil(
                            visibleRows.length /
                            PAGE_SIZE
                        )
                    );


                if (
                    currentPage >=
                    totalPages
                ) {

                    return;

                }


                currentPage++;

                applyFilters();

            }
        );


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


            filter.addEventListener(
                "change",
                () => {

                    currentPage = 1;

                    applyFilters();

                }
            );

        });


        // ======================================================
        // STATUS CLASS
        // ======================================================

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


        // ======================================================
        // RESET MODAL STEP
        // ======================================================

        function resetModalStep() {

            requestModalStep = 1;


            if (requestStepOne) {
                requestStepOne.hidden = false;
            }


            if (requestStepTwo) {
                requestStepTwo.hidden = true;
            }


            if (requestModalNext) {

                requestModalNext.innerHTML =
                    'Next <i class="fa-solid fa-arrow-right"></i>';

            }

        }


        // ======================================================
        // HIGHLIGHT STATUS
        // ======================================================

        function highlightStatus(status) {

            document
                .querySelectorAll(
                    ".request-status-item"
                )
                .forEach(item => {

                    item.classList.toggle(
                        "active",
                        item.dataset.status === status
                    );

                });

        }


        // ======================================================
        // OPEN REQUEST MODAL
        // ======================================================

        function openRequestModal(row) {

            const data =
                row.dataset;


            currentRequestRow =
                row;


            currentRequestId =
                data.id || null;


            resetModalStep();


            // --------------------------------------------------
            // Employee
            // --------------------------------------------------

            if (employeeName) {

                employeeName.textContent =
                    data.name ||
                    "Unknown Employee";

            }


            if (employeeNumber) {

                employeeNumber.textContent =
                    data.employeeNumber ||
                    "—";

            }


            if (department) {

                department.textContent =
                    data.department ||
                    "—";

            }


            // --------------------------------------------------
            // Request
            // --------------------------------------------------

            if (requestType) {

                requestType.textContent =
                    data.requestType ||
                    "—";

            }


            if (requestDate) {

                requestDate.textContent =
                    data.date ||
                    "—";

            }


            if (requestSubject) {

                requestSubject.textContent =
                    data.subject ||
                    "No subject provided.";

            }


            if (requestDescription) {

                requestDescription.textContent =
                    data.description ||
                    "No additional details provided.";

            }


            // --------------------------------------------------
            // Status
            // --------------------------------------------------

            selectedStatus =
                data.status ||
                "Pending";


            if (requestStatus) {

                requestStatus.textContent =
                    selectedStatus;


                requestStatus.className =
                    "badge " +
                    getStatusClass(
                        selectedStatus
                    );

            }


            highlightStatus(
                selectedStatus
            );


            // --------------------------------------------------
            // HR Remarks
            // --------------------------------------------------

            if (requestRemarks) {

                requestRemarks.value =
                    data.hrRemarks || "";

            }


            // --------------------------------------------------
            // Open modal
            // --------------------------------------------------

            modal.classList.add(
                "is-open"
            );


            modal.setAttribute(
                "aria-hidden",
                "false"
            );


            document.body.classList.add(
                "modal-open"
            );

        }


        // ======================================================
        // CLOSE REQUEST MODAL
        // ======================================================

        function closeRequestModal() {

            if (!modal) {
                return;
            }


            modal.classList.remove(
                "is-open"
            );


            modal.setAttribute(
                "aria-hidden",
                "true"
            );


            document.body.classList.remove(
                "modal-open"
            );


            currentRequestRow =
                null;


            currentRequestId =
                null;


            selectedStatus =
                "Pending";


            resetModalStep();

        }


        // ======================================================
        // STATUS SELECTION
        // ======================================================

        document.addEventListener(
            "click",
            function (event) {

                const statusItem =
                    event.target.closest(
                        ".request-status-item"
                    );


                if (!statusItem) {
                    return;
                }


                if (!modal.classList.contains("is-open")) {
                    return;
                }


                selectedStatus =
                    statusItem.dataset.status ||
                    "Pending";


                highlightStatus(
                    selectedStatus
                );


                if (requestStatus) {

                    requestStatus.textContent =
                        selectedStatus;


                    requestStatus.className =
                        "badge " +
                        getStatusClass(
                            selectedStatus
                        );

                }

            }
        );


        // ======================================================
        // REQUEST TYPE CLICK
        // ======================================================

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
                    button.closest(
                        ".request-row"
                    );


                if (!row) {
                    return;
                }


                openRequestModal(row);

            }
        );


        // ======================================================
        // SAVE REQUEST
        // ======================================================

        requestModalSave?.addEventListener(
            "click",
            async function () {

                if (!currentRequestId) {

                    alert(
                        "Unable to identify the employee request."
                    );

                    return;

                }


                const hrRemarks =
                    requestRemarks?.value.trim() || "";


                // ------------------------------------------------
                // Disable button while saving
                // ------------------------------------------------

                requestModalSave.disabled =
                    true;


                const originalButtonText =
                    requestModalSave.innerHTML;


                requestModalSave.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';


                try {

                    const formData =
                        new URLSearchParams();


                    formData.append(
                        "request_id",
                        currentRequestId
                    );


                    formData.append(
                        "status",
                        selectedStatus
                    );


                    formData.append(
                        "hr_remarks",
                        hrRemarks
                    );


                    const response =
                        await fetch(
                            "/hr1/public/?page=employee-requests-update",
                            {
                                method: "POST",

                                headers: {
                                    "Content-Type":
                                        "application/x-www-form-urlencoded; charset=UTF-8",

                                    "X-Requested-With":
                                        "XMLHttpRequest"
                                },

                                body:
                                    formData.toString()
                            }
                        );


                    const result =
                        await response.json();


                    if (!response.ok ||
                        !result.success
                    ) {

                        throw new Error(
                            result.message ||
                            "Failed to update request."
                        );

                    }


                    // ------------------------------------------------
                    // Update row from server response
                    // ------------------------------------------------

                    updateRequestRow(
                        currentRequestRow,
                        result.request
                    );


                    // ------------------------------------------------
                    // Show success
                    // ------------------------------------------------

                    requestModalSave.innerHTML =
                        '<i class="fa-solid fa-check"></i> Saved';


                    setTimeout(
                        () => {

                            closeRequestModal();

                            applyFilters();

                        },
                        500
                    );


                } catch (error) {

                    console.error(
                        "Employee request update error:",
                        error
                    );


                    alert(
                        error.message ||
                        "Unable to update the employee request."
                    );


                    requestModalSave.innerHTML =
                        originalButtonText;

                    requestModalSave.disabled =
                        false;

                }

            }
        );


        // ======================================================
        // UPDATE TABLE ROW
        // ======================================================

        function updateRequestRow(
            row,
            request
        ) {

            if (!row || !request) {
                return;
            }


            const newStatus =
                request.status ||
                "Pending";


            const newRemarks =
                request.hr_remarks ||
                "";


            // --------------------------------------------------
            // Update data attributes
            // --------------------------------------------------

            row.dataset.status =
                newStatus;


            row.dataset.hrRemarks =
                newRemarks;


            // --------------------------------------------------
            // Update visible status badge
            // --------------------------------------------------

            const badge =
                row.querySelector(
                    "td:last-child .badge"
                );


            if (badge) {

                badge.textContent =
                    newStatus;


                badge.className =
                    "badge " +
                    getStatusClass(
                        newStatus
                    );

            }


            // --------------------------------------------------
            // Keep modal data synchronized
            // --------------------------------------------------

            selectedStatus =
                newStatus;

        }


        // ======================================================
        // MODAL CLOSE EVENTS
        // ======================================================

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


        // ======================================================
        // MODAL NEXT / BACK
        // ======================================================

        requestModalNext?.addEventListener(
            "click",
            function () {

                if (requestModalStep === 1) {

                    requestStepOne.hidden =
                        true;

                    requestStepTwo.hidden =
                        false;

                    requestModalStep =
                        2;


                    requestModalNext.innerHTML =
                        '<i class="fa-solid fa-arrow-left"></i> Back';


                    return;

                }


                requestStepTwo.hidden =
                    true;

                requestStepOne.hidden =
                    false;

                requestModalStep =
                    1;


                requestModalNext.innerHTML =
                    'Next <i class="fa-solid fa-arrow-right"></i>';

            }
        );


        // ======================================================
        // ESCAPE KEY
        // ======================================================

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Escape" &&
                    modal.classList.contains(
                        "is-open"
                    )
                ) {

                    closeRequestModal();

                }

            }
        );


        // ======================================================
        // INITIALIZE
        // ======================================================

        applyFilters();

    });

})();