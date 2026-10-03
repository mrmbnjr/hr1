// ==========================================================
// RAM-YUM — Employee Requests (unified queue)
// ----------------------------------------------------------
// Employee requests and password reset requests are rendered
// in a single table. This controller handles filtering,
// sorting, pagination, the summary stat-card filters, and the
// two detail modals (dispatched by the row's data-kind).
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


            const tbody =
                table.querySelector("tbody");


            const rows =
                Array.from(
                    tbody.querySelectorAll(
                        "tr.request-row"
                    )
                );


            // ==================================================
            // FILTERS
            // ==================================================

            const searchInput =
                document.getElementById("requestSearch");


            const categoryFilter =
                document.getElementById("categoryFilter");


            const statusFilter =
                document.getElementById("statusFilter");


            const sortFilter =
                document.getElementById("sortFilter");


            // ==================================================
            // PAGINATION
            // ==================================================

            const resultsCount =
                document.getElementById("resultsCount");


            const pageNumbers =
                document.getElementById("pageNumbers");


            const prevPageBtn =
                document.getElementById("prevPage");


            const nextPageBtn =
                document.getElementById("nextPage");


            const filterEmptyRow =
                document.getElementById("filterEmpty");


            const PAGE_SIZE = 8;


            let currentPage = 1;


            const STATUS_ORDER = [
                "Pending",
                "Approved",
                "Rejected",
                "Completed",
                "Resolved"
            ];


            // ==================================================
            // SUMMARY CARDS
            // ==================================================

            const summaryCards =
                Array.from(
                    document.querySelectorAll(
                        ".summary-card"
                    )
                );


            function setActiveCard(card) {

                summaryCards.forEach(
                    function (item) {

                        item.classList.toggle(
                            "active",
                            item === card
                        );

                    }
                );

            }


            function refreshSummaryCounts() {

                const counts = {
                    total: rows.length,
                    pending: 0,
                    approved: 0,
                    completed: 0,
                    rejected: 0,
                    resets: 0
                };


                rows.forEach(
                    function (row) {

                        const status =
                            row.dataset.status ||
                            "Pending";


                        if (row.dataset.kind === "reset") {
                            counts.resets++;
                        }


                        if (status === "Pending") {
                            counts.pending++;
                        } else if (status === "Approved") {
                            counts.approved++;
                        } else if (status === "Completed") {
                            counts.completed++;
                        } else if (status === "Rejected") {
                            counts.rejected++;
                        }

                    }
                );


                summaryCards.forEach(
                    function (card) {

                        const kind =
                            card.dataset.filterKind;


                        const value =
                            card.dataset.filterValue;


                        let next = null;


                        if (kind === "all") {
                            next = counts.total;
                        } else if (kind === "category") {
                            next = counts.resets;
                        } else if (kind === "status") {
                            next =
                                counts[
                                    value.toLowerCase()
                                ];
                        }


                        if (
                            next === null ||
                            next === undefined
                        ) {
                            return;
                        }


                        const valueEl =
                            card.querySelector(
                                ".stat-content strong"
                            );


                        if (valueEl) {
                            valueEl.textContent =
                                String(next);
                        }

                    }
                );

            }


            summaryCards.forEach(
                function (card) {

                    card.addEventListener(
                        "click",
                        function () {

                            const kind =
                                card.dataset.filterKind;


                            const value =
                                card.dataset.filterValue;


                            if (kind === "category") {

                                if (categoryFilter) {
                                    categoryFilter.value = value;
                                }

                                if (statusFilter) {
                                    statusFilter.value = "All";
                                }

                            } else if (kind === "status") {

                                if (statusFilter) {
                                    statusFilter.value = value;
                                }

                                if (categoryFilter) {
                                    categoryFilter.value = "All";
                                }

                            } else {

                                if (categoryFilter) {
                                    categoryFilter.value = "All";
                                }

                                if (statusFilter) {
                                    statusFilter.value = "All";
                                }

                            }


                            if (searchInput) {
                                searchInput.value = "";
                            }


                            currentPage = 1;

                            setActiveCard(card);

                            applyFilters();

                        }
                    );

                }
            );


            // ==================================================
            // DATE HELPERS
            // ==================================================

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


            function compareDates(dateA, dateB) {

                return parseDate(dateA) - parseDate(dateB);

            }


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


            // Both request kinds expose their timestamp via data-date.

            function rowDate(row) {

                return row.dataset.date || "";

            }


            // ==================================================
            // FILTER + SORT
            // ==================================================

            function getVisibleRows() {

                const search =
                    (searchInput?.value || "")
                        .toLowerCase()
                        .trim();


                const category =
                    categoryFilter?.value || "All";


                const status =
                    statusFilter?.value || "All";


                const sort =
                    sortFilter?.value || "newest";


                const visible =
                    rows.filter(
                        function (row) {

                            const matchesSearch =
                                !search ||
                                (row.dataset.name || "")
                                    .toLowerCase()
                                    .includes(search);


                            const matchesCategory =
                                category === "All" ||
                                row.dataset.kind === category;


                            const matchesStatus =
                                status === "All" ||
                                row.dataset.status === status;


                            return (
                                matchesSearch &&
                                matchesCategory &&
                                matchesStatus
                            );

                        }
                    );


                visible.sort(
                    function (a, b) {

                        switch (sort) {

                            case "oldest":

                                return compareDates(
                                    rowDate(a),
                                    rowDate(b)
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
                                    rowDate(b),
                                    rowDate(a)
                                );

                        }

                    }
                );


                return visible;

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


                visible.forEach(
                    function (row) {
                        tbody.appendChild(row);
                    }
                );


                rows.forEach(
                    function (row) {
                        row.style.display = "none";
                    }
                );


                pageRows.forEach(
                    function (row) {
                        row.style.display = "";
                    }
                );


                if (filterEmptyRow) {

                    filterEmptyRow.hidden =
                        !(
                            rows.length > 0 &&
                            visible.length === 0
                        );

                }


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


            prevPageBtn?.addEventListener(
                "click",
                function () {

                    if (currentPage <= 1) {
                        return;
                    }

                    currentPage--;

                    applyFilters();

                }
            );


            nextPageBtn?.addEventListener(
                "click",
                function () {

                    const totalPages =
                        Math.max(
                            1,
                            Math.ceil(
                                getVisibleRows().length /
                                PAGE_SIZE
                            )
                        );


                    if (currentPage >= totalPages) {
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
                { el: searchInput, event: "input" },
                { el: categoryFilter, event: "change" },
                { el: statusFilter, event: "change" },
                { el: sortFilter, event: "change" }

            ].forEach(
                function (control) {

                    if (!control.el) {
                        return;
                    }


                    control.el.addEventListener(
                        control.event,
                        function () {

                            currentPage = 1;

                            // Manual changes drop the card highlight
                            // (sorting keeps the active status card).

                            if (control.el !== sortFilter) {
                                setActiveCard(null);
                            }

                            applyFilters();

                        }
                    );

                }
            );


            // ==================================================
            // STATUS CLASS
            // ==================================================

            function getStatusClass(status) {

                switch (status) {

                    case "Approved":

                    case "Completed":

                    case "Resolved":

                        return "badge-green";


                    case "Rejected":

                        return "badge-red";


                    case "Pending":

                    default:

                        return "badge-gray";

                }

            }


            // ==================================================
            // EMPLOYEE REQUEST MODAL
            // ==================================================

            const requestModal =
                document.getElementById("requestModal");


            const requestModalClose =
                document.getElementById("requestModalClose");


            const requestModalBackdrop =
                requestModal?.querySelector(
                    ".request-modal-backdrop"
                );


            const requestModalNext =
                document.getElementById("requestModalNext");


            const requestModalBack =
                document.getElementById("requestModalBack");


            const requestModalSave =
                document.getElementById("requestModalSave");


            const requestStepOne =
                document.getElementById("requestStepOne");


            const requestStepTwo =
                document.getElementById("requestStepTwo");


            const requestReadonlyMessage =
                document.getElementById("requestReadonlyMessage");


            const employeeName =
                document.getElementById("modalEmployeeName");


            const employeeNumber =
                document.getElementById("modalEmployeeNumber");


            const department =
                document.getElementById("modalDepartment");


            const requestType =
                document.getElementById("modalRequestType");


            const requestDate =
                document.getElementById("modalRequestDate");


            const requestStatus =
                document.getElementById("modalRequestStatus");


            const requestSubject =
                document.getElementById("modalRequestSubject");


            const requestDescription =
                document.getElementById("modalRequestDescription");


            const requestRemarks =
                document.getElementById("modalRequestRemarks");


            let requestModalStep = 1;

            let currentRequestRow = null;

            let currentRequestId = null;

            let requestIsFinalized = false;

            let selectedStatus = "Pending";


            // ==================================================
            // HIGHLIGHT STATUS
            // ==================================================

            function highlightStatus(status) {

                document
                    .querySelectorAll(".request-status-item")
                    .forEach(
                        function (item) {

                            item.classList.toggle(
                                "active",
                                item.dataset.status === status
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


                document
                    .querySelectorAll(".request-status-item")
                    .forEach(
                        function (item) {

                            item.classList.toggle(
                                "is-disabled",
                                !editable
                            );


                            item.setAttribute(
                                "aria-disabled",
                                editable ? "false" : "true"
                            );

                        }
                    );


                if (requestRemarks) {
                    requestRemarks.readOnly = !editable;
                }


                if (requestReadonlyMessage) {
                    requestReadonlyMessage.hidden = editable;
                }


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


                requestModalSave.hidden = true;

                requestModalSave.disabled = false;

                requestModalSave.innerHTML =
                    '<i class="fa-solid fa-check"></i> Save Changes';

            }


            // ==================================================
            // RESET MODAL STEP
            // ==================================================

            function resetModalStep() {

                requestModalStep = 1;


                if (requestStepOne) {
                    requestStepOne.hidden = false;
                }


                if (requestStepTwo) {
                    requestStepTwo.hidden = true;
                }


                if (requestModalNext) {

                    requestModalNext.hidden = false;

                    requestModalNext.innerHTML =
                        'Next <i class="fa-solid fa-arrow-right"></i>';

                }


                if (requestModalBack) {
                    requestModalBack.hidden = true;
                }


                if (requestModalSave) {
                    requestModalSave.hidden = true;
                }

            }


            // ==================================================
            // OPEN REQUEST MODAL
            // ==================================================

            function openRequestModal(row) {

                const data = row.dataset;


                currentRequestRow = row;

                currentRequestId = data.id || null;


                const originalStatus =
                    data.status || "Pending";


                selectedStatus = originalStatus;

                requestIsFinalized =
                    originalStatus !== "Pending";


                resetModalStep();


                if (employeeName) {
                    employeeName.textContent =
                        data.name || "Unknown Employee";
                }


                if (employeeNumber) {
                    employeeNumber.textContent =
                        data.employeeNumber || "—";
                }


                if (department) {
                    department.textContent =
                        data.department || "—";
                }


                if (requestType) {
                    requestType.textContent =
                        data.requestType || "—";
                }


                if (requestDate) {
                    requestDate.textContent =
                        data.date || "—";
                }


                if (requestSubject) {
                    requestSubject.textContent =
                        data.subject || "No subject provided.";
                }


                if (requestDescription) {
                    requestDescription.textContent =
                        data.description ||
                        "No additional details provided.";
                }


                if (requestStatus) {

                    requestStatus.textContent =
                        selectedStatus;


                    requestStatus.className =
                        "badge " +
                        getStatusClass(selectedStatus);

                }


                highlightStatus(selectedStatus);


                if (requestRemarks) {
                    requestRemarks.value =
                        data.hrRemarks || "";
                }


                updateModalEditability();


                requestModal.classList.add("is-open");

                requestModal.setAttribute(
                    "aria-hidden",
                    "false"
                );

                document.body.classList.add("modal-open");

            }


            // ==================================================
            // CLOSE REQUEST MODAL
            // ==================================================

            function closeRequestModal() {

                if (!requestModal) {
                    return;
                }


                requestModal.classList.remove("is-open");

                requestModal.setAttribute(
                    "aria-hidden",
                    "true"
                );

                document.body.classList.remove("modal-open");


                currentRequestRow = null;

                currentRequestId = null;

                requestIsFinalized = false;

                selectedStatus = "Pending";


                if (requestRemarks) {
                    requestRemarks.value = "";
                    requestRemarks.readOnly = false;
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
                        button.closest(".request-row");


                    if (!row) {
                        return;
                    }


                    openRequestModal(row);

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
                        !requestModal ||
                        !requestModal.classList.contains("is-open")
                    ) {
                        return;
                    }


                    if (!isRequestEditable()) {
                        return;
                    }


                    selectedStatus =
                        statusItem.dataset.status ||
                        "Pending";


                    highlightStatus(selectedStatus);


                    if (requestStatus) {

                        requestStatus.textContent =
                            selectedStatus;


                        requestStatus.className =
                            "badge " +
                            getStatusClass(selectedStatus);

                    }

                }
            );


            requestModalClose?.addEventListener(
                "click",
                closeRequestModal
            );


            requestModalBackdrop?.addEventListener(
                "click",
                closeRequestModal
            );


            // ==================================================
            // NEXT / BACK
            // ==================================================

            requestModalNext?.addEventListener(
                "click",
                function () {

                    if (requestModalStep === 1) {

                        requestStepOne.hidden = true;

                        requestStepTwo.hidden = false;

                        requestModalStep = 2;

                        requestModalNext.hidden = true;


                        if (requestModalBack) {
                            requestModalBack.hidden = false;
                        }


                        if (requestModalSave) {
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

                    requestStepTwo.hidden = true;

                    requestStepOne.hidden = false;

                    requestModalStep = 1;

                    requestModalBack.hidden = true;


                    if (requestModalNext) {
                        requestModalNext.hidden = false;
                    }


                    if (requestModalSave) {
                        requestModalSave.hidden = true;
                    }

                }
            );


            // ==================================================
            // UPDATE TABLE ROW
            // ==================================================

            function updateRequestRow(row, request) {

                if (!row || !request) {
                    return;
                }


                const status =
                    request.status || "Pending";


                const remarks =
                    request.hr_remarks || "";


                row.dataset.status = status;

                row.dataset.hrRemarks = remarks;

                row.dataset.id =
                    request.request_id || row.dataset.id;


                const badge =
                    row.querySelector(".badge");


                if (badge) {

                    badge.textContent = status;

                    badge.className =
                        "badge " +
                        getStatusClass(status);

                }

            }


            // ==================================================
            // SAVE REQUEST
            // ==================================================

            requestModalSave?.addEventListener(
                "click",
                async function () {

                    if (!currentRequestId) {

                        alert(
                            "Unable to identify the employee request."
                        );

                        return;

                    }


                    if (!isRequestEditable()) {
                        return;
                    }


                    if (selectedStatus === "Pending") {

                        alert(
                            "Please select Approved, Rejected, or Completed before saving."
                        );

                        return;

                    }


                    const hrRemarks =
                        requestRemarks?.value.trim() || "";


                    const confirmed =
                        window.confirm(
                            `Are you sure you want to finalize this request as "${selectedStatus}"? Once saved, the request cannot be changed.`
                        );


                    if (!confirmed) {
                        return;
                    }


                    requestModalSave.disabled = true;

                    requestModalSave.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';


                    try {

                        const formData = new FormData();

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
                                "?page=employee-requests-update",
                                {
                                    method: "POST",
                                    body: formData,
                                    credentials: "same-origin"
                                }
                            );


                        const result =
                            await response.json();


                        if (!response.ok || !result.success) {

                            throw new Error(
                                result.message ||
                                "Failed to save the request."
                            );

                        }


                        updateRequestRow(
                            currentRequestRow,
                            result.request
                        );


                        requestIsFinalized = true;

                        selectedStatus = result.request.status;


                        if (requestStatus) {

                            requestStatus.textContent =
                                result.request.status;


                            requestStatus.className =
                                "badge " +
                                getStatusClass(
                                    result.request.status
                                );

                        }


                        highlightStatus(result.request.status);


                        if (requestRemarks) {
                            requestRemarks.value =
                                result.request.hr_remarks || "";
                        }


                        updateModalEditability();

                        requestModalSave.hidden = true;


                        applyFilters();

                        refreshSummaryCounts();


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


                        requestModalSave.disabled = false;

                        requestModalSave.innerHTML =
                            '<i class="fa-solid fa-check"></i> Save Changes';

                    }

                }
            );


            // ==================================================
            // PASSWORD RESET REQUEST MODAL
            // ==================================================

            const UPDATE_URL =
                "?page=password-reset-request-update";


            const RESET_STATUS_META = {

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


            const resetModal =
                document.getElementById("resetRequestModal");


            const resetModalName =
                document.getElementById("resetModalName");


            const resetModalIdentifier =
                document.getElementById("resetModalIdentifier");


            const resetModalContact =
                document.getElementById("resetModalContact");


            const resetModalCreated =
                document.getElementById("resetModalCreated");


            const resetModalStatus =
                document.getElementById("resetModalStatus");


            const resetModalResolvedBy =
                document.getElementById("resetModalResolvedBy");


            const resetModalMessage =
                document.getElementById("resetModalMessage");


            const resetModalAction =
                document.getElementById("resetRequestModalAction");


            const resetModalBackdrop =
                resetModal?.querySelector(
                    ".request-modal-backdrop"
                );


            let currentResetRow = null;


            function resetStatusBadge(status) {

                const meta =
                    RESET_STATUS_META[status] ||
                    RESET_STATUS_META.Pending;


                return (
                    '<span class="badge ' +
                    meta.className +
                    '">' +
                    meta.label +
                    "</span>"
                );

            }


            function openResetModal(row) {

                if (!resetModal) {
                    return;
                }


                currentResetRow = row;


                const status =
                    row.dataset.status || "Pending";


                const meta =
                    RESET_STATUS_META[status] ||
                    RESET_STATUS_META.Pending;


                if (resetModalName) {
                    resetModalName.textContent =
                        row.dataset.name || "—";
                }


                if (resetModalIdentifier) {
                    resetModalIdentifier.textContent =
                        row.dataset.identifier || "—";
                }


                if (resetModalContact) {
                    resetModalContact.textContent =
                        row.dataset.contact || "—";
                }


                if (resetModalCreated) {
                    resetModalCreated.textContent =
                        row.dataset.created || "—";
                }


                if (resetModalStatus) {
                    resetModalStatus.innerHTML =
                        resetStatusBadge(status);
                }


                if (resetModalResolvedBy) {
                    resetModalResolvedBy.textContent =
                        row.dataset.resolvedBy || "—";
                }


                if (resetModalMessage) {
                    resetModalMessage.textContent =
                        row.dataset.message || "(no message)";
                }


                if (resetModalAction) {

                    resetModalAction.dataset.status = status;

                    resetModalAction.innerHTML =
                        status === "Resolved"
                            ? '<i class="fa-solid fa-rotate-left"></i> ' +
                                meta.action
                            : '<i class="fa-solid fa-check"></i> ' +
                                meta.action;

                }


                resetModal.classList.add("is-open");

                resetModal.setAttribute(
                    "aria-hidden",
                    "false"
                );

                document.body.classList.add("modal-open");

            }


            function closeResetModal() {

                if (!resetModal) {
                    return;
                }


                resetModal.classList.remove("is-open");

                resetModal.setAttribute(
                    "aria-hidden",
                    "true"
                );

                document.body.classList.remove("modal-open");


                currentResetRow = null;

            }


            document
                .getElementById("resetRequestModalClose")
                ?.addEventListener("click", closeResetModal);


            document
                .getElementById("resetRequestModalCloseFooter")
                ?.addEventListener("click", closeResetModal);


            resetModalBackdrop?.addEventListener(
                "click",
                closeResetModal
            );


            // ==================================================
            // APPLY A NEW STATUS TO THE RESET ROW
            // ==================================================

            function applyResetStatus(row, request) {

                const status =
                    request.status || "Pending";


                const meta =
                    RESET_STATUS_META[status] ||
                    RESET_STATUS_META.Pending;


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

                    badge.textContent = meta.label;

                }

            }


            // ==================================================
            // SAVE RESET STATUS
            // ==================================================

            async function saveResetStatus(row, nextStatus, button) {

                const requestId =
                    row.dataset.id;


                if (button) {
                    button.disabled = true;
                }


                const formData = new FormData();

                formData.append("request_id", requestId);

                formData.append("status", nextStatus);


                try {

                    const response =
                        await fetch(
                            UPDATE_URL,
                            {
                                method: "POST",
                                body: formData,
                                credentials: "same-origin"
                            }
                        );


                    const result =
                        await response.json();


                    if (!response.ok || !result.success) {

                        throw new Error(
                            result.message ||
                            "Failed to update the request."
                        );

                    }


                    applyResetStatus(row, result.request);

                    refreshSummaryCounts();

                    applyFilters();


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
            // MODAL ACTION BUTTON
            // ==================================================

            resetModalAction?.addEventListener(
                "click",
                async function () {

                    if (!currentResetRow) {
                        return;
                    }


                    const status =
                        currentResetRow.dataset.status ||
                        "Pending";


                    const meta =
                        RESET_STATUS_META[status] ||
                        RESET_STATUS_META.Pending;


                    const confirmText =
                        status === "Resolved"
                            ? "Reopen this password reset request?"
                            : "Mark this password reset request as resolved? Make sure you have already reset the account.";


                    if (!window.confirm(confirmText)) {
                        return;
                    }


                    resetModalAction.disabled = true;


                    const saved =
                        await saveResetStatus(
                            currentResetRow,
                            meta.next,
                            null
                        );


                    resetModalAction.disabled = false;


                    if (saved) {
                        closeResetModal();
                    }

                }
            );


            // ==================================================
            // ROW CLICK → OPEN THE RIGHT MODAL
            // ==================================================

            function openRowModal(row) {

                if ((row.dataset.kind || "employee") === "reset") {
                    openResetModal(row);
                } else {
                    openRequestModal(row);
                }

            }


            rows.forEach(
                function (row) {

                    const openRow =
                        function (event) {

                            if (
                                event.target.closest(
                                    "a, button, input, select, textarea"
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


                            openRowModal(row);

                        };


                    row.addEventListener("click", openRow);

                    row.addEventListener("keydown", openRow);

                }
            );


            // ==================================================
            // ESCAPE KEY
            // ==================================================

            document.addEventListener(
                "keydown",
                function (event) {

                    if (event.key !== "Escape") {
                        return;
                    }


                    if (
                        requestModal &&
                        requestModal.classList.contains("is-open")
                    ) {
                        closeRequestModal();
                    }


                    if (
                        resetModal &&
                        resetModal.classList.contains("is-open")
                    ) {
                        closeResetModal();
                    }

                }
            );


            // ==================================================
            // INITIALIZE
            // ==================================================

            refreshSummaryCounts();

            applyFilters();

        }
    );

})();
