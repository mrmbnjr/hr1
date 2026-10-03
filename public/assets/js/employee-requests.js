// ==========================================================
// RAM-YUM — Employee Requests
// Filtering, sorting, pagination, request details modal,
// status finalization, and HR remarks
// ==========================================================

(function () {

    document.addEventListener(
        "DOMContentLoaded",
        function () {

            // ==================================================
            // TABLE
            // ==================================================

            const table =
                document.getElementById(
                    "employeeRequestTable"
                );


            if (!table) {
                return;
            }


            const rows =
                Array.from(
                    table.querySelectorAll(
                        "tbody tr.request-row"
                    )
                );


            // ==================================================
            // FILTERS
            // ==================================================

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


            // ==================================================
            // PAGINATION
            // ==================================================

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

            rows.forEach(row => {
                const openRow = event => {
                    if (event.target.closest('a, button, input, select, textarea')) {
                        return;
                    }

                    if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    if (event.type === 'keydown') {
                        event.preventDefault();
                    }

                    openRequestModal(row);
                };

                row.addEventListener('click', openRow);
                row.addEventListener('keydown', openRow);
            });


            const STATUS_ORDER = [
                "Pending",
                "Approved",
                "Rejected",
                "Completed"
            ];


            let currentPage = 1;


            // ==================================================
            // MODAL
            // ==================================================

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

            const requestModalBack =
                document.getElementById(
                    "requestModalBack"
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


            const requestReadonlyMessage =
                document.getElementById(
                    "requestReadonlyMessage"
                );


            // ==================================================
            // MODAL FIELDS
            // ==================================================

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


            // ==================================================
            // MODAL STATE
            // ==================================================

            let requestModalStep = 1;

            let currentRequestRow = null;

            let currentRequestId = null;

            let originalStatus = "Pending";

            let selectedStatus = "Pending";

            let requestIsFinalized = false;


            // ==================================================
            // FILTER + SORT
            // ==================================================

            function getVisibleRows() {

                const selectedDepartment =
                    departmentFilter?.value ??
                    "All";


                const selectedRequestType =
                    requestTypeFilter?.value ??
                    "All";


                const selectedStatus =
                    statusFilter?.value ??
                    "All";


                const selectedSort =
                    sortFilter?.value ??
                    "newest";


                const visibleRows =
                    rows.filter(
                        row => {

                            const matchesDepartment =
                                selectedDepartment === "All" ||
                                row.dataset.department ===
                                selectedDepartment;


                            const matchesRequestType =
                                selectedRequestType === "All" ||
                                row.dataset.requestType ===
                                selectedRequestType;


                            const matchesStatus =
                                selectedStatus === "All" ||
                                row.dataset.status ===
                                selectedStatus;


                            return (
                                matchesDepartment &&
                                matchesRequestType &&
                                matchesStatus
                            );

                        }
                    );


                visibleRows.sort(
                    (a, b) => {

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

                    }
                );


                return visibleRows;

            }


            // ==================================================
            // DATE COMPARISON
            // ==================================================

            function compareDates(
                dateA,
                dateB
            ) {

                const timeA =
                    parseDate(dateA);


                const timeB =
                    parseDate(dateB);


                return timeA - timeB;

            }


            function parseDate(
                dateString
            ) {

                if (!dateString) {
                    return 0;
                }


                const parsed =
                    new Date(
                        dateString
                    ).getTime();


                return Number.isNaN(parsed)
                    ? 0
                    : parsed;

            }


            // ==================================================
            // STATUS COMPARISON
            // ==================================================

            function compareStatus(
                statusA,
                statusB
            ) {

                const indexA =
                    STATUS_ORDER.indexOf(
                        statusA
                    );


                const indexB =
                    STATUS_ORDER.indexOf(
                        statusB
                    );


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


            // ==================================================
            // APPLY FILTERS
            // ==================================================

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


                if (
                    currentPage >
                    totalPages
                ) {

                    currentPage =
                        totalPages;

                }


                const start =
                    (
                        currentPage - 1
                    ) * PAGE_SIZE;


                const pageRows =
                    visible.slice(
                        start,
                        start + PAGE_SIZE
                    );


                const tbody =
                    table.querySelector(
                        "tbody"
                    );


                visible.forEach(
                    row => {

                        tbody.appendChild(
                            row
                        );

                    }
                );


                rows.forEach(
                    row => {

                        row.style.display =
                            "none";

                    }
                );


                pageRows.forEach(
                    row => {

                        row.style.display =
                            "";

                    }
                );


                updateResultsCount(
                    visible.length,
                    start
                );


                renderPagination(
                    totalPages
                );

            }


            // ==================================================
            // RESULTS COUNT
            // ==================================================

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
            // PREVIOUS PAGE
            // ==================================================

            prevPageBtn?.addEventListener(
                "click",
                () => {

                    if (
                        currentPage <= 1
                    ) {

                        return;
                    }


                    currentPage--;

                    applyFilters();

                }
            );


            // ==================================================
            // NEXT PAGE
            // ==================================================

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


            // ==================================================
            // FILTER EVENTS
            // ==================================================

            [
                departmentFilter,
                requestTypeFilter,
                statusFilter,
                sortFilter

            ].forEach(
                filter => {

                    if (!filter) {
                        return;
                    }


                    filter.addEventListener(
                        "change",
                        () => {

                            currentPage =
                                1;

                            applyFilters();

                        }
                    );

                }
            );


            // ==================================================
            // STATUS CLASS
            // ==================================================

            function getStatusClass(
                status
            ) {

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


            // ==================================================
            // HIGHLIGHT STATUS
            // ==================================================

            function highlightStatus(
                status
            ) {

                document
                    .querySelectorAll(
                        ".request-status-item"
                    )
                    .forEach(
                        item => {

                            item.classList.toggle(
                                "active",
                                item.dataset.status ===
                                status
                            );

                        }
                    );

            }


            // ==================================================
            // CHECK EDITABILITY
            // ==================================================

            function isRequestEditable() {

                return !requestIsFinalized;

            }


            // ==================================================
            // UPDATE MODAL EDITABILITY
            // ==================================================

            function updateModalEditability() {

                const editable =
                    isRequestEditable();


                /*
                |--------------------------------------------------------------------------
                | Status buttons
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll(
                        ".request-status-item"
                    )
                    .forEach(
                        item => {

                            item.classList.toggle(
                                "is-disabled",
                                !editable
                            );


                            item.setAttribute(
                                "aria-disabled",
                                editable
                                    ? "false"
                                    : "true"
                            );

                        }
                    );


                /*
                |--------------------------------------------------------------------------
                | Remarks
                |--------------------------------------------------------------------------
                */

                if (requestRemarks) {

                    requestRemarks.readOnly =
                        !editable;

                }


                /*
                |--------------------------------------------------------------------------
                | Read-only message
                |--------------------------------------------------------------------------
                */

                if (requestReadonlyMessage) {

                    requestReadonlyMessage.hidden =
                        editable;

                }


                /*
                |--------------------------------------------------------------------------
                | Save button
                |--------------------------------------------------------------------------
                */

                if (requestModalSave) {

                    requestModalSave.hidden =
                        !editable ||
                        requestModalStep !== 2;

                }

            }


            // ==================================================
            // RESET SAVE BUTTON
            // ==================================================

            function resetSaveButton() {

                if (!requestModalSave) {
                    return;
                }


                requestModalSave.hidden =
                    true;


                requestModalSave.disabled =
                    false;


                requestModalSave.innerHTML =
                    '<i class="fa-solid fa-check"></i> Save Changes';

            }


            // ==================================================
            // RESET MODAL STEP
            // ==================================================

            function resetModalStep() {

                requestModalStep =
                    1;


                if (requestStepOne) {

                    requestStepOne.hidden =
                        false;

                }


                if (requestStepTwo) {

                    requestStepTwo.hidden =
                        true;

                }


                if (requestModalNext) {

                    requestModalNext.hidden =
                        false;

                    requestModalNext.innerHTML =
                        'Next <i class="fa-solid fa-arrow-right"></i>';

                }

                if (requestModalBack) {

                    requestModalBack.hidden =
                        true;

                }


                if (requestModalSave) {

                    requestModalSave.hidden =
                        true;

                }

            }


            // ==================================================
            // OPEN REQUEST MODAL
            // ==================================================

            function openRequestModal(
                row
            ) {

                const data =
                    row.dataset;


                currentRequestRow =
                    row;


                currentRequestId =
                    data.id || null;


                /*
                |--------------------------------------------------------------------------
                | Determine current database state
                |--------------------------------------------------------------------------
                */

                originalStatus =
                    data.status ||
                    "Pending";


                selectedStatus =
                    originalStatus;


                requestIsFinalized =
                    originalStatus !==
                    "Pending";


                /*
                |--------------------------------------------------------------------------
                | Reset modal
                |--------------------------------------------------------------------------
                */

                resetModalStep();


                /*
                |--------------------------------------------------------------------------
                | Employee
                |--------------------------------------------------------------------------
                */

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


                /*
                |--------------------------------------------------------------------------
                | Request information
                |--------------------------------------------------------------------------
                */

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


                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

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


                /*
                |--------------------------------------------------------------------------
                | Remarks
                |--------------------------------------------------------------------------
                */

                if (requestRemarks) {

                    requestRemarks.value =
                        data.hrRemarks ||
                        "";

                }


                /*
                |--------------------------------------------------------------------------
                | Apply editability
                |--------------------------------------------------------------------------
                */

                updateModalEditability();


                /*
                |--------------------------------------------------------------------------
                | Open
                |--------------------------------------------------------------------------
                */

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


            // ==================================================
            // CLOSE REQUEST MODAL
            // ==================================================

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


                originalStatus =
                    "Pending";


                selectedStatus =
                    "Pending";


                requestIsFinalized =
                    false;


                if (requestRemarks) {

                    requestRemarks.value =
                        "";

                    requestRemarks.readOnly =
                        false;

                }


                resetSaveButton();

                resetModalStep();

            }


            // ==================================================
            // REQUEST TYPE CLICK
            // ==================================================

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


                    openRequestModal(
                        row
                    );

                }
            );


            // ==================================================
            // STATUS CLICK
            // ==================================================

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


                    if (
                        !modal.classList.contains(
                            "is-open"
                        )
                    ) {

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Finalized requests are read-only
                    |--------------------------------------------------------------------------
                    */

                    if (!isRequestEditable()) {

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


            // ==================================================
            // MODAL CLOSE EVENTS
            // ==================================================

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


            // ==================================================
            // NEXT / BACK
            // ==================================================

            requestModalNext?.addEventListener(
                "click",
                function () {

                    /*
                    |--------------------------------------------------------------------------
                    | Step 1 -> Step 2
                    |--------------------------------------------------------------------------
                    */

                    if (
                        requestModalStep === 1
                    ) {

                        requestStepOne.hidden =
                            true;


                        requestStepTwo.hidden =
                            false;


                        requestModalStep =
                            2;


                        requestModalNext.hidden =
                            true;


                        if (requestModalBack) {

                            requestModalBack.hidden =
                                false;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Only show Save for Pending requests
                        |--------------------------------------------------------------------------
                        */

                        if (
                            requestModalSave
                        ) {

                            requestModalSave.hidden =
                                requestIsFinalized;

                        }


                        return;
                    }


                }
            );

            requestModalBack?.addEventListener(
                "click",
                function () {

                    requestStepTwo.hidden =
                        true;


                    requestStepOne.hidden =
                        false;


                    requestModalStep =
                        1;


                    requestModalBack.hidden =
                        true;


                    if (requestModalNext) {

                        requestModalNext.hidden =
                            false;

                    }


                    if (requestModalSave) {

                        requestModalSave.hidden =
                            true;

                    }

                }
            );


            // ==================================================
            // SAVE REQUEST
            // ==================================================

            requestModalSave?.addEventListener(
                "click",
                async function () {

                    /*
                    |--------------------------------------------------------------------------
                    | Must have request
                    |--------------------------------------------------------------------------
                    */

                    if (!currentRequestId) {

                        alert(
                            "Unable to identify the employee request."
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Already finalized
                    |--------------------------------------------------------------------------
                    */

                    if (!isRequestEditable()) {

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Pending cannot be saved
                    |--------------------------------------------------------------------------
                    */

                    if (
                        selectedStatus ===
                        "Pending"
                    ) {

                        alert(
                            "Please select Approved, Rejected, or Completed before saving."
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Remarks
                    |--------------------------------------------------------------------------
                    */

                    const hrRemarks =
                        requestRemarks?.value.trim() ||
                        "";


                    /*
                    |--------------------------------------------------------------------------
                    | Confirm finalization
                    |--------------------------------------------------------------------------
                    */

                    const confirmed =
                        window.confirm(
                            `Are you sure you want to finalize this request as "${selectedStatus}"? Once saved, the request cannot be changed.`
                        );


                    if (!confirmed) {

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Disable button
                    |--------------------------------------------------------------------------
                    */

                    requestModalSave.disabled =
                        true;


                    requestModalSave.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';


                    try {

                        /*
                        |--------------------------------------------------------------------------
                        | Prepare form
                        |--------------------------------------------------------------------------
                        */

                        const formData =
                            new FormData();


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


                        /*
                        |--------------------------------------------------------------------------
                        | Send request
                        |--------------------------------------------------------------------------
                        */

                    const response =
                        await fetch(
                            "?page=employee-requests-update",
                            {
                                method: "POST",
                                body: formData,
                                credentials: "same-origin"
                            }
                        );

                        const result =
                            await response.json();


                        /*
                        |--------------------------------------------------------------------------
                        | Server error
                        |--------------------------------------------------------------------------
                        */

                        if (
                            !response.ok ||
                            !result.success
                        ) {

                            throw new Error(
                                result.message ||
                                "Failed to save the request."
                            );

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Update table row
                        |--------------------------------------------------------------------------
                        */

                        updateRequestRow(
                            currentRequestRow,
                            result.request
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Finalize modal state
                        |--------------------------------------------------------------------------
                        */

                        requestIsFinalized =
                            true;


                        originalStatus =
                            result.request.status;


                        selectedStatus =
                            result.request.status;


                        /*
                        |--------------------------------------------------------------------------
                        | Update status display
                        |--------------------------------------------------------------------------
                        */

                        if (requestStatus) {

                            requestStatus.textContent =
                                result.request.status;


                            requestStatus.className =
                                "badge " +
                                getStatusClass(
                                    result.request.status
                                );

                        }


                        highlightStatus(
                            result.request.status
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Update remarks
                        |--------------------------------------------------------------------------
                        */

                        if (requestRemarks) {

                            requestRemarks.value =
                                result.request.hr_remarks ||
                                "";

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Lock request
                        |--------------------------------------------------------------------------
                        */

                        updateModalEditability();


                        /*
                        |--------------------------------------------------------------------------
                        | Save button becomes hidden
                        |--------------------------------------------------------------------------
                        */

                        requestModalSave.hidden =
                            true;


                        /*
                        |--------------------------------------------------------------------------
                        | Refresh table
                        |--------------------------------------------------------------------------
                        */

                        applyFilters();


                        /*
                        |--------------------------------------------------------------------------
                        | Notify HR
                        |--------------------------------------------------------------------------
                        */

                        alert(
                            "Employee request finalized successfully."
                        );

                    } catch (error) {

                        console.error(
                            "Employee request update error:",
                            error
                        );


                        alert(
                            error.message ||
                            "Failed to save the employee request."
                        );


                        requestModalSave.disabled =
                            false;


                        requestModalSave.innerHTML =
                            '<i class="fa-solid fa-check"></i> Save Changes';

                    }

                }
            );


            // ==================================================
            // UPDATE TABLE ROW
            // ==================================================

            function updateRequestRow(
                row,
                request
            ) {

                if (!row || !request) {
                    return;
                }


                const status =
                    request.status ||
                    "Pending";


                const remarks =
                    request.hr_remarks ||
                    "";


                /*
                |--------------------------------------------------------------------------
                | Update data attributes
                |--------------------------------------------------------------------------
                */

                row.dataset.status =
                    status;


                row.dataset.hrRemarks =
                    remarks;


                row.dataset.id =
                    request.request_id ||
                    row.dataset.id;


                /*
                |--------------------------------------------------------------------------
                | Update visible badge
                |--------------------------------------------------------------------------
                */

                const badge =
                    row.querySelector(
                        ".badge"
                    );


                if (badge) {

                    badge.textContent =
                        status;


                    badge.className =
                        "badge " +
                        getStatusClass(
                            status
                        );

                }

            }


            // ==================================================
            // ESCAPE KEY
            // ==================================================

            document.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key ===
                        "Escape" &&
                        modal.classList.contains(
                            "is-open"
                        )
                    ) {

                        closeRequestModal();

                    }

                }
            );


            // ==================================================
            // INITIALIZE
            // ==================================================

            applyFilters();

        }
    );

})();


// ==========================================================
// RAM-YUM — Password Reset Requests (HR / Admin tab)
// Tab switching, detail modal, and resolve / reopen actions
// ==========================================================

(function () {

    document.addEventListener(
        "DOMContentLoaded",
        function () {

            // ==================================================
            // TABLE — only present for ADMIN / HR
            // ==================================================

            const resetTable =
                document.getElementById(
                    "passwordResetTable"
                );


            if (!resetTable) {
                return;
            }


            const UPDATE_URL =
                "?page=password-reset-request-update";


            const STATUS_META = {

                Pending: {
                    label: "Pending",
                    className: "badge-gray",
                    action: "Mark resolved",
                    next: "Resolved"
                },

                Resolved: {
                    label: "Resolved",
                    className: "badge-green",
                    action: "Reopen",
                    next: "Pending"
                }

            };


            // ==================================================
            // TABS
            // ==================================================

            const tabButtons =
                Array.from(
                    document.querySelectorAll(
                        ".request-tab[data-tab]"
                    )
                );


            const tabPanels =
                Array.from(
                    document.querySelectorAll(
                        ".request-tab-panel[data-panel]"
                    )
                );


            function activateTab(name) {

                tabButtons.forEach(function (button) {

                    const isActive =
                        button.dataset.tab === name;

                    button.classList.toggle(
                        "is-active",
                        isActive
                    );

                    button.setAttribute(
                        "aria-selected",
                        isActive ? "true" : "false"
                    );

                });


                tabPanels.forEach(function (panel) {

                    const isActive =
                        panel.dataset.panel === name;

                    panel.classList.toggle(
                        "is-active",
                        isActive
                    );

                    panel.hidden = !isActive;

                });

            }


            tabButtons.forEach(function (button) {

                button.addEventListener(
                    "click",
                    function () {
                        activateTab(
                            button.dataset.tab
                        );
                    }
                );

            });


            // ==================================================
            // PENDING COUNT PILL
            // ==================================================

            let pendingCount =
                (function () {

                    const badge =
                        document.querySelector(
                            ".request-tab-badge"
                        );

                    return badge
                        ? parseInt(
                            badge.textContent,
                            10
                        ) || 0
                        : 0;

                })();


            function renderPendingCount() {

                const tab =
                    document.querySelector(
                        '.request-tab[data-tab="resets"]'
                    );


                if (!tab) {
                    return;
                }


                let badge =
                    tab.querySelector(
                        ".request-tab-badge"
                    );


                if (pendingCount <= 0) {

                    if (badge) {
                        badge.remove();
                    }

                    return;
                }


                if (!badge) {

                    badge =
                        document.createElement(
                            "span"
                        );

                    badge.className =
                        "request-tab-badge";

                    tab.appendChild(badge);

                }


                badge.textContent =
                    String(pendingCount);

            }


            // ==================================================
            // MODAL
            // ==================================================

            const modal =
                document.getElementById(
                    "resetRequestModal"
                );


            if (!modal) {
                return;
            }


            const modalName =
                document.getElementById(
                    "resetModalName"
                );


            const modalIdentifier =
                document.getElementById(
                    "resetModalIdentifier"
                );


            const modalContact =
                document.getElementById(
                    "resetModalContact"
                );


            const modalCreated =
                document.getElementById(
                    "resetModalCreated"
                );


            const modalStatus =
                document.getElementById(
                    "resetModalStatus"
                );


            const modalResolvedBy =
                document.getElementById(
                    "resetModalResolvedBy"
                );


            const modalMessage =
                document.getElementById(
                    "resetModalMessage"
                );


            const modalAction =
                document.getElementById(
                    "resetRequestModalAction"
                );


            const modalBackdrop =
                modal.querySelector(
                    ".request-modal-backdrop"
                );


            let currentRow = null;


            function statusBadge(status) {

                const meta =
                    STATUS_META[status] ||
                    STATUS_META.Pending;

                return (
                    '<span class="badge ' +
                    meta.className +
                    '">' +
                    meta.label +
                    "</span>"
                );

            }


            function openModal(row) {

                currentRow = row;


                const status =
                    row.dataset.status ||
                    "Pending";


                const meta =
                    STATUS_META[status] ||
                    STATUS_META.Pending;


                modalName.textContent =
                    row.dataset.name || "—";


                modalIdentifier.textContent =
                    row.dataset.identifier || "—";


                modalContact.textContent =
                    row.dataset.contact || "—";


                modalCreated.textContent =
                    row.dataset.created || "—";


                modalStatus.innerHTML =
                    statusBadge(status);


                modalResolvedBy.textContent =
                    row.dataset.resolvedBy || "—";


                modalMessage.textContent =
                    row.dataset.message ||
                    "(no message)";


                modalAction.dataset.status =
                    status;


                modalAction.innerHTML =
                    status === "Resolved"
                        ? '<i class="fa-solid fa-rotate-left"></i> ' +
                            meta.action
                        : '<i class="fa-solid fa-check"></i> ' +
                            meta.action;


                modal.classList.add("is-open");

                modal.setAttribute(
                    "aria-hidden",
                    "false"
                );

                document.body.classList.add(
                    "modal-open"
                );

            }


            function closeModal() {

                modal.classList.remove("is-open");

                modal.setAttribute(
                    "aria-hidden",
                    "true"
                );

                document.body.classList.remove(
                    "modal-open"
                );

                currentRow = null;

            }


            document
                .getElementById("resetRequestModalClose")
                ?.addEventListener(
                    "click",
                    closeModal
                );


            document
                .getElementById("resetRequestModalCloseFooter")
                ?.addEventListener(
                    "click",
                    closeModal
                );


            modalBackdrop?.addEventListener(
                "click",
                closeModal
            );


            document.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key === "Escape" &&
                        modal.classList.contains(
                            "is-open"
                        )
                    ) {
                        closeModal();
                    }

                }
            );


            // ==================================================
            // ROW CLICK → OPEN DETAILS
            // ==================================================

            function bindRow(row) {

                const open = function (event) {

                    if (
                        event.target.closest(
                            "button, a, input, select, textarea"
                        )
                    ) {
                        return;
                    }


                    if (
                        event.type === "keydown" &&
                        event.key !== "Enter" &&
                        event.key !== " "
                    ) {
                        return;
                    }


                    if (event.type === "keydown") {
                        event.preventDefault();
                    }


                    openModal(row);

                };


                row.addEventListener("click", open);

                row.addEventListener("keydown", open);

            }


            resetTable
                .querySelectorAll(
                    "tbody tr.reset-row"
                )
                .forEach(bindRow);


            // ==================================================
            // APPLY A NEW STATUS TO THE ROW
            // ==================================================

            function applyStatus(row, request) {

                const status =
                    request.status ||
                    "Pending";


                const meta =
                    STATUS_META[status] ||
                    STATUS_META.Pending;


                row.dataset.status = status;

                row.dataset.resolvedAt =
                    request.resolved_at || "";

                row.dataset.resolvedBy =
                    request.resolved_by_username || "";


                const badge =
                    row.querySelector(".badge");


                if (badge) {

                    badge.className =
                        "badge " + meta.className;

                    badge.textContent =
                        meta.label;

                }


                const button =
                    row.querySelector(
                        ".reset-action-btn"
                    );


                if (button) {

                    button.dataset.status =
                        status;

                    button.textContent =
                        meta.action;

                }

            }


            // ==================================================
            // SAVE STATUS
            // ==================================================

            async function saveStatus(
                row,
                nextStatus,
                button
            ) {

                const requestId =
                    row.dataset.id;


                const previousStatus =
                    row.dataset.status ||
                    "Pending";


                if (button) {
                    button.disabled = true;
                }


                const formData =
                    new FormData();


                formData.append(
                    "request_id",
                    requestId
                );


                formData.append(
                    "status",
                    nextStatus
                );


                try {

                    const response =
                        await fetch(
                            UPDATE_URL,
                            {
                                method: "POST",
                                body: formData,
                                credentials:
                                    "same-origin"
                            }
                        );


                    const result =
                        await response.json();


                    if (
                        !response.ok ||
                        !result.success
                    ) {

                        throw new Error(
                            result.message ||
                            "Failed to update the request."
                        );

                    }


                    applyStatus(
                        row,
                        result.request
                    );


                    // Keep the pending-count pill in step.

                    if (
                        previousStatus === "Pending" &&
                        result.request.status === "Resolved"
                    ) {

                        pendingCount =
                            Math.max(
                                0,
                                pendingCount - 1
                            );

                        renderPendingCount();

                    } else if (
                        previousStatus === "Resolved" &&
                        result.request.status === "Pending"
                    ) {

                        pendingCount += 1;

                        renderPendingCount();

                    }


                    return true;

                } catch (error) {

                    window.alert(
                        error.message ||
                        "Failed to update the request."
                    );

                    return false;

                } finally {

                    if (button) {
                        button.disabled = false;
                    }

                }

            }


            // ==================================================
            // ROW QUICK-ACTION BUTTONS
            // ==================================================

            resetTable.addEventListener(
                "click",
                function (event) {

                    const button =
                        event.target.closest(
                            ".reset-action-btn"
                        );


                    if (!button) {
                        return;
                    }


                    event.stopPropagation();


                    const row =
                        button.closest(
                            "tr.reset-row"
                        );


                    if (!row) {
                        return;
                    }


                    const status =
                        row.dataset.status ||
                        "Pending";


                    const meta =
                        STATUS_META[status] ||
                        STATUS_META.Pending;


                    const confirmText =
                        status === "Resolved"
                            ? "Reopen this password reset request?"
                            : "Mark this password reset request as resolved? Make sure you have already reset the account.";


                    if (!window.confirm(confirmText)) {
                        return;
                    }


                    saveStatus(
                        row,
                        meta.next,
                        button
                    );

                }
            );


            // ==================================================
            // MODAL ACTION BUTTON
            // ==================================================

            modalAction?.addEventListener(
                "click",
                async function () {

                    if (!currentRow) {
                        return;
                    }


                    const status =
                        currentRow.dataset.status ||
                        "Pending";


                    const meta =
                        STATUS_META[status] ||
                        STATUS_META.Pending;


                    const confirmText =
                        status === "Resolved"
                            ? "Reopen this password reset request?"
                            : "Mark this password reset request as resolved? Make sure you have already reset the account.";


                    if (!window.confirm(confirmText)) {
                        return;
                    }


                    modalAction.disabled = true;


                    const saved =
                        await saveStatus(
                            currentRow,
                            meta.next,
                            null
                        );


                    modalAction.disabled = false;


                    if (saved) {
                        closeModal();
                    }

                }
            );

        }
    );

})();