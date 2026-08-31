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

        function applyFilters() {

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

                const matchesDepartment =
                    department === 'All' ||
                    row.dataset.department === department;


                const matchesStatus =
                    status === 'All' ||
                    row.dataset.status === status;


                return (
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

            if (visible.length === 0) {

                resultsCount.textContent =
                    'No employees found';

            } else {

                const from =
                    start + 1;

                const to =
                    Math.min(
                        start + PAGE_SIZE,
                        visible.length
                    );

                resultsCount.textContent =
                    `Showing ${from}-${to} of ${visible.length} employees`;
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

            if (!pageNumbers) {
                return;
            }


            pageNumbers.innerHTML = "";


            const addButton = (page) => {

                const btn =
                    document.createElement(
                        "button"
                    );


                btn.type = "button";

                btn.className =
                    "page-num" +
                    (
                        page === currentPage
                            ? " active"
                            : ""
                    );

                btn.textContent = page;


                btn.addEventListener(
                    "click",
                    () => {

                        currentPage = page;

                        applyFilters();

                    }
                );


                pageNumbers.appendChild(btn);
            };


            const addDots = () => {

                const span =
                    document.createElement(
                        "span"
                    );

                span.textContent = "...";

                pageNumbers.appendChild(span);
            };


            if (totalPages <= 7) {

                for (
                    let i = 1;
                    i <= totalPages;
                    i++
                ) {

                    addButton(i);

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
                    let i = start;
                    i <= end;
                    i++
                ) {

                    addButton(i);

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

                currentPage++;

                applyFilters();

            }
        );


        /*
        |--------------------------------------------------------------------------
        | FILTER EVENTS
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */

        if (modal && openButton) {

            function openModal() {

                modal.classList.add(
                    "is-visible"
                );

                modal.setAttribute(
                    "aria-hidden",
                    "false"
                );

                document.body.style.overflow =
                    "hidden";
            }


            function closeModal() {

                modal.classList.remove(
                    "is-visible"
                );

                modal.setAttribute(
                    "aria-hidden",
                    "true"
                );

                document.body.style.overflow =
                    "";
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


            document.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key === "Escape"
                    ) {

                        closeModal();

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | VERIFY DOCUMENT
        |--------------------------------------------------------------------------
        */

        const verifyButtons =
            document.querySelectorAll(
                ".verify-document"
            );


        verifyButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    async function () {

                        const documentId =
                            this.dataset.id;


                        if (!documentId) {

                            alert(
                                "Invalid document."
                            );

                            return;
                        }


                        const confirmed =
                            window.confirm(
                                "Are you sure you want to verify this document?"
                            );


                        if (!confirmed) {
                            return;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Disable button
                        |--------------------------------------------------------------------------
                        */

                        this.disabled = true;


                        const originalHTML =
                            this.innerHTML;


                        this.innerHTML =
                            '<i class="fa-solid fa-spinner fa-spin"></i>';


                        try {

                            const formData =
                                new FormData();


                            formData.append(
                                "document_id",
                                documentId
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

                                this.disabled =
                                    false;

                                this.innerHTML =
                                    originalHTML;

                                return;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Success
                            |--------------------------------------------------------------------------
                            */

                            alert(
                                data.message ||
                                "Document verified successfully."
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Reload page
                            |--------------------------------------------------------------------------
                            */

                            window.location.reload();


                        } catch (error) {

                            console.error(
                                "Document verification error:",
                                error
                            );


                            alert(
                                "Unable to verify the document. Please try again."
                            );


                            this.disabled =
                                false;

                            this.innerHTML =
                                originalHTML;
                        }

                    }
                );

            }

        );

    }
);