(() => {
    const table = document.getElementById('userTable');
    if (!table) return;

    const $ = id => document.getElementById(id);
    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.querySelectorAll('.user-row'));

    const search = $('userSearch');
    const role = $('roleFilter');
    const department = $('departmentFilter');
    const sort = $('sortFilter');
    const pagination = document.querySelector('.pagination');
    const pageNumbers = $('pageNumbers');
    const prevPage = $('prevPage');
    const nextPage = $('nextPage');
    const resultsCount = $('resultsCount');
    const emptyState = $('userEmptyState');
    const resetButton = $('resetUserFilters');
    const statusTabs = $('statusTabs');
    const legendKeys = Array.from(statusTabs.querySelectorAll('.status-tab'));
    const attentionItems = Array.from(document.querySelectorAll('.attention-item'));
    const userModal = $('userModal');
    const detailsModal = $('detailsModal');

    const PAGE_SIZE = 8;
    const SEARCH_FIELDS = ['name', 'employeeId', 'username', 'email'];
    const STATUS_ORDER = { 'Active': 0, 'Pending Setup': 1, 'Inactive': 2 };
    const STATUS_CLASS = { 'Active': 'is-active', 'Pending Setup': 'is-pending', 'Inactive': 'is-inactive' };

    let currentPage = 1;
    let statusFilter = 'All';   // the tabs above the table are the only status control
    let attentionFilter = '';   // '' | 'dormant' | 'unlinked' (matches the row's data-* flags)
    let lastFocus = null;

    /* ---------- Filtering, sorting, paging ---------- */

    // Safari can't parse "YYYY-MM-DD HH:MM:SS", so normalise before Date.parse.
    const time = row => Date.parse((row.dataset.created || '').replace(' ', 'T')) || 0;
    const byName = (a, b) => a.dataset.name.localeCompare(b.dataset.name);

    function compareRows(a, b) {
        switch (sort.value) {
            case 'name-az': return byName(a, b);
            case 'name-za': return byName(b, a);
            case 'oldest': return time(a) - time(b);
            case 'status': return (STATUS_ORDER[a.dataset.status] - STATUS_ORDER[b.dataset.status]) || byName(a, b);
            default: return time(b) - time(a);
        }
    }

    // 1 … 4 5 6 … 20 — everything when there are only a few pages.
    function pageItems(total, current) {
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        const keep = new Set([1, total, current - 1, current, current + 1]);
        const items = [];
        let last = 0;
        for (let page = 1; page <= total; page++) {
            if (!keep.has(page)) continue;
            if (page - last > 1) items.push('gap');
            items.push(page);
            last = page;
        }
        return items;
    }

    function renderPagination(totalPages) {
        const nodes = pageItems(totalPages, currentPage).map(item => {
            if (item === 'gap') {
                const gap = document.createElement('span');
                gap.textContent = '…';
                gap.setAttribute('aria-hidden', 'true');
                return gap;
            }
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `page-num${item === currentPage ? ' active' : ''}`;
            button.textContent = item;
            button.setAttribute('aria-label', `Page ${item}`);
            if (item === currentPage) button.setAttribute('aria-current', 'page');
            button.addEventListener('click', () => { currentPage = item; applyFilters(); });
            return button;
        });
        pageNumbers.replaceChildren(...nodes);
        prevPage.disabled = currentPage === 1;
        nextPage.disabled = currentPage === totalPages;
        pagination.style.display = totalPages > 1 ? '' : 'none';
    }

    function applyFilters() {
        closeMenus();

        const query = search.value.toLowerCase().trim();
        const selectedRole = role.value;
        const selectedDepartment = department.value;

        const visibleRows = rows.filter(row => {
            const data = row.dataset;
            return (!query || SEARCH_FIELDS.some(field => (data[field] || '').includes(query))) &&
                (selectedRole === 'All' || data.role === selectedRole) &&
                (selectedDepartment === 'All' || data.department === selectedDepartment) &&
                (statusFilter === 'All' || data.status === statusFilter) &&
                (!attentionFilter || data[attentionFilter] === '1');
        }).sort(compareRows);

        const totalPages = Math.max(1, Math.ceil(visibleRows.length / PAGE_SIZE));
        currentPage = Math.min(currentPage, totalPages);
        const startIndex = (currentPage - 1) * PAGE_SIZE;
        const pageRows = visibleRows.slice(startIndex, startIndex + PAGE_SIZE);
        const shown = new Set(pageRows);

        rows.forEach(row => { row.style.display = shown.has(row) ? '' : 'none'; });
        pageRows.forEach(row => tbody.appendChild(row));   // DOM order = sort order

        const noMatches = visibleRows.length === 0;
        const noAccounts = rows.length === 0;
        table.style.display = noMatches ? 'none' : '';
        emptyState.hidden = !noMatches;
        emptyState.querySelector('h3').textContent = noAccounts ? 'No accounts yet' : 'No users found';
        emptyState.querySelector('p').textContent = noAccounts ? 'There are no user accounts to show.' : 'Try adjusting your search or filters.';
        resetButton.hidden = noAccounts;

        resultsCount.textContent = noMatches
            ? 'No users found'
            : `Showing ${startIndex + 1}-${startIndex + pageRows.length} of ${visibleRows.length} account${visibleRows.length === 1 ? '' : 's'}`;

        renderPagination(totalPages);
    }

    function setStatusFilter(value) {
        statusFilter = value;
        statusTabs.dataset.selected = value;   // lets the strip dim the other segments
        legendKeys.forEach(key => {
            const on = key.dataset.statusFilter === value;
            key.classList.toggle('active', on);
            key.setAttribute('aria-pressed', String(on));
        });
    }

    function setAttention(value) {
        attentionFilter = value;
        attentionItems.forEach(item => item.setAttribute('aria-pressed', String(item.dataset.attention === value)));
    }

    /* ---------- Row menu ---------- */

    function closeMenus() {
        tbody.querySelectorAll('.row-menu').forEach(menu => { menu.hidden = true; });
        tbody.querySelectorAll('[data-action="menu"]').forEach(button => button.setAttribute('aria-expanded', 'false'));
    }

    function toggleMenu(button) {
        const menu = button.nextElementSibling;
        const willOpen = menu.hidden;
        closeMenus();
        if (!willOpen) return;
        menu.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        menu.querySelector('button').focus();
    }

    /* ---------- Dialogs ---------- */

    function openModal(modal, origin, focusTarget) {
        lastFocus = origin || document.activeElement;
        modal.hidden = false;
        document.body.classList.add('modal-open');
        (focusTarget || modal.querySelector('[data-close-modal]')).focus();
    }

    function closeModals() {
        const wasOpen = !userModal.hidden || !detailsModal.hidden;
        userModal.hidden = true;
        detailsModal.hidden = true;
        document.body.classList.remove('modal-open');
        if (wasOpen && lastFocus && document.contains(lastFocus)) lastFocus.focus();
        lastFocus = null;
    }

    function setStatusPill(pill, status) {
        const dot = document.createElement('i');
        dot.className = 'status-dot';
        dot.setAttribute('aria-hidden', 'true');
        pill.className = `status-pill ${STATUS_CLASS[status] || 'is-inactive'}`;
        pill.replaceChildren(dot, document.createTextNode(status));
    }

    function openDetails(row, origin) {
        const data = row.dataset;
        $('detailsAvatar').textContent = row.querySelector('.avatar-circle').textContent;
        $('detailsName').textContent = data.fullname;
        $('detailsEmployee').textContent = data.employeeNumber || 'No employee ID';
        setStatusPill($('detailsStatus'), data.status);
        $('detailsUsername').textContent = data.login;
        $('detailsRole').textContent = data.role || '-';
        $('detailsEmail').textContent = data.emailAddress || 'No email on record';
        $('detailsDepartment').textContent = data.department || 'Unassigned';
        $('detailsCreated').textContent = data.createdDisplay || data.created || '-';
        $('detailsLastLogin').textContent = data.lastLogin || 'Never logged in';
        openModal(detailsModal, origin);
    }

    function openEdit(row, origin) {
        const data = row.dataset;
        const roleSelect = $('roleId');
        const options = Array.from(roleSelect.options);
        const match = options.find(option => option.value && option.value === data.roleId)
            || options.find(option => option.textContent.trim() === data.role);

        $('formUserId').value = data.userId;
        $('username').value = data.login;
        $('accountEmail').value = data.emailAddress;   // the real email, never the display placeholder
        roleSelect.value = match ? match.value : '';
        $('accountStatus').value = data.status === 'Inactive' ? 'Inactive' : 'Active';
        openModal(userModal, origin, $('username'));
    }

    /* ---------- Enable / disable ---------- */

    function postAction(fields) {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = '?page=user-management-action';
        Object.entries(fields).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }

    function toggleAccount(row) {
        const disabling = row.dataset.status !== 'Inactive';
        const message = disabling
            ? `Disable account "${row.dataset.login}"? They won't be able to sign in.`
            : `Enable account "${row.dataset.login}"? They will be able to sign in again.`;
        if (!window.confirm(message)) return;
        postAction({ action: 'toggle-status', user_id: row.dataset.userId, status: disabling ? 'Inactive' : 'Active' });
    }

    /* ---------- Events ---------- */

    // One delegated listener for every row action (rows are re-ordered, the table isn't).
    table.addEventListener('click', event => {
        const button = event.target.closest('[data-action]');
        if (!button) return;
        const row = button.closest('.user-row');
        const action = button.dataset.action;

        if (action === 'menu') { toggleMenu(button); return; }

        // Return focus to the row's menu button if the action came from inside the menu.
        const origin = button.closest('.row-menu') ? row.querySelector('[data-action="menu"]') : button;
        closeMenus();
        if (action === 'details') openDetails(row, origin);
        else if (action === 'edit') openEdit(row, origin);
        else if (action === 'toggle') toggleAccount(row);
    });

    document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', closeModals));
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.addEventListener('click', event => {
        if (event.target === backdrop) closeModals();
    }));

    // Close the row menu on outside click or when keyboard focus leaves it.
    document.addEventListener('click', event => { if (!event.target.closest('.actions-cell')) closeMenus(); });
    document.addEventListener('focusin', event => { if (!event.target.closest('.actions-cell')) closeMenus(); });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            const openMenu = tbody.querySelector('.row-menu:not([hidden])');
            closeMenus();
            closeModals();
            if (openMenu) openMenu.previousElementSibling.focus();
            return;
        }

        // Keep Tab inside an open dialog (it is aria-modal).
        if (event.key !== 'Tab') return;
        const modal = [userModal, detailsModal].find(item => !item.hidden);
        if (!modal) return;
        const focusable = Array.from(modal.querySelectorAll('button, input:not([type="hidden"]), select')).filter(el => !el.disabled);
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });

    [search, role, department, sort].forEach(control => control.addEventListener('input', () => {
        currentPage = 1;
        applyFilters();
    }));

    legendKeys.forEach(key => key.addEventListener('click', () => {
        setStatusFilter(key.dataset.statusFilter);
        currentPage = 1;
        applyFilters();
    }));

    // Click again to clear.
    attentionItems.forEach(item => item.addEventListener('click', () => {
        setAttention(attentionFilter === item.dataset.attention ? '' : item.dataset.attention);
        currentPage = 1;
        applyFilters();
    }));

    resetButton.addEventListener('click', () => {
        search.value = '';
        role.value = 'All';
        department.value = 'All';
        setStatusFilter('All');
        setAttention('');
        currentPage = 1;
        applyFilters();
    });

    prevPage.addEventListener('click', () => { if (currentPage > 1) { currentPage--; applyFilters(); } });
    nextPage.addEventListener('click', () => { currentPage++; applyFilters(); });   // clamped in applyFilters

    applyFilters();
})();