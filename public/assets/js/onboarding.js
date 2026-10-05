/*
|--------------------------------------------------------------------------
| NEW HIRE ONBOARDING
|--------------------------------------------------------------------------
|
| Handles:
| - Employee filtering
| - Sorting
| - Pagination
| - Request Document modal
| - Document request submission
| - Document verification
|
|--------------------------------------------------------------------------
*/

(function () {

    const rows = Array.from(
        document.querySelectorAll(
            '#onboardingTable tbody tr.employee-row'
        )
    );

    const employeeSearch =
        document.getElementById('employeeSearch');

    const departmentFilter =
        document.getElementById('departmentFilter');

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

    const isOnboardingListPage =
        document.getElementById('onboardingTable');


    const PAGE_SIZE = 8;

    let currentPage = 1;


    /*
    |--------------------------------------------------------------------------
    | TABLE FILTERING + PAGINATION
    |--------------------------------------------------------------------------
    */

    if (isOnboardingListPage) {

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

                window.location.href = row.dataset.rowHref;
            };

            row.addEventListener('click', openRow);
            row.addEventListener('keydown', openRow);
        });

        function applyFilters() {

            const searchTerm =
                (employeeSearch?.value ?? '').trim().toLowerCase();

            const department =
                departmentFilter?.value ?? 'All';

            const status =
                statusFilter?.value ?? 'All';

            const sort =
                sortFilter?.value ?? 'newest';


            /*
            |--------------------------------------------------------------------------
            | FILTER
            |--------------------------------------------------------------------------
            */

            let visible = rows.filter(row => {
                const rowName = (row.dataset.fullname || '').toLowerCase();
                const rowEmail = (row.querySelector('.sub-text')?.textContent || '').toLowerCase();

                const matchesSearch =
                    !searchTerm ||
                    rowName.includes(searchTerm) ||
                    rowEmail.includes(searchTerm);

                const matchesDepartment =
                    department === 'All' ||
                    row.dataset.department === department;


                const matchesStatus =
                    status === 'All' ||
                    row.dataset.status === status;


                return (
                    matchesSearch &&
                    matchesDepartment &&
                    matchesStatus
                );

            });


            /*
            |--------------------------------------------------------------------------
            | SORT
            |--------------------------------------------------------------------------
            */

            visible.sort((a, b) => {

                switch (sort) {

                    case 'progress-high':

                        return (
                            Number(b.dataset.progress || 0) -
                            Number(a.dataset.progress || 0)
                        );


                    case 'progress-low':

                        return (
                            Number(a.dataset.progress || 0) -
                            Number(b.dataset.progress || 0)
                        );


                    case 'oldest':

                        return (
                            parseDate(a.dataset.startDate) -
                            parseDate(b.dataset.startDate)
                        );


                    case 'name-az':

                        return (
                            a.dataset.fullname || ''
                        ).localeCompare(
                            b.dataset.fullname || ''
                        );


                    case 'name-za':

                        return (
                            b.dataset.fullname || ''
                        ).localeCompare(
                            a.dataset.fullname || ''
                        );


                    case 'newest':

                    default:

                        return (
                            parseDate(b.dataset.startDate) -
                            parseDate(a.dataset.startDate)
                        );

                }

            });


            /*
            |--------------------------------------------------------------------------
            | REORDER TABLE
            |--------------------------------------------------------------------------
            */

            const tbody =
                document.querySelector(
                    '#onboardingTable tbody'
                );


            visible.forEach(row => {
                tbody.appendChild(row);
            });


            /*
            |--------------------------------------------------------------------------
            | HIDE ALL ROWS
            |--------------------------------------------------------------------------
            */

            rows.forEach(row => {
                row.style.display = 'none';
            });


            /*
            |--------------------------------------------------------------------------
            | PAGINATION
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | RESULT COUNT
            |--------------------------------------------------------------------------
            */

            if (resultsCount) {
                if (visible.length === 0) {
                    resultsCount.textContent = 'No employees found';
                } else {
                    const from = start + 1;
                    const to = Math.min(start + PAGE_SIZE, visible.length);
                    resultsCount.textContent = `Showing ${from}-${to} of ${visible.length} employees`;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | PAGINATION UI
            |--------------------------------------------------------------------------
            */

            renderPagination(totalPages);
        }


        /*
        |--------------------------------------------------------------------------
        | DATE HELPER
        |--------------------------------------------------------------------------
        */

        function parseDate(value) {

            if (!value) {
                return 0;
            }


            const timestamp =
                Date.parse(value);


            return Number.isNaN(timestamp)
                ? 0
                : timestamp;
        }


        /*
        |--------------------------------------------------------------------------
        | PAGINATION BUTTONS
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | PREVIOUS PAGE
        |--------------------------------------------------------------------------
        */

        prevPageBtn?.addEventListener(
            'click',
            () => {

                if (currentPage > 1) {

                    currentPage--;

                    applyFilters();

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | NEXT PAGE
        |--------------------------------------------------------------------------
        */

        nextPageBtn?.addEventListener(
            'click',
            () => {
                const totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
                const filtered = rows.filter(row => {
                    const department = departmentFilter?.value ?? 'All';
                    const status = statusFilter?.value ?? 'All';
                    const searchTerm = (employeeSearch?.value ?? '').trim().toLowerCase();
                    const rowName = (row.dataset.fullname || '').toLowerCase();
                    const rowEmail = (row.querySelector('.sub-text')?.textContent || '').toLowerCase();
                    const matchesSearch = !searchTerm || rowName.includes(searchTerm) || rowEmail.includes(searchTerm);
                    const matchesDepartment = department === 'All' || row.dataset.department === department;
                    const matchesStatus = status === 'All' || row.dataset.status === status;
                    return matchesSearch && matchesDepartment && matchesStatus;
                });

                const totalFilteredPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));

                if (currentPage < totalFilteredPages) {
                    currentPage++;
                    applyFilters();
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | FILTER EVENTS
        |--------------------------------------------------------------------------
        */

        employeeSearch?.addEventListener(
            'input',
            () => {
                currentPage = 1;
                applyFilters();
            }
        );

        departmentFilter?.addEventListener(
            'change',
            () => {

                currentPage = 1;

                applyFilters();

            }
        );


        statusFilter?.addEventListener(
            'change',
            () => {

                currentPage = 1;

                applyFilters();

            }
        );


        sortFilter?.addEventListener(
            'change',
            () => {

                currentPage = 1;

                applyFilters();

            }
        );


        /*
        |--------------------------------------------------------------------------
        | INITIAL LOAD
        |--------------------------------------------------------------------------
        */

        applyFilters();
    }

})();


/*
|--------------------------------------------------------------------------
| DOCUMENT REQUEST + DOCUMENT VERIFICATION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        /*
        |--------------------------------------------------------------------------
        | ELEMENTS
        |--------------------------------------------------------------------------
        */

        const modal =
            document.getElementById(
                "documentModal"
            );

        const editModal =
            document.getElementById(
                "editDocumentModal"
            );

        const editModalInput =
            document.getElementById(
                "editDocumentName"
            );

        const editModalDescription =
            document.getElementById(
                "editDocumentDescription"
            );

        const editModalRequestType =
            document.getElementById(
                "editDocumentRequestType"
            );

        const editModalActionType =
            document.getElementById(
                "editDocumentActionType"
            );

        const editModalPriority =
            document.getElementById(
                "editDocumentPriority"
            );

        const editModalDueDate =
            document.getElementById(
                "editDocumentDueDate"
            );

        const editModalId =            document.getElementById(
                "editDocumentId"
            );

        const editForm =
            document.getElementById(
                "editDocumentForm"
            );

        const openButton =
            document.getElementById(
                "openDocumentModal"
            );

        const closeButton =
            document.getElementById(
                "closeDocumentModal"
            );

        const cancelButton =
            document.getElementById(
                "cancelDocumentModal"
            );

        const form =
            document.getElementById(
                "requestDocumentForm"
            );

        const closeEditButton =
            document.getElementById(
                "closeEditDocumentModal"
            );

        const cancelEditButton =
            document.getElementById(
                "cancelEditDocumentModal"
            );


        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */

        function openModalTarget(targetModal) {

            targetModal?.classList.add(
                "is-visible"
            );

            targetModal?.setAttribute(
                "aria-hidden",
                "false"
            );

            document.body.style.overflow =
                "hidden";
        }


        function closeModalTarget(targetModal) {

            targetModal?.classList.remove(
                "is-visible"
            );

            targetModal?.setAttribute(
                "aria-hidden",
                "true"
            );

            if (
                !document.querySelector(
                    ".document-modal-overlay.is-visible"
                )
            ) {
                document.body.style.overflow = "";
            }
        }


        if (modal && openButton) {

            function openModal() {
                openModalTarget(modal);
            }


            function closeModal() {
                closeModalTarget(modal);
            }


            openButton.addEventListener(
                "click",
                openModal
            );


            closeButton?.addEventListener(
                "click",
                closeModal
            );


            cancelButton?.addEventListener(
                "click",
                closeModal
            );


            modal.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target === modal
                    ) {

                        closeModal();

                    }

                }
            );

        }


        if (editModal) {

            closeEditButton?.addEventListener(
                "click",
                () => closeModalTarget(editModal)
            );

            cancelEditButton?.addEventListener(
                "click",
                async function () {
                    const documentId = editModalId?.value;

                    if (!documentId) {
                        closeModalTarget(editModal);
                        return;
                    }

                    const confirmed = window.confirm(
                        "Are you sure you want to delete this document request?"
                    );

                    if (!confirmed) {
                        return;
                    }

                    const button = this;
                    const originalText = button.textContent;

                    button.disabled = true;
                    button.textContent = "Deleting...";

                    try {
                        const formData = new FormData();
                        formData.append("document_id", documentId);

                        const response = await fetch(
                            "?page=onboarding-delete-document",
                            {
                                method: "POST",
                                body: formData
                            }
                        );

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        const data = await response.json();

                        if (!data.success) {
                            alert(data.message || "Unable to delete document request.");
                            button.disabled = false;
                            button.textContent = originalText;
                            return;
                        }

                        alert(data.message || "Document request deleted successfully.");
                        window.location.reload();

                    } catch (error) {
                        console.error("Delete document error:", error);
                        alert("Unable to delete the document request. Please try again.");
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                }
            );

            editModal.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target === editModal
                    ) {

                        closeModalTarget(editModal);

                    }

                }
            );
        }


        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Escape"
                ) {

                    closeModalTarget(modal);
                    closeModalTarget(editModal);

                }

            }
        );

        

        /*
        |--------------------------------------------------------------------------
        | DOCUMENT VERIFICATION + REJECTION
        |--------------------------------------------------------------------------
        */

        const verifyDocumentModal =
            document.getElementById("verifyDocumentModal");

        const rejectDocumentModal =
            document.getElementById("rejectDocumentModal");

        const verifyDocumentForm =
            document.getElementById("verifyDocumentForm");

        const rejectDocumentForm =
            document.getElementById("rejectDocumentForm");

        const verifyDocumentId =
            document.getElementById("verifyDocumentId");

        const rejectDocumentId =
            document.getElementById("rejectDocumentId");

        const verifyDocumentName =
            document.getElementById("verifyDocumentName");

        const rejectDocumentName =
            document.getElementById("rejectDocumentName");

        const verifyDocumentRemarks =
            document.getElementById("verifyDocumentRemarks");

        const rejectDocumentRemarks =
            document.getElementById("rejectDocumentRemarks");

        /*
        |--------------------------------------------------------------------------
        | OPEN VERIFY MODAL
        |--------------------------------------------------------------------------
        */

        const verifyButtons =
            document.querySelectorAll(".verify-document");

        verifyButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const documentId =
                            this.dataset.id;

                        const documentName =
                            this.dataset.name || "Document";

                        if (!documentId) {

                            alert("Invalid document.");
                            return;

                        }

                        if (verifyDocumentId) {
                            verifyDocumentId.value = documentId;
                        }

                        if (verifyDocumentName) {
                            verifyDocumentName.textContent =
                                documentName;
                        }

                        /*
                        * Clear previous remarks every time
                        * the modal is opened.
                        */
                        if (verifyDocumentRemarks) {
                            verifyDocumentRemarks.value = "";
                        }

                        openModalTarget(
                            verifyDocumentModal
                        );

                    }
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | OPEN REJECT MODAL
        |--------------------------------------------------------------------------
        */

        const rejectButtons =
            document.querySelectorAll(".reject-document");

        rejectButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const documentId =
                            this.dataset.id;

                        const documentName =
                            this.dataset.name || "Document";

                        if (!documentId) {

                            alert("Invalid document.");
                            return;

                        }

                        if (rejectDocumentId) {
                            rejectDocumentId.value = documentId;
                        }

                        if (rejectDocumentName) {
                            rejectDocumentName.textContent =
                                documentName;
                        }

                        /*
                        * Clear previous remarks every time
                        * the modal is opened.
                        */
                        if (rejectDocumentRemarks) {
                            rejectDocumentRemarks.value = "";
                        }

                        openModalTarget(
                            rejectDocumentModal
                        );

                    }
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | CLOSE VERIFY MODAL
        |--------------------------------------------------------------------------
        */

        verifyDocumentModal?.addEventListener(
            "click",
            function (event) {

                if (event.target === verifyDocumentModal) {

                    closeModalTarget(
                        verifyDocumentModal
                    );

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | CLOSE REJECT MODAL
        |--------------------------------------------------------------------------
        */

        rejectDocumentModal?.addEventListener(
            "click",
            function (event) {

                if (event.target === rejectDocumentModal) {

                    closeModalTarget(
                        rejectDocumentModal
                    );

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | CLOSE DOCUMENT DECISION MODALS (X + Cancel buttons)
        |--------------------------------------------------------------------------
        |
        | Any element carrying data-close-document-decision="<modalId>" closes
        | that modal. Covers the verify, reject and mark-completed dialogs.
        */

        document
            .querySelectorAll("[data-close-document-decision]")
            .forEach(
                function (button) {

                    button.addEventListener(
                        "click",
                        function () {

                            const modalId =
                                this.dataset.closeDocumentDecision;

                            const target =
                                document.getElementById(modalId);

                            if (target) {
                                closeModalTarget(target);
                            }

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | VERIFY DOCUMENT
        |--------------------------------------------------------------------------
        */

        verifyDocumentForm?.addEventListener(
            "submit",
            async function (event) {

                event.preventDefault();

                const documentId =
                    verifyDocumentId?.value;

                const remarks =
                    (verifyDocumentRemarks?.value || "").trim();

                if (!documentId) {

                    alert("Invalid document.");
                    return;

                }

                const submitButton =
                    verifyDocumentForm.querySelector(
                        "button[type='submit']"
                    );

                const originalHTML =
                    submitButton?.innerHTML || "";

                submitButton.disabled = true;

                submitButton.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Approving...';

                try {

                    const formData =
                        new FormData();

                    formData.append(
                        "document_id",
                        documentId
                    );

                    /*
                    * Optional remarks.
                    */
                    formData.append(
                        "remarks",
                        remarks
                    );

                    const response =
                        await fetch(
                            "?page=onboarding-verify-document",
                            {
                                method: "POST",
                                body: formData
                            }
                        );

                    if (!response.ok) {

                        throw new Error(
                            `HTTP ${response.status}`
                        );

                    }

                    const data =
                        await response.json();

                    if (!data.success) {

                        alert(
                            data.message ||
                            "Unable to verify document."
                        );

                        submitButton.disabled = false;
                        submitButton.innerHTML =
                            originalHTML;

                        return;

                    }

                    closeModalTarget(
                        verifyDocumentModal
                    );

                    alert(
                        data.message ||
                        "Document approved successfully."
                    );

                    window.location.reload();

                } catch (error) {

                    console.error(
                        "Document verification error:",
                        error
                    );

                    alert(
                        "Unable to approve the document. Please try again."
                    );

                    submitButton.disabled = false;
                    submitButton.innerHTML =
                        originalHTML;

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | REJECT DOCUMENT
        |--------------------------------------------------------------------------
        */

        rejectDocumentForm?.addEventListener(
            "submit",
            async function (event) {

                event.preventDefault();

                const documentId =
                    rejectDocumentId?.value;

                const remarks =
                    (rejectDocumentRemarks?.value || "").trim();

                if (!documentId) {

                    alert("Invalid document.");
                    return;

                }

                /*
                * Remarks are REQUIRED when rejecting.
                */
                if (!remarks) {

                    alert(
                        "Please provide remarks explaining why this document is being rejected."
                    );

                    rejectDocumentRemarks?.focus();

                    return;

                }

                const submitButton =
                    rejectDocumentForm.querySelector(
                        "button[type='submit']"
                    );

                const originalHTML =
                    submitButton?.innerHTML || "";

                submitButton.disabled = true;

                submitButton.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Rejecting...';

                try {

                    const formData =
                        new FormData();

                    formData.append(
                        "document_id",
                        documentId
                    );

                    formData.append(
                        "remarks",
                        remarks
                    );

                    const response =
                        await fetch(
                            "?page=onboarding-reject-document",
                            {
                                method: "POST",
                                body: formData
                            }
                        );

                    if (!response.ok) {

                        throw new Error(
                            `HTTP ${response.status}`
                        );

                    }

                    const data =
                        await response.json();

                    if (!data.success) {

                        alert(
                            data.message ||
                            "Unable to reject document."
                        );

                        submitButton.disabled = false;
                        submitButton.innerHTML =
                            originalHTML;

                        return;

                    }

                    closeModalTarget(
                        rejectDocumentModal
                    );

                    alert(
                        data.message ||
                        "Document rejected successfully."
                    );

                    window.location.reload();

                } catch (error) {

                    console.error(
                        "Document rejection error:",
                        error
                    );

                    alert(
                        "Unable to reject the document. Please try again."
                    );

                    submitButton.disabled = false;
                    submitButton.innerHTML =
                        originalHTML;

                }

            }
        );

        /*
        |--------------------------------------------------------------------------
        | OPEN EDIT DOCUMENT MODAL
        |--------------------------------------------------------------------------
        */

        const editButtons = document.querySelectorAll(".edit-document");

        editButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                const documentId = this.dataset.id;
                const documentName = this.dataset.name || "";
                const description = this.dataset.description || "";
                const requestType = this.dataset.requestType || "";
                const actionType = this.dataset.actionType || "document";
                const priority = this.dataset.priority || "Normal";
                const dueDate = this.dataset.dueDate || "";

                if (!documentId) {
                    alert("Invalid document.");
                    return;
                }

                if (editModalId) {
                    editModalId.value = documentId;
                }

                if (editModalInput) {
                    editModalInput.value = documentName;
                }

                if (editModalDescription) {
                    editModalDescription.value = description;
                }

                if (editModalRequestType && requestType) {
                    editModalRequestType.value = requestType;
                }

                if (editModalActionType) {
                    editModalActionType.value = actionType;
                }

                if (editModalPriority) {
                    editModalPriority.value = priority;
                }

                if (editModalDueDate) {
                    editModalDueDate.value = dueDate;
                }

                openModalTarget(editModal);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | UPDATE DOCUMENT
        |--------------------------------------------------------------------------
        */

        editForm?.addEventListener("submit", async function (event) {
            event.preventDefault();

            const documentId = editModalId?.value;
            const documentName = (editModalInput?.value || "").trim();
            const description = (editModalDescription?.value || "").trim();
            const requestType = editModalRequestType?.value || "";
            const actionType = editModalActionType?.value || "document";
            const priority = editModalPriority?.value || "Normal";
            const dueDate = editModalDueDate?.value || "";

            if (!documentId) {
                alert("Invalid document.");
                return;
            }

            if (!documentName) {
                alert("Please enter a document name.");
                editModalInput?.focus();
                return;
            }

            const submitButton =
                editForm.querySelector("button[type='submit']");

            const originalHTML =
                submitButton?.innerHTML || "";

            submitButton.disabled = true;

            submitButton.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            try {
                const formData = new FormData();

                formData.append("document_id", documentId);
                formData.append("document_name", documentName);
                formData.append("description", description);
                formData.append("request_type", requestType);
                formData.append("action_type", actionType);
                formData.append("priority", priority);
                formData.append("due_date", dueDate);

                const response = await fetch(
                    "?page=onboarding-update-document",
                    {
                        method: "POST",
                        body: formData
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (!data.success) {
                    alert(
                        data.message ||
                        "Unable to update the document request."
                    );

                    submitButton.disabled = false;
                    submitButton.innerHTML = originalHTML;
                    return;
                }

                closeModalTarget(editModal);

                alert(
                    data.message ||
                    "Document request updated successfully."
                );

                window.location.reload();

            } catch (error) {
                console.error(
                    "Document update error:",
                    error
                );

                alert(
                    "Unable to update the document request. Please try again."
                );

                submitButton.disabled = false;
                submitButton.innerHTML = originalHTML;
            }
        });


        /*
        |--------------------------------------------------------------------------
        | VIEW DOCUMENT MODAL
        |--------------------------------------------------------------------------
        */

        const viewDocumentModal =
            document.getElementById("viewDocumentModal");

        const closeViewDocumentButton =
            document.getElementById("closeViewDocumentModal");

        const cancelViewDocumentButton =
            document.getElementById("cancelViewDocumentModal");

        if (viewDocumentModal) {

            const dv = {
                title: document.getElementById("dvModalTitle"),
                type: document.getElementById("dvModalType"),
                priority: document.getElementById("dvModalPriority"),
                infoType: document.getElementById("dvInfoType"),
                infoAction: document.getElementById("dvInfoAction"),
                infoDue: document.getElementById("dvInfoDue"),
                infoPriority: document.getElementById("dvInfoPriority"),
                infoStatus: document.getElementById("dvInfoStatus"),
                instructionSection: document.getElementById("dvInstructionSection"),
                instruction: document.getElementById("dvInstruction"),
                submissionSection: document.getElementById("dvSubmissionSection"),
                submittedText: document.getElementById("dvSubmittedText"),
                submittedFile: document.getElementById("dvSubmittedFile"),
                submittedFileName: document.getElementById("dvSubmittedFileName"),
                remarksSection: document.getElementById("dvRemarksSection"),
                remarks: document.getElementById("dvRemarks"),
                reviewMeta: document.getElementById("dvReviewMeta"),
                timeline: document.getElementById("dvTimeline")
            };

            function renderDocumentTimeline(activities) {

                if (!dv.timeline) {
                    return;
                }

                dv.timeline.innerHTML = "";

                if (!activities || !activities.length) {
                    const empty = document.createElement("li");
                    empty.className = "ar-timeline__empty";
                    empty.textContent = "No activity recorded yet.";
                    dv.timeline.appendChild(empty);
                    return;
                }

                activities.forEach(function (activity) {
                    const item = document.createElement("li");
                    item.className = "ar-timeline__item";

                    const dot = document.createElement("span");
                    dot.className = "ar-timeline__dot";

                    const icon = document.createElement("i");
                    icon.className = "fa-solid " + (activity.icon || "fa-circle");
                    dot.appendChild(icon);

                    const body = document.createElement("div");
                    body.className = "ar-timeline__body";

                    const time = document.createElement("span");
                    time.className = "ar-timeline__time";
                    time.textContent = activity.date || "";

                    const text = document.createElement("p");
                    text.className = "ar-timeline__text";
                    text.textContent = activity.text || "";

                    body.appendChild(time);
                    body.appendChild(text);

                    item.appendChild(dot);
                    item.appendChild(body);
                    dv.timeline.appendChild(item);
                });
            }

            const STATUS_BADGE = {
                Pending: "badge-gray",
                Approved: "badge-green",
                Rejected: "badge-red"
            };

            function openViewDocumentModal(data) {

                dv.title.textContent = data.name || "Document";
                dv.type.textContent = data.requestType || "—";

                const priorityTone = (data.priority || "Normal").toLowerCase();
                dv.priority.className = "ar-priority ar-priority--" + priorityTone;
                dv.priority.textContent = data.priority || "Normal";

                dv.infoType.textContent = data.requestType || "—";
                dv.infoAction.textContent = data.actionLabel || "—";
                dv.infoDue.textContent = data.dueDate || "No due date";
                dv.infoPriority.textContent = data.priority || "Normal";

                dv.infoStatus.className =
                    "badge " + (STATUS_BADGE[data.status] || "badge-gray");
                dv.infoStatus.textContent = data.status || "Pending";

                const hasInstruction = !!(data.instruction && data.instruction.trim());
                dv.instructionSection.hidden = !hasInstruction;
                dv.instruction.textContent = hasInstruction ? data.instruction : "";

                const hasText = !!(data.response && data.response.trim());
                const hasFile = !!(data.fileName && data.fileUrl);
                const hasSubmission = hasText || hasFile;

                dv.submissionSection.hidden = !hasSubmission;

                if (dv.submittedText) {
                    dv.submittedText.hidden = !hasText;
                    dv.submittedText.textContent = hasText ? data.response : "";
                }

                if (dv.submittedFile) {
                    dv.submittedFile.hidden = !hasFile;

                    if (hasFile) {
                        dv.submittedFile.href = data.fileUrl;
                    }
                }

                if (dv.submittedFileName && hasFile) {
                    dv.submittedFileName.textContent = data.fileName;
                }

                const hasRemarks = !!(data.remarks && data.remarks.trim());
                dv.remarksSection.hidden = !hasRemarks;
                dv.remarks.textContent = hasRemarks ? data.remarks : "";
                dv.reviewMeta.textContent = "";

                if (hasRemarks) {
                    const meta = [];

                    if (data.reviewedAt) {
                        meta.push("Reviewed " + data.reviewedAt);
                    }

                    dv.reviewMeta.textContent = meta.join(" · ");
                }

                renderDocumentTimeline(data.activities);

                openModalTarget(viewDocumentModal);            }

            document.querySelectorAll(".view-document").forEach(function (button) {
                button.addEventListener("click", function () {

                    let data;

                    try {
                        data = JSON.parse(this.dataset.document || "{}");
                    } catch (error) {
                        console.error("Invalid document payload:", error);
                        return;
                    }

                    openViewDocumentModal(data);
                });
            });

            function closeViewDocumentModal() {
                closeModalTarget(viewDocumentModal);
            }

            closeViewDocumentButton?.addEventListener("click", closeViewDocumentModal);
            cancelViewDocumentButton?.addEventListener("click", closeViewDocumentModal);

            viewDocumentModal.addEventListener("click", function (event) {
                if (event.target === viewDocumentModal) {
                    closeViewDocumentModal();
                }
            });

            document.addEventListener("keydown", function (event) {
                if (
                    event.key === "Escape" &&
                    viewDocumentModal.classList.contains("is-visible")
                ) {
                    closeViewDocumentModal();
                }
            });
        }
    }
);