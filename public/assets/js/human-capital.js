"use strict";

/* ==========================================================
   HUMAN CAPITAL MANAGEMENT
========================================================== */

document.addEventListener("DOMContentLoaded", () => {

    /* ======================================================
       ELEMENTS
    ====================================================== */

    const tabs = document.querySelectorAll(".cs-view-tab");

    const panels = {
        organization: document.getElementById("organizationView"),
        departments: document.getElementById("departmentsView"),
        positions: document.getElementById("positionsView")
    };

    const searchInput = document.getElementById("csSearchInput");

    const departmentFilter = document.getElementById("csDepartmentFilter");
    const employmentFilter = document.getElementById("csEmploymentFilter");
    const statusFilter = document.getElementById("csStatusFilter");

    const dropdownButtons = document.querySelectorAll(".dropdown-btn");



    /* ======================================================
       VIEW TOGGLE
    ====================================================== */

    tabs.forEach(tab => {

        tab.addEventListener("click", () => {

            tabs.forEach(button =>
                button.classList.remove("active")
            );

            tab.classList.add("active");

            Object.values(panels).forEach(panel => {

                if (panel) {

                    panel.hidden = true;

                }

            });

            const target = panels[tab.dataset.view];

            if (target) {

                target.hidden = false;

            }

        });

    });



    /* ======================================================
       SEARCH
    ====================================================== */

    if (searchInput) {

        searchInput.addEventListener("keyup", () => {

            const keyword = searchInput.value.toLowerCase();

            filterDepartments(keyword);

            filterPositions(keyword);

        });

    }



    function filterDepartments(keyword) {

        document.querySelectorAll(".department-card").forEach(card => {

            card.style.display =

                card.innerText.toLowerCase().includes(keyword)

                    ? ""

                    : "none";

        });

    }



    function filterPositions(keyword) {

        document.querySelectorAll(".cs-table tbody tr").forEach(row => {

            row.style.display =

                row.innerText.toLowerCase().includes(keyword)

                    ? ""

                    : "none";

        });

    }



    /* ======================================================
       FILTERS
    ====================================================== */

    departmentFilter?.addEventListener("change", applyFilters);

    employmentFilter?.addEventListener("change", applyFilters);

    statusFilter?.addEventListener("change", applyFilters);



    function applyFilters() {

        const department = departmentFilter.value.toLowerCase();

        const employment = employmentFilter.value.toLowerCase();

        const status = statusFilter.value.toLowerCase();

        document.querySelectorAll(".cs-table tbody tr").forEach(row => {

            const text = row.innerText.toLowerCase();

            let visible = true;

            if (department && !text.includes(department)) {

                visible = false;

            }

            if (employment && !text.includes(employment)) {

                visible = false;

            }

            if (status && !text.includes(status)) {

                visible = false;

            }

            row.style.display = visible ? "" : "none";

        });

    }



    /* ======================================================
       DROPDOWN MENUS
    ====================================================== */

    dropdownButtons.forEach(button => {

        button.addEventListener("click", e => {

            e.stopPropagation();

            closeDropdowns();

            const menu = button.nextElementSibling;

            menu?.classList.toggle("show");

        });

    });



    document.addEventListener("click", () => {

        closeDropdowns();

    });



    function closeDropdowns() {

        document.querySelectorAll(".dropdown-menu").forEach(menu => {

            menu.classList.remove("show");

        });

    }

        /* ======================================================
       DEPARTMENT MODAL
    ====================================================== */

    const departmentModal = document.getElementById("departmentModal");

    const departmentForm = document.getElementById("departmentForm");

    const departmentId = document.getElementById("department_id");

    const departmentName = document.getElementById("department_name");

    const departmentDescription = document.getElementById("description");



    document

        .getElementById("csAddDepartmentBtn")

        ?.addEventListener("click", () => {

            resetDepartmentForm();

            openDepartmentModal();

        });



    document

        .getElementById("createFirstDepartment")

        ?.addEventListener("click", () => {

            resetDepartmentForm();

            openDepartmentModal();

        });



    document.querySelectorAll(".closeModal").forEach(button => {

        button.addEventListener("click", closeDepartmentModal);

    });



    function openDepartmentModal() {

        if (!departmentModal) return;

        departmentModal.classList.add("show");

        departmentModal.setAttribute("aria-hidden", "false");

    }



    function closeDepartmentModal() {

        if (!departmentModal) return;

        departmentModal.classList.remove("show");

        departmentModal.setAttribute("aria-hidden", "true");

    }



    function resetDepartmentForm() {

        if (!departmentForm) return;

        departmentForm.reset();

        departmentId.value = "";

    }



    /* ======================================================
       LOAD DEPARTMENT
    ====================================================== */

    document.querySelectorAll(".editDepartment").forEach(button => {

        button.addEventListener("click", () => {

            loadDepartment(button.dataset.id);

        });

    });



    async function loadDepartment(id) {

        try {

            const response = await fetch(

                `?page=human-capital-department&id=${id}`

            );

            const result = await response.json();

            if (!result.success) {

                showToast("Unable to load department.", "error");

                return;

            }

            departmentId.value = result.data.department_id;

            departmentName.value = result.data.department_name;

            departmentDescription.value =

                result.data.description ?? "";

            openDepartmentModal();

        }

        catch (error) {

            console.error(error);

            showToast("Something went wrong.", "error");

        }

    }



    /* ======================================================
       SAVE DEPARTMENT
    ====================================================== */

    departmentForm?.addEventListener("submit", saveDepartment);



    async function saveDepartment(event) {

        event.preventDefault();

        if (departmentName.value.trim() === "") {

            showToast(

                "Department name is required.",

                "warning"

            );

            departmentName.focus();

            return;

        }

        const formData = new FormData(departmentForm);

        try {

            const response = await fetch(

                "?page=human-capital-save-department",

                {

                    method: "POST",

                    body: formData

                }

            );

            const result = await response.json();

            if (!result.success) {

                showToast(

                    result.message,

                    "error"

                );

                return;

            }

            showToast(

                "Department saved successfully."

            );

            closeDepartmentModal();

            setTimeout(() => {

                location.reload();

            }, 700);

        }

        catch (error) {

            console.error(error);

            showToast(

                "Unable to save department.",

                "error"

            );

        }

    }



    /* ======================================================
       DEPARTMENT DETAILS
    ====================================================== */

    const detailsModal = document.getElementById(

        "departmentDetailsModal"

    );

    const detailsBody = document.getElementById(

        "departmentDetailsBody"

    );



    document.querySelectorAll(".viewDepartment").forEach(button => {

        button.addEventListener("click", () => {

            showDepartment(button.dataset.id);

        });

    });



    async function showDepartment(id) {

        try {

            const response = await fetch(

                `?page=human-capital-department&id=${id}`

            );

            const result = await response.json();

            if (!result.success) {

                showToast(

                    "Unable to load department.",

                    "error"

                );

                return;

            }

            const department = result.data;

            detailsBody.innerHTML = `

                <div class="department-details">

                    <h2>${department.department_name}</h2>

                    <p>

                        ${department.description || "No description available."}

                    </p>

                </div>

            `;

            detailsModal.classList.add("show");

        }

        catch (error) {

            console.error(error);

            showToast(

                "Unable to load department.",

                "error"

            );

        }

    }



    detailsModal

        ?.querySelector(".closeModal")

        ?.addEventListener("click", () => {

            detailsModal.classList.remove("show");

        });
            /* ======================================================
       DELETE DEPARTMENT
    ====================================================== */

    document.querySelectorAll(".deleteDepartment").forEach(button => {

        button.addEventListener("click", () => {

            confirmAction(
                "Delete Department",
                "This department will be permanently deleted.",
                () => deleteDepartment(button.dataset.id)
            );

        });

    });

    async function deleteDepartment(id) {

        try {

            const formData = new FormData();

            formData.append("id", id);

            const response = await fetch(
                "?page=human-capital-delete-department",
                {
                    method: "POST",
                    body: formData
                }
            );

            const result = await response.json();

            if (!result.success) {

                showToast(result.message, "error");

                return;

            }

            showToast("Department deleted.");

            setTimeout(() => {

                location.reload();

            }, 700);

        }

        catch (error) {

            console.error(error);

            showToast("Unable to delete department.", "error");

        }

    }



    /* ======================================================
       CONFIRMATION MODAL
    ====================================================== */

    const confirmModal = document.getElementById("confirmModal");

    const confirmTitle = document.getElementById("confirmTitle");

    const confirmMessage = document.getElementById("confirmMessage");

    const confirmButton = document.getElementById("confirmButton");

    let confirmCallback = null;



    function confirmAction(title, message, callback) {

        if (!confirmModal) return;

        confirmTitle.textContent = title;

        confirmMessage.textContent = message;

        confirmCallback = callback;

        confirmModal.classList.add("show");

    }



    document

        .getElementById("confirmCancel")

        ?.addEventListener("click", () => {

            confirmModal.classList.remove("show");

        });



    confirmButton?.addEventListener("click", () => {

        confirmModal.classList.remove("show");

        if (typeof confirmCallback === "function") {

            confirmCallback();

        }

    });



    document.querySelectorAll(".closeConfirm").forEach(button => {

        button.addEventListener("click", () => {

            confirmModal.classList.remove("show");

        });

    });
        /* ======================================================
       TOAST NOTIFICATIONS
    ====================================================== */

    const toastContainer = document.getElementById("toastContainer");

    function showToast(message, type = "success") {

        if (!toastContainer) {

            alert(message);
            return;

        }

        const toast = document.createElement("div");

        toast.className = `toast toast-${type}`;

        toast.innerHTML = `
            <div class="toast-content">
                <span>${message}</span>
            </div>
        `;

        toastContainer.appendChild(toast);

        requestAnimationFrame(() => {

            toast.classList.add("show");

        });

        setTimeout(() => {

            toast.classList.remove("show");

            setTimeout(() => {

                toast.remove();

            }, 300);

        }, 3000);

    }



    /* ======================================================
       GLOBAL MODAL CLOSE
    ====================================================== */

    document.querySelectorAll(".modal").forEach(modal => {

        modal.addEventListener("click", e => {

            if (e.target === modal) {

                modal.classList.remove("show");

            }

        });

    });



    document.addEventListener("keydown", e => {

        if (e.key === "Escape") {

            document.querySelectorAll(".modal.show").forEach(modal => {

                modal.classList.remove("show");

            });

        }

    });



    /* ======================================================
       HELPERS
    ====================================================== */

    function reloadAfterDelay(delay = 700) {

        setTimeout(() => {

            location.reload();

        }, delay);

    }



    function post(url, data) {

        return fetch(url, {

            method: "POST",

            body: data

        }).then(response => response.json());

    }



    function get(url) {

        return fetch(url)

            .then(response => response.json());

    }



    /* ======================================================
       INITIALIZATION
    ====================================================== */

    console.log("Human Capital initialized.");

});