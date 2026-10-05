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
                "Completed"
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
                    resets: 0,
                    documents: 0
                };


                const byKind = {
                    employee: 0,
                    reset: 0,
                    document: 0
                };


                rows.forEach(
                    function (row) {

                        const kind =
                            row.dataset.kind || "employee";


                        const status =
                            row.dataset.status || "Pending";


                        if (byKind[kind] !== undefined) {
                            byKind[kind]++;
                        }


                        /*
                         * Password resets share the canonical statuses
                         * but never use Approved: a reset is Pending,
                         * Completed once carried out, or Rejected.
                         */

                        if (kind === "reset") {

                            counts.resets++;


                            if (status === "Completed") {
                                counts.completed++;
                            } else if (status === "Rejected") {
                                counts.rejected++;
                            } else {
                                counts.pending++;
                            }


                            return;

                        }


                        /*
                         * Onboarding documents are accepted when approved.
                         */

                        if (kind === "document") {

                            counts.documents++;


                            if (status === "Approved") {
                                counts.approved++;
                            } else if (status === "Rejected") {
                                counts.rejected++;
                            } else {
                                counts.pending++;
                            }


                            return;

                        }


                        if (status === "Approved") {
                            counts.approved++;
                        } else if (status === "Completed") {
                            counts.completed++;
                        } else if (status === "Rejected") {
                            counts.rejected++;
                        } else {
                            counts.pending++;
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
                            next =
                                byKind[value] !== undefined
                                    ? byKind[value]
                                    : 0;
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

                        return "badge-green";


                    case "Rejected":

                        return "badge-red";


                    case "Pending":

                    default:

                        return "badge-gray";

                }

            }


            // ==================================================
            // REQUEST MODAL (unified — employee / reset / document)
            // --------------------------------------------------
            // One confirmation modal for every request type:
            //   Step 1 — read-only details (fields adapt per kind)
            //   Step 2 — status selection + HR remarks
            // The statuses offered in Step 2 differ per kind: the three
            // request types share the same four-state vocabulary but
            // expose different actions.
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


            const requestStatusList =
                document.getElementById("requestStatusList");


            const requestReadonlyMessage =
                document.getElementById("requestReadonlyMessage");


            const requestModalEyebrow =
                document.getElementById("requestModalEyebrow");


            const requestModalTitle =
                document.getElementById("requestModalTitle");


            const modalPartyHeading =
                document.getElementById("modalPartyHeading");


            const modalPartyLabelOne =
                document.getElementById("modalPartyLabelOne");


            const modalPartyLabelTwo =
                document.getElementById("modalPartyLabelTwo");


            const modalPartyLabelThree =
                document.getElementById("modalPartyLabelThree");


            const employeeName =
                document.getElementById("modalEmployeeName");


            const employeeNumber =
                document.getElementById("modalEmployeeNumber");


            const department =
                document.getElementById("modalDepartment");


            const requestTypeLabel =
                document.getElementById("modalRequestTypeLabel");


            const requestType =
                document.getElementById("modalRequestType");


            const requestCategoryItem =
                document.getElementById("modalRequestCategoryItem");


            const requestCategory =
                document.getElementById("modalRequestCategory");


            const requestDateLabel =
                document.getElementById("modalRequestDateLabel");


            const requestDate =
                document.getElementById("modalRequestDate");


            const requestDueItem =
                document.getElementById("modalRequestDueItem");


            const requestDue =
                document.getElementById("modalRequestDue");


            const requestPriorityItem =
                document.getElementById("modalRequestPriorityItem");


            const requestPriority =
                document.getElementById("modalRequestPriority");


            const requestAssignedByItem =
                document.getElementById("modalRequestAssignedByItem");


            const requestAssignedBy =
                document.getElementById("modalRequestAssignedBy");


            const requestHandledByItem =
                document.getElementById("modalRequestHandledByItem");


            const requestHandledBy =
                document.getElementById("modalRequestHandledBy");


            const requestStatus =
                document.getElementById("modalRequestStatus");


            const subjectSection =
                document.getElementById("modalSubjectSection");


            const requestSubject =
                document.getElementById("modalRequestSubject");


            const detailsHeading =
                document.getElementById("modalDetailsHeading");


            const requestDescription =
                document.getElementById("modalRequestDescription");


            const submissionSection =
                document.getElementById("modalSubmissionSection");


            const submissionText =
                document.getElementById("modalSubmissionText");


            const submissionFile =
                document.getElementById("modalSubmissionFile");


            const submissionFileName =
                document.getElementById("modalSubmissionFileName");


            const submissionFileSize =
                document.getElementById("modalSubmissionFileSize");


            const submissionAt =
                document.getElementById("modalSubmissionAt");


            const remarksSection =
                document.getElementById("modalRemarksSection");


            const requestRemarks =
                document.getElementById("modalRequestRemarks");

            const STATUS_LABELS = {
                Pending: "Request is waiting for HR review.",
                Approved: "Request has been approved by HR.",
                Rejected: "Request has been rejected by HR.",
                Completed: "Request has been completed."
            };


            /*
             * Per-kind presentation and behaviour.
             *
             *   options()  — the statuses HR may set from the current one
             *                (empty array = nothing left to do).
             *   editable() — whether the confirmation step is interactive.
             *
             * The three request types share the canonical four statuses but
             * expose different actions: onboarding documents finish at
             * Approved, while password resets skip the Approved stage.
             */

            const REQUEST_KINDS = {

                employee: {
                    eyebrow: "Employee Request",
                    title: "Request Details",
                    partyHeading: "Employee Information",
                    partyLabels: [
                        "Employee",
                        "Employee Number",
                        "Department"
                    ],
                    requestTypeLabel: "Request Type",
                    dateLabel: "Date Submitted",
                    detailsHeading: "Request Details",
                    showSubject: true,
                    showSubmission: false,
                    showCategory: false,
                    showDue: false,
                    showPriority: false,
                    showAssignedBy: false,
                    showHandledBy: false,
                    showRemarks: true,
                    options: function () {
                        return [
                            "Pending",
                            "Approved",
                            "Rejected",
                            "Completed"
                        ];
                    },
                    editable: function (status) {
                        return status === "Pending";
                    }
                },

                reset: {
                    eyebrow: "Password Reset Request",
                    title: "Request Details",
                    partyHeading: "Requester Information",
                    partyLabels: [
                        "Full Name",
                        "Username / Employee ID",
                        "Contact"
                    ],
                    requestTypeLabel: "Request Type",
                    dateLabel: "Date Requested",
                    detailsHeading: "Message to HR",
                    showSubject: false,
                    showSubmission: false,
                    showCategory: false,
                    showDue: false,
                    showPriority: false,
                    showAssignedBy: false,
                    showHandledBy: true,
                    showRemarks: false,
                    options: function (status) {
                        if (status === "Pending") {
                            return ["Completed", "Rejected"];
                        }
                        return [];
                    },
                    editable: function (status) {
                        return status === "Pending";
                    }
                },

                document: {
                    eyebrow: "Onboarding Document Request",
                    title: "Document Details",
                    partyHeading: "Employee Information",
                    partyLabels: [
                        "Employee",
                        "Employee Number",
                        "Department"
                    ],
                    requestTypeLabel: "Document",
                    dateLabel: "Date Requested",
                    detailsHeading: "Instructions",
                    showSubject: false,
                    showSubmission: true,
                    showCategory: true,
                    showDue: true,
                    showPriority: true,
                    showAssignedBy: true,
                    showHandledBy: false,
                    showRemarks: true,
                    options: function (status, data) {
                        if (status === "Pending" && data.canReview) {
                            return ["Approved", "Rejected"];
                        }
                        return [];
                    },
                    editable: function (status, data) {
                        return this.options(status, data).length > 0;
                    }
                }

            };


            let requestModalStep = 1;

            let currentRequestRow = null;

            let currentRequestId = null;

            let currentRequestKind = "employee";

            let requestIsEditable = false;

            let selectedStatus = "Pending";


            // ==================================================
            // PARSE DOCUMENT PAYLOAD (JSON blob on the row)
            // ==================================================

            function parseDocumentPayload(row) {

                if (!row) {
                    return {};
                }


                try {

                    return JSON.parse(
                        row.dataset.document || "{}"
                    );

                } catch (error) {

                    console.error(
                        "Invalid document payload:",
                        error
                    );

                    return {};

                }

            }


            function requestData(row, kind) {

                if (kind === "document") {
                    return parseDocumentPayload(row);
                }


                return Object.assign({}, row.dataset);

            }


            // ==================================================
            // BUILD STATUS LIST (per kind / current status)
            // ==================================================

            function buildStatusList(options) {

                if (!requestStatusList) {
                    return;
                }


                requestStatusList.innerHTML = "";


                options.forEach(
                    function (status) {

                        const button =
                            document.createElement("button");

                        button.type = "button";
                        button.className = "request-status-item";
                        button.dataset.status = status;


                        const dot =
                            document.createElement("span");

                        dot.className = "status-dot";


                        const body =
                            document.createElement("div");


                        const strong =
                            document.createElement("strong");

                        strong.textContent = status;


                        const span =
                            document.createElement("span");

                        span.textContent =
                            STATUS_LABELS[status] || "";


                        body.appendChild(strong);
                        body.appendChild(span);

                        button.appendChild(dot);
                        button.appendChild(body);

                        requestStatusList.appendChild(button);

                    }
                );

            }


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
            // MODAL EDITABILITY
            // ==================================================

            function updateModalEditability() {

                document
                    .querySelectorAll(".request-status-item")
                    .forEach(
                        function (item) {

                            item.classList.toggle(
                                "is-disabled",
                                !requestIsEditable
                            );


                            item.setAttribute(
                                "aria-disabled",
                                requestIsEditable ? "false" : "true"
                            );

                        }
                    );


                if (requestRemarks) {
                    requestRemarks.readOnly = !requestIsEditable;
                }

                if (requestReadonlyMessage) {
                    requestReadonlyMessage.hidden = requestIsEditable;
                }


                if (requestModalSave) {

                    requestModalSave.hidden =
                        !requestIsEditable ||
                        (requestModalStep !== 2 &&
                            currentRequestKind !== "reset");

                }

            }


            // ==================================================
            // SAVE BUTTON RESET
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
            // STEP RESET
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
            // SET TEXT (with em-dash fallback)
            // ==================================================

            function setText(element, value) {

                if (!element) {
                    return;
                }


                element.textContent =
                    (value === undefined ||
                        value === null ||
                        value === "")
                        ? "—"
                        : value;

            }


            // ==================================================
            // APPLY KIND CONFIG TO STEP 1
            // ==================================================

            function applyKindConfig(kind) {

                const config =
                    REQUEST_KINDS[kind] || REQUEST_KINDS.employee;


                if (requestModalEyebrow) {
                    requestModalEyebrow.textContent = config.eyebrow;
                }


                if (requestModalTitle) {
                    requestModalTitle.textContent = config.title;
                }


                if (modalPartyHeading) {
                    modalPartyHeading.textContent =
                        config.partyHeading;
                }


                [
                    [modalPartyLabelOne, config.partyLabels[0]],
                    [modalPartyLabelTwo, config.partyLabels[1]],
                    [modalPartyLabelThree, config.partyLabels[2]]
                ].forEach(
                    function (pair) {

                        if (pair[0]) {
                            pair[0].textContent = pair[1];
                        }

                    }
                );


                if (requestTypeLabel) {
                    requestTypeLabel.textContent =
                        config.requestTypeLabel;
                }


                if (requestDateLabel) {
                    requestDateLabel.textContent = config.dateLabel;
                }


                if (detailsHeading) {
                    detailsHeading.textContent =
                        config.detailsHeading;
                }


                if (subjectSection) {
                    subjectSection.hidden = !config.showSubject;
                }


                if (submissionSection) {
                    submissionSection.hidden = !config.showSubmission;
                }


                if (requestCategoryItem) {
                    requestCategoryItem.hidden = !config.showCategory;
                }


                if (requestDueItem) {
                    requestDueItem.hidden = !config.showDue;
                }


                if (requestPriorityItem) {
                    requestPriorityItem.hidden = !config.showPriority;
                }


                if (requestAssignedByItem) {
                    requestAssignedByItem.hidden =
                        !config.showAssignedBy;
                }


                if (requestHandledByItem) {
                    requestHandledByItem.hidden =
                        !config.showHandledBy;
                }


                if (remarksSection) {
                    remarksSection.hidden = !config.showRemarks;
                }

            }


            // ==================================================
            // FILL STEP 1 (per kind)
            // ==================================================

            function fillStepOne(kind, data) {

                applyKindConfig(kind);


                if (kind === "document") {

                    setText(employeeName, data.employeeName);
                    setText(employeeNumber, data.employeeNumber);
                    setText(department, data.department);

                    setText(requestType, data.title);
                    setText(requestCategory, data.type);
                    setText(requestDate, data.assignedAt);
                    setText(
                        requestDue,
                        data.hasDue ? data.dueDate : "No due date"
                    );
                    setText(requestPriority, data.priority);
                    setText(requestAssignedBy, data.assignedBy);

                    setText(requestDescription, data.instruction);


                    if (submissionText) {

                        const hasText =
                            !!(data.responseText &&
                                data.responseText.trim());

                        submissionText.hidden = !hasText;

                        submissionText.textContent =
                            hasText ? data.responseText : "";

                    }


                    if (submissionFile) {

                        const hasFile =
                            !!(data.hasFile && data.fileUrl);

                        submissionFile.hidden = !hasFile;

                        if (hasFile) {
                            submissionFile.href = data.fileUrl;
                        }

                    }


                    if (submissionFileName) {
                        submissionFileName.textContent =
                            data.fileName || "document";
                    }


                    if (submissionFileSize) {
                        submissionFileSize.textContent =
                            data.fileSize || "";
                    }


                    if (submissionAt) {

                        submissionAt.textContent =
                            (data.submittedAt &&
                                data.submittedAt !== "—")
                                ? "Submitted " + data.submittedAt
                                : "";

                    }


                    return;

                }


                if (kind === "reset") {

                    setText(employeeName, data.name);
                    setText(employeeNumber, data.identifier);
                    setText(department, data.contact);

                    setText(requestType, "Password Reset");
                    setText(requestDate, data.date);
                    setText(requestHandledBy, data.resolvedBy);
                    setText(requestDescription, data.message);


                    return;

                }


                setText(employeeName, data.name);
                setText(employeeNumber, data.employeeNumber);
                setText(department, data.department);

                setText(requestType, data.requestType);
                setText(requestDate, data.date);
                setText(requestSubject, data.subject);
                setText(requestDescription, data.description);

            }


            // ==================================================
            // OPEN MODAL
            // ==================================================

            function openRequestModal(row, kind) {

                const config =
                    REQUEST_KINDS[kind] || REQUEST_KINDS.employee;


                const data = requestData(row, kind);


                const originalStatus =
                    data.status || "Pending";


                currentRequestRow = row;

                currentRequestKind = kind;

                currentRequestId =
                    kind === "document"
                        ? data.id
                        : (row.dataset.id || null);


                selectedStatus = originalStatus;


                requestIsEditable =
                    config.editable(originalStatus, data);


                resetModalStep();

                resetSaveButton();

                fillStepOne(kind, data);

                if (kind === "reset") {
                    requestStepTwo.hidden = false;
                    requestModalNext.hidden = true;
                    requestModalBack.hidden = true;
                }


                buildStatusList(
                    config.options(originalStatus, data)
                );


                highlightStatus(originalStatus);


                if (requestStatus) {

                    requestStatus.textContent = originalStatus;

                    requestStatus.className =
                        "badge " + getStatusClass(originalStatus);

                }


                if (requestRemarks) {
                    requestRemarks.value = kind === "reset"
                        ? ""
                        : data.hrRemarks || data.reviewRemarks || "";
                    requestRemarks.readOnly = false;
                }

                if (requestReadonlyMessage) {
                    requestReadonlyMessage.hidden = requestIsEditable;
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
            // CLOSE MODAL
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

                currentRequestKind = "employee";

                requestIsEditable = false;

                selectedStatus = "Pending";


                if (requestRemarks) {
                    requestRemarks.value = "";
                    requestRemarks.readOnly = false;
                }


                resetSaveButton();

                resetModalStep();

            }


            // ==================================================
            // VIEW BUTTON → OPEN
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


                    openRowModal(row);

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


                    if (!requestIsEditable) {
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

                    if (
                        requestModalStep !== 1 ||
                        currentRequestKind === "reset"
                    ) {
                        return;
                    }


                    requestStepOne.hidden = true;

                    requestStepTwo.hidden = false;

                    requestModalStep = 2;

                    requestModalNext.hidden = true;


                    if (requestModalBack) {
                        requestModalBack.hidden = false;
                    }


                    if (requestModalSave) {
                        requestModalSave.hidden =
                            !requestIsEditable;
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
            // ROW STATUS UPDATE (in place, after a save)
            // ==================================================

            function setRowStatus(row, status) {

                if (!row) {
                    return;
                }


                row.dataset.status = status;


                const badge =
                    row.querySelector(".badge");


                if (badge) {

                    badge.textContent = status;

                    badge.className =
                        "badge " + getStatusClass(status);

                }

            }


            function updateRequestRow(row, request) {

                if (!row || !request) {
                    return;
                }


                row.dataset.hrRemarks =
                    request.hr_remarks || "";

                row.dataset.id =
                    request.request_id || row.dataset.id;


                setRowStatus(row, request.status || "Pending");

            }

            function updateDocumentRow(row, status, remarks) {

                const data = parseDocumentPayload(row);


                data.status = status;

                data.statusLabel = status;

                data.statusClass = getStatusClass(status);

                data.canReview = false;

                if (remarks) {
                    data.reviewRemarks = remarks;
                }


                row.dataset.document = JSON.stringify(data);

            }


            // ==================================================
            // SAVE (dispatch per kind)
            // ==================================================

            async function saveRequest() {

                const hrRemarks =
                    requestRemarks?.value.trim() || "";


                /*
                 * Employee request — its own endpoint.
                 */

                if (currentRequestKind === "employee") {

                    const formData = new FormData();

                    formData.append("request_id", currentRequestId);

                    formData.append("status", selectedStatus);

                    formData.append("hr_remarks", hrRemarks);

                    const response = await fetch(
                        "?page=employee-requests-update",
                        {
                            method: "POST",
                            body: formData,
                            credentials: "same-origin"
                        }
                    );


                    const result = await response.json();


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


                    if (requestRemarks) {
                        requestRemarks.value =
                            result.request.hr_remarks || "";
                    }


                    return result.request.status;

                }


                /*
                 * Password reset requests are actioned once, while Pending.
                 */

                if (currentRequestKind === "reset") {

                    const formData = new FormData();

                    formData.append("request_id", currentRequestId);

                    formData.append("status", selectedStatus);


                    const response = await fetch(
                        "?page=password-reset-request-update",
                        {
                            method: "POST",
                            body: formData,
                            credentials: "same-origin"
                        }
                    );


                    const result = await response.json();


                    if (!response.ok || !result.success) {

                        throw new Error(
                            result.message ||
                            "Failed to update the password reset request."
                        );

                    }


                    setRowStatus(currentRequestRow, selectedStatus);


                    return selectedStatus;

                }


                /*
                 * Onboarding document — the chosen status maps to the
                 * matching Onboarding endpoint.
                 */

                const endpoint =
                    selectedStatus === "Approved"
                        ? "?page=onboarding-verify-document"
                        : "?page=onboarding-reject-document";


                const formData = new FormData();

                formData.append("document_id", currentRequestId);

                formData.append("remarks", hrRemarks);


                const response = await fetch(
                    endpoint,
                    {
                        method: "POST",
                        body: formData,
                        credentials: "same-origin"
                    }
                );


                const result = await response.json();


                if (!response.ok || !result.success) {

                    throw new Error(
                        result.message ||
                        "Failed to update the document."
                    );

                }


                setRowStatus(currentRequestRow, selectedStatus);

                updateDocumentRow(
                    currentRequestRow,
                    selectedStatus,
                    hrRemarks
                );


                return selectedStatus;

            }


            requestModalSave?.addEventListener(
                "click",
                async function () {

                    if (!currentRequestId) {

                        alert(
                            "Unable to identify this request."
                        );

                        return;

                    }


                    if (!requestIsEditable) {
                        return;
                    }


                    if (
                        currentRequestKind === "employee" &&
                        selectedStatus === "Pending"
                    ) {

                        alert(
                            "Please select Approved or Rejected before saving."
                        );

                        return;

                    }

                    if (
                        currentRequestKind === "reset" &&
                        selectedStatus === "Pending"
                    ) {

                        alert(
                            "Please select Completed or Rejected before saving."
                        );

                        return;

                    }


                    const confirmed =
                        window.confirm(
                            `Mark this request as "${selectedStatus}"?`
                        );


                    if (!confirmed) {
                        return;
                    }


                    requestModalSave.disabled = true;

                    requestModalSave.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';


                    try {

                        const savedStatus =
                            await saveRequest();


                        requestIsEditable = false;

                        selectedStatus = savedStatus;


                        if (requestStatus) {

                            requestStatus.textContent =
                                savedStatus;

                            requestStatus.className =
                                "badge " +
                                getStatusClass(savedStatus);

                        }


                        updateModalEditability();

                        requestModalSave.hidden = true;


                        applyFilters();

                        refreshSummaryCounts();


                        alert(
                            "Request updated successfully."
                        );

                    } catch (error) {

                        console.error(
                            "Request update error:",
                            error
                        );


                        alert(
                            error.message ||
                            "Failed to save the request."
                        );


                        requestModalSave.disabled = false;

                        requestModalSave.innerHTML =
                            '<i class="fa-solid fa-check"></i> Save Changes';

                    }

                }
            );


            // ==================================================
            // ROW CLICK → OPEN THE UNIFIED MODAL
            // ==================================================

            function openRowModal(row) {

                const kind =
                    row.dataset.kind || "employee";


                openRequestModal(row, kind);

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
