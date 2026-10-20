// Applicants
(function () {

    const rows                = Array.from(document.querySelectorAll('#applicantTable tbody tr.applicant-row'));
    const searchInput         = document.getElementById('applicantSearch');
    const positionFilter      = document.getElementById('positionFilter');
    const statusFilter        = document.getElementById('statusFilter');
    const sortFilter          = document.getElementById('sortFilter');
    const rowsPerPage         = document.getElementById('rowsPerPage');
    const clearFiltersBtn     = document.getElementById('clearFilters');
    const noMatchRow          = document.getElementById('noMatchRow');
    const resultsCount        = document.getElementById('resultsCount');
    const pageNumbers         = document.getElementById('pageNumbers');
    const prevPageBtn         = document.getElementById('prevPage');
    const nextPageBtn         = document.getElementById('nextPage');
    const isApplicantListPage = document.getElementById('applicantTable');

    let pageSize = 8;
    let currentPage = 1;
    let activeStatusFilter = 'All';

    /* -------------------- Table filtering + pagination -------------------- */

    if (isApplicantListPage) {

        const summaryCards = Array.from(document.querySelectorAll('.summary-card'));

        /* Keep the status dropdown and the summary tiles in step */
        function syncStatusUI() {
            if (statusFilter) statusFilter.value = activeStatusFilter;

            summaryCards.forEach(card => {
                const isActive =
                    activeStatusFilter !== 'All' && card.dataset.filter === activeStatusFilter;

                card.classList.toggle('active', isActive);
                card.setAttribute('aria-pressed', String(isActive));
            });

            updateClearState();
        }

        /* Disable "Clear Filters" while everything is at its default */
        function updateClearState() {
            if (!clearFiltersBtn) return;

            clearFiltersBtn.disabled =
                !searchInput.value.trim() &&
                positionFilter.value === 'All' &&
                activeStatusFilter === 'All' &&
                sortFilter.value === 'priority';
        }

        function applyFilters() {
            updateClearState();

            const search = (searchInput.value || '').toLowerCase().trim();
            const position = positionFilter.value;
            const sort = sortFilter.value;

            let visible = rows.filter(row => {
                const matchesSearch = !search
                    || (row.dataset.name || '').includes(search)
                    || (row.dataset.email || '').toLowerCase().includes(search);
                const matchesPosition = position === 'All' || row.dataset.position === position;
                const matchesStatus = activeStatusFilter === 'All' || row.dataset.status === activeStatusFilter;

                return matchesSearch && matchesPosition && matchesStatus;
            });

            visible.sort((a, b) => {

                switch (sort) {

                    case 'highest-score':
                        return parseInt(b.dataset.aiScore) - parseInt(a.dataset.aiScore);

                    case 'lowest-score':
                        return parseInt(a.dataset.aiScore) - parseInt(b.dataset.aiScore);

                    case 'oldest':
                        return new Date(a.dataset.appliedDate) - new Date(b.dataset.appliedDate);

                    case 'name-az':
                        return a.dataset.fullname.localeCompare(b.dataset.fullname);

                    case 'name-za':
                        return b.dataset.fullname.localeCompare(a.dataset.fullname);

                    case 'priority':
                        return (
                            (a.dataset.status !== 'Submitted') - (b.dataset.status !== 'Submitted') ||
                            parseInt(b.dataset.aiScore) - parseInt(a.dataset.aiScore) ||
                            new Date(b.dataset.appliedDate) - new Date(a.dataset.appliedDate)
                        );

                    case 'newest':
                    default:
                        return new Date(b.dataset.appliedDate) - new Date(a.dataset.appliedDate);
                }

            });

            const tbody = document.querySelector('#applicantTable tbody');

            // Reorder the table rows based on the sorted array
            visible.forEach(row => tbody.appendChild(row));

            // Hide all rows
            rows.forEach(row => row.style.display = 'none');

            const totalPages = Math.max(1, Math.ceil(visible.length / pageSize));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const start = (currentPage - 1) * pageSize;
            const pageRows = visible.slice(start, start + pageSize);

            pageRows.forEach(row => row.style.display = '');

            if (noMatchRow) {
                noMatchRow.hidden = visible.length !== 0;
            }

            resultsCount.textContent = visible.length
                ? `Showing ${pageRows.length} of ${visible.length} applicants`
                : 'No applicants found';

            renderPagination(totalPages);
        }

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

        prevPageBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; applyFilters(); } });
        nextPageBtn.addEventListener('click', () => { currentPage++; applyFilters(); });

        searchInput.addEventListener('input', () => { currentPage = 1; applyFilters(); });
        positionFilter.addEventListener('change', () => { currentPage = 1; applyFilters(); });
        sortFilter.addEventListener('change', () => { currentPage = 1; applyFilters(); });

        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                activeStatusFilter = statusFilter.value;
                currentPage = 1;
                syncStatusUI();
                applyFilters();
            });
        }

        if (rowsPerPage) {
            rowsPerPage.addEventListener('change', () => {
                pageSize = parseInt(rowsPerPage.value, 10) || 8;
                currentPage = 1;
                applyFilters();
            });
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                searchInput.value = '';
                positionFilter.value = 'All';
                sortFilter.value = 'priority';
                activeStatusFilter = 'All';
                currentPage = 1;
                syncStatusUI();
                applyFilters();
            });
        }

        summaryCards.forEach(card => {
            card.addEventListener('click', () => {
                const next = card.dataset.filter;

                // Clicking the active tile a second time clears the filter
                activeStatusFilter =
                    (next !== 'All' && activeStatusFilter === next) ? 'All' : next;

                sortFilter.value = 'priority';
                currentPage = 1;
                syncStatusUI();
                applyFilters();
            });

            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    card.click();
                }
            });
        });

        const clearInlineBtn = document.getElementById('clearFiltersInline');

        if (clearInlineBtn && clearFiltersBtn) {
            clearInlineBtn.addEventListener('click', () => clearFiltersBtn.click());
        }

        window.bindRowNavigation(rows);

        syncStatusUI();
        applyFilters();
    }

    /* -------------------- Details panel -------------------- */

    const overlay     = document.getElementById('detailsOverlay');
    const panel       = document.getElementById('detailsPanel');
    const closeDetails = document.getElementById('closeDetails');

    const recClassMap = {
        'Highly Recommended': 'rec-high',
        'Recommended': 'rec-mid',
        'Needs Review': 'rec-review',
        'Not Recommended': 'rec-low'
    };

    const statusBadgeClass = {
        'Submitted': 'badge-gray',
        'Review': 'badge-blue',
        'Under Review': 'badge-blue',
        'Interview': 'badge-purple',
        'Selection': 'badge-orange',
        'Offer': 'badge-teal',
        'Hired': 'badge-green',
        'Rejected': 'badge-red'
    };

    const statusLabel = {
        'Submitted': 'Submitted',
        'Review': 'Under Review',
        'Under Review': 'Under Review',
        'Interview': 'Interview',
        'Selection': 'Selection',
        'Offer': 'Offer',
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
        if (overlay) {
            overlay.classList.remove('active');
        }
        if (panel) {
            panel.classList.remove('active');
            panel.setAttribute('aria-hidden', 'true');
        }
    }

    if (closeDetails) {
        closeDetails.addEventListener('click', closeDetailsPanel);
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            closeDetailsPanel();
            closeAllModals();
        });
    }

    /* -------------------- Modals -------------------- */

    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.remove('active');
    }
    function closeAllModals() {
        ['scheduleModal', 'hireModal', 'rejectModal', 'qualifyModal',
         'evaluationModal', 'advanceModal', 'requirementModal',
         'approveModal', 'verifyModal', 'offerModal', 'acceptModal',
         'declineModal'].forEach(closeModal);
    }

    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.close));
    });

    /* Schedule interview */
    const openScheduleBtn = document.getElementById('openScheduleModal');

    if (openScheduleBtn) {
        openScheduleBtn.addEventListener('click', () => {
            openModal('scheduleModal');
        });
    }

    const evaluateResumeBtn = document.getElementById('evaluateResumeBtn');

    if (evaluateResumeBtn) {
        evaluateResumeBtn.addEventListener('click', async function () {
            const applicationId = this.dataset.applicationId;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Evaluating...';

            try {
                const formData = new FormData();
                formData.append('application_id', applicationId);

                const response = await fetch('?page=evaluate-resume', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to evaluate this resume.');
                }

                alert('AI resume screening finished successfully.');
                window.location.reload();

            } catch (error) {
                console.error('Resume evaluation error:', error);
                alert(error.message || 'An error occurred while evaluating the resume.');
                this.disabled = false;
                this.innerHTML = originalText;
            }
        });
    }

    const evaluateAcademicDocumentBtn = document.getElementById('evaluateAcademicDocumentBtn');

    if (evaluateAcademicDocumentBtn) {
        evaluateAcademicDocumentBtn.addEventListener('click', async function () {
            const applicationId = this.dataset.applicationId;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Assessing all documents...';

            try {
                const formData = new FormData();
                formData.append('application_id', applicationId);

                const response = await fetch('?page=evaluate-academic-document', { method: 'POST', body: formData });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to assess the credential documents.');
                }

                /*
                 * The whole set of documents is assessed in one pass, so
                 * report the aggregate result rather than a single file.
                 */
                const summary = result.data && result.data.summary;
                let message = result.message || 'Credential assessment completed.';

                if (summary) {
                    message +=
                        '\n\nDocuments assessed: ' + summary.evaluated + ' of ' + summary.total +
                        '\nVerified: ' + summary.valid +
                        '\nNeeds review: ' + summary.flagged;

                    if (summary.failed) {
                        message += '\nCould not assess: ' + summary.failed;
                    }

                    if (summary.evaluated) {
                        message += '\nAverage confidence: ' + summary.average_confidence + '%';
                    }
                }

                alert(message);
                window.location.reload();

            } catch (error) {
                alert(error.message || 'An error occurred while assessing the credential documents.');
                this.disabled = false;
                this.innerHTML = originalText;
            }
        });
    }

    const editScheduleBtn = document.getElementById('editScheduleBtn');

    if (editScheduleBtn) {
        editScheduleBtn.addEventListener('click', () => {
            openModal('scheduleModal');
        });
    }

    /* ==========================================================
    HIRE / REJECT
    ========================================================== */

    const hireBtn = document.getElementById('hireBtn');
    const rejectBtn = document.getElementById('rejectBtn');
    const confirmHireBtn = document.getElementById('confirmHireBtn');
    const rejectForm = document.getElementById('rejectForm');

    let hireRequestInProgress = false;

    /* ----------------------------------------------------------
    OPEN HIRE MODAL
    ---------------------------------------------------------- */

    if (hireBtn) {

        hireBtn.addEventListener('click', function () {

            console.log('Hire button clicked');

            const applicationId = this.dataset.applicationId;

            console.log('Application ID:', applicationId);

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            openModal('hireModal');
        });
    }

    /* ----------------------------------------------------------
    CONFIRM HIRE
    ---------------------------------------------------------- */

    if (confirmHireBtn) {

        confirmHireBtn.addEventListener('click', async function () {

            if (hireRequestInProgress) {
                return;
            }

            const applicationId = hireBtn ? hireBtn.dataset.applicationId : null;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            hireRequestInProgress = true;
            confirmHireBtn.disabled = true;
            confirmHireBtn.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Hiring...';

            try {

                const formData = new FormData();
                formData.append('application_id', applicationId);

                const response = await fetch('?page=hire-applicant', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                console.log('Hire response:', result);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to hire applicant.');
                }

                /*
                |--------------------------------------------------------------------------
                | SUCCESS
                |--------------------------------------------------------------------------
                */

                closeModal('hireModal');

                alert(
                    result.message + '\n\n' +

                    'Employee Number: ' +
                    result.data.employee_number +

                    '\n\nUsername: ' +
                    result.data.username +

                    '\n\nTemporary Password: ' +
                    result.data.temporary_password
                );

                /*
                |--------------------------------------------------------------------------
                | Reload applicant page
                |--------------------------------------------------------------------------
                */

                window.location.href =
                    '?page=review&id=' +
                    ((hireBtn && hireBtn.dataset.applicantId) || applicationId);

            } catch (error) {

                console.error('Hire error:', error);

                alert(error.message || 'An error occurred while hiring the applicant.');

            } finally {

                confirmHireBtn.disabled = false;
                confirmHireBtn.innerHTML =
                    '<i class="fa-solid fa-user-check"></i> Confirm Hire';

            }

        });
    }

    /* ----------------------------------------------------------
    OPEN REJECT MODAL
    ---------------------------------------------------------- */

    if (rejectBtn) {

        rejectBtn.addEventListener('click', function () {

            console.log('Reject button clicked');

            const applicationId = this.dataset.applicationId;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            openModal('rejectModal');
        });
    }

    /* ----------------------------------------------------------
    CONFIRM REJECTION
    ---------------------------------------------------------- */

    if (rejectForm) {

        rejectForm.addEventListener('submit', async function (e) {

            e.preventDefault();

            const applicationId = rejectBtn ? rejectBtn.dataset.applicationId : null;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            const submitBtn = rejectForm.querySelector('button[type="submit"]');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Rejecting...';
            }

            try {

                const reasonSelect = document.getElementById('rejectReason');
                const reasonNote = document.getElementById('rejectNote');

                const formData = new FormData();

                formData.append('application_id', applicationId);

                if (reasonSelect) {
                    formData.append('reason_category', reasonSelect.value);
                }

                if (reasonNote) {
                    formData.append('reason_note', reasonNote.value);
                }

                const response = await fetch('?page=reject-applicant', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                console.log('Reject response:', result);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to reject applicant.');
                }

                closeModal('rejectModal');

                /*
                 * A withdrawal sends no email by design, so only
                 * warn about mail failures when a notification
                 * was expected.
                 */
                const noEmailExpected =
                    reasonSelect && reasonSelect.value === 'Withdrew';

                alert(
                    !noEmailExpected && result.email_sent === false
                        ? 'Applicant has been rejected, but the notification email could not be sent.'
                        : 'Applicant has been rejected.'
                );

                window.location.href =
                    '?page=review&id=' +
                    ((rejectBtn && rejectBtn.dataset.applicantId) || applicationId);

            } catch (error) {

                console.error('Reject error:', error);

                alert(error.message || 'An error occurred while rejecting the applicant.');

            } finally {

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Confirm Rejection';
                }

            }

        });
    }

    /* ----------------------------------------------------------
    QUALIFY
    ---------------------------------------------------------- */

    const qualifyBtn = document.getElementById('qualifyBtn');
    const confirmQualifyBtn = document.getElementById('confirmQualifyBtn');

    let qualifyRequestInProgress = false;

    /* ----------------------------------------------------------
    OPEN QUALIFY MODAL
    ---------------------------------------------------------- */

    if (qualifyBtn) {

        qualifyBtn.addEventListener('click', function () {

            const applicationId = this.dataset.applicationId;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            openModal('qualifyModal');
        });
    }

    /* ----------------------------------------------------------
    CONFIRM QUALIFICATION
    ---------------------------------------------------------- */

    if (confirmQualifyBtn) {

        confirmQualifyBtn.addEventListener('click', async function () {

            if (qualifyRequestInProgress) {
                return;
            }

            const applicationId = qualifyBtn.dataset.applicationId;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            qualifyRequestInProgress = true;
            confirmQualifyBtn.disabled = true;
            confirmQualifyBtn.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Qualifying...';

            try {

                const formData = new FormData();
                formData.append('application_id', applicationId);

                const response = await fetch('?page=qualify-applicant', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                console.log('Qualify response:', result);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to qualify applicant.');
                }

                closeModal('qualifyModal');

                alert('Applicant has been qualified for interview.');

                window.location.href =
                    '?page=review&id=' +
                    ((qualifyBtn && qualifyBtn.dataset.applicantId) || applicationId);

            } catch (error) {

                console.error('Qualify error:', error);

                alert(error.message || 'An error occurred while qualifying the applicant.');

            } finally {

                qualifyRequestInProgress = false;
                confirmQualifyBtn.disabled = false;
                confirmQualifyBtn.innerHTML =
                    '<i class="fa-solid fa-user-check"></i> Confirm Qualification';

            }

        });
    }

    /* ----------------------------------------------------------
    INTERVIEW EVALUATION
    ---------------------------------------------------------- */

    const openEvaluationBtn = document.getElementById('openEvaluationModal');

    if (openEvaluationBtn) {
        openEvaluationBtn.addEventListener('click', () => {
            openModal('evaluationModal');
        });
    }

    const evaluationForm = document.getElementById('evaluationForm');

    if (evaluationForm) {

        evaluationForm.addEventListener('submit', async function (e) {

            e.preventDefault();

            const submitBtn = evaluationForm.querySelector('button[type="submit"]');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
            }

            try {

                const response = await fetch('?page=submit-interview-evaluation', {
                    method: 'POST',
                    body: new FormData(evaluationForm)
                });

                const result = await response.json();

                console.log('Evaluation response:', result);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to submit the evaluation.');
                }

                closeModal('evaluationModal');

                alert('Evaluation submitted.');

                window.location.reload();

            } catch (error) {

                console.error('Evaluation error:', error);

                alert(error.message || 'An error occurred while submitting the evaluation.');

            } finally {

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit Evaluation';
                }

            }

        });
    }

    /* ----------------------------------------------------------
    ADVANCE TO SELECTION
    ---------------------------------------------------------- */

    const advanceBtn = document.getElementById('advanceBtn');
    const confirmAdvanceBtn = document.getElementById('confirmAdvanceBtn');

    if (advanceBtn && confirmAdvanceBtn) {

        advanceBtn.addEventListener('click', function () {

            if (this.disabled) {
                return;
            }

            const applicationId = this.dataset.applicationId;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            openModal('advanceModal');
        });

        confirmAdvanceBtn.addEventListener('click', async function () {

            const applicationId = this.dataset.applicationId;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            this.disabled = true;
            this.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Advancing...';

            try {

                const formData = new FormData();
                formData.append('application_id', applicationId);

                const response = await fetch('?page=advance-to-selection', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                console.log('Advance response:', result);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to advance this applicant.');
                }

                closeModal('advanceModal');

                alert(
                    result.email_sent === false
                        ? 'Applicant advanced to the selection stage, but the notification email could not be sent.'
                        : 'Applicant advanced to the selection stage.'
                );

                window.location.reload();

            } catch (error) {

                console.error('Advance error:', error);

                alert(error.message || 'An error occurred while advancing the applicant.');

            } finally {

                this.disabled = false;
                this.innerHTML = 'Confirm Advance';

            }

        });
    }

    /* ----------------------------------------------------------
    SELECTION: REQUIREMENT MARKING
    ---------------------------------------------------------- */

    const requirementModal = document.getElementById('requirementModal');
    const requirementForm = document.getElementById('requirementForm');

    document.querySelectorAll('.requirement-mark-btn').forEach(btn => {

        btn.addEventListener('click', function () {

            document.getElementById('requirementIdField').value =
                this.dataset.requirementId;

            document.getElementById('requirementTypeField').value =
                this.dataset.requirementType;

            document.getElementById('requirementModalTitle').textContent =
                'Mark ' + this.dataset.requirementType;

            document.getElementById('requirementStatusField').value =
                this.dataset.requirementStatus === 'Failed' ? 'Failed' : 'Cleared';

            document.getElementById('requirementNoteField').value =
                this.dataset.requirementNote || '';

            openModal('requirementModal');
        });
    });

    if (requirementForm) {

        requirementForm.addEventListener('submit', async function (e) {

            e.preventDefault();

            const submitBtn = requirementForm.querySelector('button[type="submit"]');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            }

            try {

                const response = await fetch('?page=update-hiring-requirement', {
                    method: 'POST',
                    body: new FormData(requirementForm)
                });

                const result = await response.json();

                console.log('Requirement response:', result);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to update the requirement.');
                }

                closeModal('requirementModal');

                alert('Requirement updated.');

                window.location.reload();

            } catch (error) {

                console.error('Requirement error:', error);

                alert(error.message || 'An error occurred while updating the requirement.');

            } finally {

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Save Requirement';
                }

            }

        });
    }

    /* ----------------------------------------------------------
    SELECTION: APPROVE FOR OFFER
    ---------------------------------------------------------- */

    const approveOfferBtn = document.getElementById('approveOfferBtn');
    const confirmApproveBtn = document.getElementById('confirmApproveBtn');

    if (approveOfferBtn && confirmApproveBtn) {

        approveOfferBtn.addEventListener('click', function () {

            if (this.disabled) {
                return;
            }

            openModal('approveModal');
        });

        confirmApproveBtn.addEventListener('click', async function () {

            const applicationId = this.dataset.applicationId;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            this.disabled = true;
            this.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Approving...';

            try {

                const formData = new FormData();
                formData.append('application_id', applicationId);

                const response = await fetch('?page=approve-for-offer', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                console.log('Approve response:', result);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to approve this applicant.');
                }

                closeModal('approveModal');

                alert('Applicant approved for offer.');

                window.location.reload();

            } catch (error) {

                console.error('Approve error:', error);

                alert(error.message || 'An error occurred while approving for offer.');

            } finally {

                this.disabled = false;
                this.innerHTML = 'Confirm Approval';

            }

        });
    }

    /* ----------------------------------------------------------
    SELECTION: REQUEST ADDITIONAL VERIFICATION
    ---------------------------------------------------------- */

    const requestVerificationBtn = document.getElementById('requestVerificationBtn');
    const confirmVerifyBtn = document.getElementById('confirmVerifyBtn');

    if (requestVerificationBtn && confirmVerifyBtn) {

        requestVerificationBtn.addEventListener('click', () => {
            openModal('verifyModal');
        });

        confirmVerifyBtn.addEventListener('click', async function () {

            const applicationId = this.dataset.applicationId;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            const selectedRequirements = Array.from(
                document.querySelectorAll(
                    '#verifyModal input[name="requirement_ids[]"]:checked'
                )
            );

            if (selectedRequirements.length === 0) {
                alert('Select at least one completed check to request again.');
                return;
            }

            this.disabled = true;
            this.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Requesting...';

            try {

                const formData = new FormData();

                formData.append('application_id', applicationId);

                selectedRequirements.forEach(requirement => {
                    formData.append('requirement_ids[]', requirement.value);
                });

                const response = await fetch('?page=request-additional-verification', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                console.log('Verification response:', result);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to request additional verification.');
                }

                closeModal('verifyModal');

                alert(result.message || 'Additional verification requested.');

                window.location.reload();

            } catch (error) {

                console.error('Verification error:', error);

                alert(error.message || 'An error occurred while requesting additional verification.');

            } finally {

                this.disabled = false;
                this.innerHTML = 'Request Verification';

            }

        });
    }

    /* ----------------------------------------------------------
    OFFER: SEND / REVISE & RESEND
    ---------------------------------------------------------- */

    const offerModal = document.getElementById('offerModal');
    const offerForm = document.getElementById('offerForm');
    const sendOfferBtn = document.getElementById('sendOfferBtn');
    const reviseOfferBtn = document.getElementById('reviseOfferBtn');

    /*
     * Endpoint chosen by the button that opened the modal: fresh
     * sends post to send-offer, revisions to revise-offer.
     */
    let offerEndpoint = '?page=send-offer';

    function openOfferModal(endpoint, title, submitLabel) {

        offerEndpoint = endpoint;

        document.getElementById('offerModalTitle').textContent = title;

        const submitBtn = offerForm.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.textContent = submitLabel;

        openModal('offerModal');
    }

    if (sendOfferBtn) {
        sendOfferBtn.addEventListener('click', () => {
            openOfferModal('?page=send-offer', 'Send Offer', 'Send Offer');
        });
    }

    if (reviseOfferBtn) {
        reviseOfferBtn.addEventListener('click', () => {
            openOfferModal('?page=revise-offer', 'Revise & Resend Offer', 'Send Revised Offer');
        });
    }

    if (offerForm) {

        offerForm.addEventListener('submit', async function (e) {

            e.preventDefault();

            const submitBtn = offerForm.querySelector('button[type="submit"]');

            const originalLabel = submitBtn ? submitBtn.textContent : 'Send';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
            }

            try {

                const response = await fetch(offerEndpoint, {
                    method: 'POST',
                    body: new FormData(offerForm)
                });

                const result = await response.json();

                console.log('Offer response:', result);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to send the offer.');
                }

                closeModal('offerModal');

                alert(
                    result.email_sent === false
                        ? 'The offer was sent, but the offer email could not be delivered. Please verify the applicant email address.'
                        : (result.message || 'Offer sent to the applicant.')
                );

                window.location.reload();

            } catch (error) {

                console.error('Offer error:', error);

                alert(error.message || 'An error occurred while sending the offer.');

            } finally {

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalLabel;
                }

            }

        });
    }

    /* ----------------------------------------------------------
    OFFER: RECORD ACCEPTANCE
    ---------------------------------------------------------- */

    const recordAcceptBtn = document.getElementById('recordAcceptBtn');
    const confirmAcceptBtn = document.getElementById('confirmAcceptBtn');

    if (recordAcceptBtn && confirmAcceptBtn) {

        recordAcceptBtn.addEventListener('click', () => {
            openModal('acceptModal');
        });

        confirmAcceptBtn.addEventListener('click', async function () {

            const applicationId = this.dataset.applicationId;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            this.disabled = true;
            this.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Recording...';

            try {

                const formData = new FormData();

                formData.append('application_id', applicationId);
                formData.append('response', 'accept');

                const response = await fetch('?page=record-offer-response', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                console.log('Accept response:', result);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to record the acceptance.');
                }

                closeModal('acceptModal');

                alert(result.message || 'Offer acceptance recorded.');

                window.location.reload();

            } catch (error) {

                console.error('Accept error:', error);

                alert(error.message || 'An error occurred while recording the acceptance.');

            } finally {

                this.disabled = false;
                this.innerHTML = 'Confirm Acceptance';

            }

        });
    }

    /* ----------------------------------------------------------
    OFFER: RECORD DECLINE
    ---------------------------------------------------------- */

    const recordDeclineBtn = document.getElementById('recordDeclineBtn');
    const confirmDeclineBtn = document.getElementById('confirmDeclineBtn');

    if (recordDeclineBtn && confirmDeclineBtn) {

        recordDeclineBtn.addEventListener('click', () => {
            openModal('declineModal');
        });

        confirmDeclineBtn.addEventListener('click', async function () {

            const applicationId = this.dataset.applicationId;

            if (!applicationId) {
                alert('Application ID is missing.');
                return;
            }

            this.disabled = true;
            this.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Recording...';

            try {

                const formData = new FormData();

                formData.append('application_id', applicationId);
                formData.append('response', 'decline');

                const noteField = document.getElementById('declineNoteField');
                formData.append('note', noteField ? noteField.value : '');

                const response = await fetch('?page=record-offer-response', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                console.log('Decline response:', result);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to record the decline.');
                }

                closeModal('declineModal');

                alert(result.message || 'Offer decline recorded.');

                window.location.reload();

            } catch (error) {

                console.error('Decline error:', error);

                alert(error.message || 'An error occurred while recording the decline.');

            } finally {

                this.disabled = false;
                this.innerHTML = 'Confirm Decline';

            }

        });
    }

})();