// Applicants
(function () {

    const rows = Array.from(document.querySelectorAll('#applicantTable tbody tr.employee-row'));
    const departmentFilter = document.getElementById('departmentFilter');
    const statusFilter = document.getElementById('statusFilter');
    const sortFilter          = document.getElementById('sortFilter');
    const resultsCount        = document.getElementById('resultsCount');
    const pageNumbers         = document.getElementById('pageNumbers');
    const prevPageBtn         = document.getElementById('prevPage');
    const nextPageBtn         = document.getElementById('nextPage');
    const isApplicantListPage = document.getElementById('applicantTable');

    const PAGE_SIZE = 8;
    let currentPage = 1;
    let activeStatusFilter = 'All';

    /* -------------------- Table filtering + pagination -------------------- */

    if (isApplicantListPage) {
        function applyFilters() {
            const department = departmentFilter.value;
            const status = statusFilter.value;
            const sort = sortFilter.value;

            let visible = rows.filter(row => {
                const matchesDepartment =
                    department === 'All' ||
                    row.dataset.department === department;

                const matchesStatus =
                    status === 'All' ||
                    row.dataset.status === status;

                return matchesDepartment && matchesStatus;
            });

            visible.sort((a, b) => {

                switch (sort) {

                    case 'progress-high':
                        return Number(b.dataset.progress) - Number(a.dataset.progress);

                    case 'progress-low':
                        return Number(a.dataset.progress) - Number(b.dataset.progress);

                    case 'oldest':
                        return new Date(a.dataset.startDate) - new Date(b.dataset.startDate)

                    case 'name-az':
                        return a.dataset.fullname.localeCompare(b.dataset.fullname);

                    case 'name-za':
                        return b.dataset.fullname.localeCompare(a.dataset.fullname);

                    case 'newest':
                    default:
                        return new Date(b.dataset.startDate) - new Date(a.dataset.startDate);
                }

            });

            const tbody = document.querySelector('#applicantTable tbody');

            // Reorder the table rows based on the sorted array
            visible.forEach(row => tbody.appendChild(row));

            // Hide all rows
            rows.forEach(row => row.style.display = 'none');

            const totalPages = Math.max(1, Math.ceil(visible.length / PAGE_SIZE));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const start = (currentPage - 1) * PAGE_SIZE;
            const pageRows = visible.slice(start, start + PAGE_SIZE);

            pageRows.forEach(row => row.style.display = '');

            resultsCount.textContent =
            `Showing ${start + 1}-${Math.min(start + PAGE_SIZE, visible.length)} of ${visible.length} employees`;
            'No employees found'

            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            pageNumbers.innerHTML = "";

            const addButton = (page) => {
                const btn = document.createElement("button");
                btn.type = "button";
                btn.className = "page-num" + (page === currentPage ? " active" : "");
                btn.textContent = page;

                btn.addEventListener("click", () => {
                    currentPage = page;
                    applyFilters();
                });

                pageNumbers.appendChild(btn);
            };

            const addDots = () => {
                const span = document.createElement("span");
                span.textContent = "...";
                pageNumbers.appendChild(span);
            };

            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) {
                    addButton(i);
                }
            } else {
                addButton(1);

                if (currentPage > 3) addDots();

                const start = Math.max(2, currentPage - 1);
                const end = Math.min(totalPages - 1, currentPage + 1);

                for (let i = start; i <= end; i++) {
                    addButton(i);
                }

                if (currentPage < totalPages - 2) addDots();

                addButton(totalPages);
            }

            prevPageBtn.disabled = currentPage === 1;
            nextPageBtn.disabled = currentPage === totalPages;
        }

        prevPageBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; applyFilters(); } });
        nextPageBtn.addEventListener('click', () => { currentPage++; applyFilters(); });

        positionFilter.addEventListener('change', () => { currentPage = 1; applyFilters(); });
        sortFilter.addEventListener('change', () => { currentPage = 1; applyFilters(); });
        departmentFilter.addEventListener('change', () => {
            currentPage = 1;
            applyFilters();
        });

        statusFilter.addEventListener('change', () => {
            currentPage = 1;
            applyFilters();
        });

        applyFilters();
    }
}())