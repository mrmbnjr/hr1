// Applicants
(function () {

    const rows          = Array.from(document.querySelectorAll('#applicantTable tbody tr.applicant-row'));
    const searchInput    = document.getElementById('applicantSearch');
    const positionFilter = document.getElementById('positionFilter');
    const statusFilter   = document.getElementById('statusFilter');
    const resultsCount   = document.getElementById('resultsCount');
    const pageNumbers    = document.getElementById('pageNumbers');
    const prevPageBtn    = document.getElementById('prevPage');
    const nextPageBtn    = document.getElementById('nextPage');

    const PAGE_SIZE = 8;
    let currentPage = 1;
    let activeStatusFilter = 'All';

    /* -------------------- Table filtering + pagination -------------------- */

    function applyFilters() {
        const search   = (searchInput.value || '').toLowerCase().trim();
        const position = positionFilter.value;
        const status   = statusFilter.value !== 'All' ? statusFilter.value : activeStatusFilter;

        const visible = rows.filter(row => {
            const matchesSearch   = !search || row.dataset.name.includes(search);
            const matchesPosition = position === 'All' || row.dataset.position === position;
            const matchesStatus   = status === 'All' || row.dataset.status === status;
            return matchesSearch && matchesPosition && matchesStatus;
        });

        rows.forEach(row => row.style.display = 'none');

        const totalPages = Math.max(1, Math.ceil(visible.length / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * PAGE_SIZE;
        const pageRows = visible.slice(start, start + PAGE_SIZE);
        pageRows.forEach(row => row.style.display = '');

        resultsCount.textContent = visible.length
            ? `Showing ${start + 1}-${Math.min(start + PAGE_SIZE, visible.length)} of ${visible.length} applicants`
            : 'No applicants match your filters';

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        pageNumbers.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'page-num' + (i === currentPage ? ' active' : '');
            btn.textContent = i;
            btn.addEventListener('click', () => { currentPage = i; applyFilters(); });
            pageNumbers.appendChild(btn);
        }
        prevPageBtn.disabled = currentPage <= 1;
        nextPageBtn.disabled = currentPage >= totalPages;
    }

    prevPageBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; applyFilters(); } });
    nextPageBtn.addEventListener('click', () => { currentPage++; applyFilters(); });

    searchInput.addEventListener('input', () => { currentPage = 1; applyFilters(); });
    positionFilter.addEventListener('change', () => { currentPage = 1; applyFilters(); });
    statusFilter.addEventListener('change', () => { currentPage = 1; activeStatusFilter = 'All'; applyFilters(); });

    document.querySelectorAll('.summary-card').forEach(card => {
        card.addEventListener('click', () => {
            activeStatusFilter = card.dataset.filter;
            statusFilter.value = 'All';
            currentPage = 1;
            document.querySelectorAll('.summary-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            applyFilters();
        });
    });

    applyFilters();

    /* -------------------- Details panel -------------------- */

    const overlay       = document.getElementById('detailsOverlay');
    const panel          = document.getElementById('detailsPanel');
    const closeDetails   = document.getElementById('closeDetails');

    const recClassMap = {
        'Highly Recommended': 'rec-high',
        'Recommended': 'rec-mid',
        'Needs Review': 'rec-review',
        'Not Recommended': 'rec-low'
    };

    const statusBadgeClass = {
        'Submitted': 'badge-gray',
        'Review': 'badge-blue',
        'Interview': 'badge-orange',
        'Hired': 'badge-green',
        'Rejected': 'badge-red'
    };

    const statusLabel = {
        'Submitted': 'Submitted',
        'Review': 'Under Review',
        'Interview': 'Interview Scheduled',
        'Hired': 'Hired',
        'Rejected': 'Rejected'
    };

    let currentRow = null;

    function openDetails(row) {
        currentRow = row;
        const d = row.dataset;

        document.getElementById('dpAvatar').textContent = (d.fullname || '?').charAt(0).toUpperCase();
        document.getElementById('dpName').textContent = d.fullname || '-';

        const statusBadge = document.getElementById('dpStatusBadge');
        statusBadge.textContent = statusLabel[d.status] || d.status;
        statusBadge.className = 'badge ' + (statusBadgeClass[d.status] || 'badge-gray');

        document.getElementById('dpFullname').textContent = d.fullname || '-';
        document.getElementById('dpEmail').textContent = d.email || '-';
        document.getElementById('dpPhone').textContent = d.phone || '-';
        document.getElementById('dpPosition').textContent = d.position || '-';
        document.getElementById('dpDate').textContent = d.appliedDate || '-';
        document.getElementById('dpStatusText').textContent = statusLabel[d.status] || d.status;

        document.getElementById('dpResumeName').textContent = d.resumeName || 'resume.pdf';
        document.getElementById('dpViewResume').href = d.resume || '#';
        document.getElementById('dpDownloadResume').href = d.resume || '#';

        const aiScore = parseInt(d.aiScore || '0', 10);
        document.getElementById('dpAiScoreText').textContent = aiScore + '%';
        document.getElementById('dpAiScoreCircle').style.setProperty('--score', aiScore);

        const recBadge = document.getElementById('dpRecommendation');
        recBadge.textContent = d.recommendation || 'Needs Review';
        recBadge.className = 'rec-badge ' + (recClassMap[d.recommendation] || 'rec-review');

        setBar('dpSkillsBar', 'dpSkillsValue', d.skills);
        setBar('dpExperienceBar', 'dpExperienceValue', d.experience);
        setBar('dpEducationBar', 'dpEducationValue', d.education);

        if (d.interviewStatus === 'Scheduled') {
            document.getElementById('dpInterviewNotScheduled').style.display = 'none';
            document.getElementById('dpInterviewScheduled').style.display = 'block';
            document.getElementById('dpInterviewDate').textContent = d.interviewDate || '-';
            document.getElementById('dpInterviewTime').textContent = d.interviewTime || '-';
            document.getElementById('dpInterviewManager').textContent = d.interviewManager || '-';
            document.getElementById('dpInterviewLocation').textContent = d.interviewLocation || '-';
        } else {
            document.getElementById('dpInterviewNotScheduled').style.display = 'flex';
            document.getElementById('dpInterviewScheduled').style.display = 'none';
        }

        const mgrRec = document.getElementById('dpMgrRecommendation');
        mgrRec.textContent = d.mgrRecommendation || 'Pending';
        mgrRec.className = 'rec-badge ' + (recClassMap[d.mgrRecommendation] || 'rec-review');
        document.getElementById('dpMgrRemarks').textContent = d.mgrRemarks || 'No remarks yet.';

        document.getElementById('hireApplicantName').textContent = d.fullname || 'this applicant';

        overlay.classList.add('active');
        panel.classList.add('active');
        panel.setAttribute('aria-hidden', 'false');
    }

    function setBar(barId, valueId, raw) {
        const value = parseInt(raw || '0', 10);
        document.getElementById(barId).style.width = value + '%';
        document.getElementById(valueId).textContent = value + '%';
    }

    document.querySelectorAll('[data-action="view"]').forEach(btn => {
        btn.addEventListener('click', () => openDetails(btn.closest('tr')));
    });

    function closeDetailsPanel() {
        overlay.classList.remove('active');
        panel.classList.remove('active');
        panel.setAttribute('aria-hidden', 'true');
    }

    closeDetails.addEventListener('click', closeDetailsPanel);
    overlay.addEventListener('click', () => {
        closeDetailsPanel();
        closeAllModals();
    });

    /* -------------------- Modals -------------------- */

    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
    function closeAllModals() {
        ['scheduleModal', 'hireModal', 'rejectModal'].forEach(closeModal);
    }

    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.close));
    });

    /* Schedule interview */
    document.getElementById('openScheduleModal').addEventListener('click', () => openModal('scheduleModal'));
    document.getElementById('editScheduleBtn').addEventListener('click', () => {
        if (!currentRow) return;
        const d = currentRow.dataset;
        document.getElementById('scheduleDate').value = d.interviewDate || '';
        document.getElementById('scheduleTime').value = d.interviewTime || '';
        document.getElementById('scheduleLocation').value = d.interviewLocation || '';
        document.getElementById('scheduleNotes').value = d.interviewNotes || '';
        openModal('scheduleModal');
    });

    document.getElementById('scheduleForm').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!currentRow) return;

        const d = currentRow.dataset;
        d.interviewStatus   = 'Scheduled';
        d.interviewDate     = document.getElementById('scheduleDate').value;
        d.interviewTime     = document.getElementById('scheduleTime').value;
        d.interviewManager  = document.getElementById('scheduleManager').selectedOptions[0]?.text || '';
        d.interviewLocation = document.getElementById('scheduleLocation').value;
        d.interviewNotes    = document.getElementById('scheduleNotes').value;

        d.status = 'Interview';
        const statusCell = currentRow.querySelector('td:nth-child(4) .badge');
        statusCell.textContent = statusLabel['Interview'];
        statusCell.className = 'badge ' + statusBadgeClass['Interview'];

        closeModal('scheduleModal');
        openDetails(currentRow);

        // TODO: replace with AJAX call to ApplicantController::scheduleInterview
    });

    /* Hire */
    document.getElementById('hireBtn').addEventListener('click', () => openModal('hireModal'));
    document.getElementById('confirmHireBtn').addEventListener('click', () => {
        if (!currentRow) return;

        currentRow.dataset.status = 'Hired';
        const statusCell = currentRow.querySelector('td:nth-child(4) .badge');
        statusCell.textContent = statusLabel['Hired'];
        statusCell.className = 'badge ' + statusBadgeClass['Hired'];

        closeModal('hireModal');
        closeDetailsPanel();
        applyFilters();

        // TODO: replace with AJAX call to ApplicantController::hireApplicant
        // which should: set application_status = Hired, insert into employees,
        // create the user account, send the hiring email, and start onboarding.
    });

    /* Reject */
    document.getElementById('rejectBtn').addEventListener('click', () => openModal('rejectModal'));
    document.getElementById('rejectForm').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!currentRow) return;

        currentRow.dataset.status = 'Rejected';
        const statusCell = currentRow.querySelector('td:nth-child(4) .badge');
        statusCell.textContent = statusLabel['Rejected'];
        statusCell.className = 'badge ' + statusBadgeClass['Rejected'];

        closeModal('rejectModal');
        closeDetailsPanel();
        applyFilters();

        // TODO: replace with AJAX call to ApplicantController::rejectApplicant
        // passing document.getElementById('rejectionRemarks').value
    });

})();
