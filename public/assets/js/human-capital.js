const tabs = document.querySelectorAll(".cs-view-tab");
const slider = document.querySelector(".cs-view-slider");
const wrapper = document.querySelector(".cs-view-wrapper");
const panels = document.querySelectorAll(".cs-view-panel");

const views = {
    organization: 0,
    departments: 1,
    positions: 2
};

function updateHeight(index) {
    wrapper.style.height = panels[index].offsetHeight + "px";
}

function activateView(viewName) {
    const tab = document.querySelector(`.cs-view-tab[data-view="${viewName}"]`);
    if (!tab) return;

    tabs.forEach(btn => btn.classList.remove("active"));
    tab.classList.add("active");

    const index = views[viewName];

    slider.style.transform = `translateX(-${index * 100}%)`;

    updateHeight(index);
}

tabs.forEach(tab => {

    tab.addEventListener("click", () => {

        activateView(tab.dataset.view);

        // Remember which tab the user was on so a reload
        // (e.g. after creating a department) can restore it.
        sessionStorage.setItem("hc_active_view", tab.dataset.view);

    });

});

window.addEventListener("load", () => {

    // Restore the last active tab if one was saved, otherwise
    // default to the first view (organization).
    const savedView = sessionStorage.getItem("hc_active_view");

    if (savedView && views.hasOwnProperty(savedView)) {
        activateView(savedView);
    } else {
        updateHeight(0);
    }

});

window.addEventListener("resize", () => {
    const active = document.querySelector(".cs-view-tab.active");
    updateHeight(views[active.dataset.view]);
});


/*
|--------------------------------------------------------------------------
| TOAST NOTIFICATIONS
|--------------------------------------------------------------------------
*/

function showToast(message, type = "success") {
    const container = document.getElementById("toastContainer");
    if (!container) return;

    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;

    const icon = type === "success" ? "fa-circle-check" : "fa-circle-exclamation";

    toast.innerHTML = `
        <i class="fa-solid ${icon}"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add("show"));

    setTimeout(() => {
        toast.classList.remove("show");

        // Remove it once the transition finishes...
        toast.addEventListener("transitionend", () => toast.remove());

        // ...but remove it anyway after a short grace period,
        // in case no CSS transition exists to fire the event.
        setTimeout(() => toast.remove(), 400);

    }, 3500);
}

/*
|--------------------------------------------------------------------------
| MODAL OPEN / CLOSE
|--------------------------------------------------------------------------
*/

document.addEventListener("click", (e) => {
    // Department modal
    if (
        e.target.closest("#addDepartment") ||
        e.target.closest("#createFirstDepartment")
    ) {
        document.getElementById("addDepartmentModal").classList.add("show");
    }

    // Position modal
    if (
        e.target.closest("#addPosition") ||
        e.target.closest("#createFirstPosition")
    ) {
        document.getElementById("addPositionModal").classList.add("show");
    }

    const closeId = e.target.closest("[data-close-modal]")?.dataset.closeModal;
    if (closeId) {
        document.getElementById(closeId).classList.remove("show");
    }

    if (e.target.classList.contains("modal-backdrop")) {
        e.target.closest(".cs-modal").classList.remove("show");
    }
});


/*
|--------------------------------------------------------------------------
| ADD DEPARTMENT FORM SUBMIT
|--------------------------------------------------------------------------
*/

const addDepartmentForm = document.getElementById("addDepartmentForm");

addDepartmentForm?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const submitBtn = addDepartmentForm.querySelector("button[type='submit']");
    submitBtn.disabled = true;

    try {
        const response = await fetch(addDepartmentForm.action, {
            method: "POST",
            body: new FormData(addDepartmentForm)
        });

        const result = await response.json();

        if (result.success) {
            sessionStorage.setItem("hc_active_view", "departments");
            location.reload();
        } else {
            showToast(result.message || "Failed to create department.", "error");
            submitBtn.disabled = false;
        }

    } catch (err) {
        showToast("Something went wrong. Please try again.", "error");
        submitBtn.disabled = false;
    }
});


/*
|--------------------------------------------------------------------------
| ADD POSITION FORM SUBMIT
|--------------------------------------------------------------------------
*/

const addPositionForm = document.getElementById("addPositionForm");

addPositionForm?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const submitBtn = addPositionForm.querySelector("button[type='submit']");
    submitBtn.disabled = true;

    try {
        const response = await fetch(addPositionForm.action, {
            method: "POST",
            body: new FormData(addPositionForm)
        });

        const result = await response.json();

        if (result.success) {
            sessionStorage.setItem("hc_active_view", "positions");
            location.reload();
        } else {
            showToast(result.message || "Failed to create position.", "error");
            submitBtn.disabled = false;
        }

    } catch (err) {
        showToast("Something went wrong. Please try again.", "error");
        submitBtn.disabled = false;
    }
});

/*
|--------------------------------------------------------------------------
| DELETE DEPARTMENT / POSITION (shared confirmation modal)
|--------------------------------------------------------------------------
*/

let pendingDeleteId = null;
let pendingDeleteType = null; // "department" | "position"

document.addEventListener("click", (e) => {
    const deleteDeptBtn = e.target.closest(".deleteDepartment");
    const deletePosBtn = e.target.closest(".deletePosition");

    if (deleteDeptBtn) {
        pendingDeleteId = deleteDeptBtn.dataset.id;
        pendingDeleteType = "department";

        document.getElementById("confirmTitle").textContent = "Delete Department";
        document.getElementById("confirmMessage").textContent =
            "Are you sure you want to delete this department? This cannot be undone.";

        document.getElementById("confirmModal").classList.add("show");
    }

    if (deletePosBtn) {
        pendingDeleteId = deletePosBtn.dataset.id;
        pendingDeleteType = "position";

        document.getElementById("confirmTitle").textContent = "Delete Position";
        document.getElementById("confirmMessage").textContent =
            "Are you sure you want to delete this position? This cannot be undone.";

        document.getElementById("confirmModal").classList.add("show");
    }
});

document.getElementById("cancelConfirm")?.addEventListener("click", () => {
    pendingDeleteId = null;
    pendingDeleteType = null;
    document.getElementById("confirmModal").classList.remove("show");
});

document.getElementById("confirmAction")?.addEventListener("click", async () => {
    if (!pendingDeleteId || !pendingDeleteType) return;

    const btn = document.getElementById("confirmAction");
    btn.disabled = true;

    const isDepartment = pendingDeleteType === "department";

    const page = isDepartment ? "delete-department" : "delete-position";
    const fieldName = isDepartment ? "department_id" : "position_id";

    try {
        const formData = new FormData();
        formData.append(fieldName, pendingDeleteId);

        const response = await fetch(`?page=${page}`, {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            sessionStorage.setItem("hc_active_view", isDepartment ? "departments" : "positions");
            location.reload();
        } else {
            showToast(result.message || `Failed to delete ${pendingDeleteType}.`, "error");
            document.getElementById("confirmModal").classList.remove("show");
        }

    } catch (err) {
        showToast("Something went wrong. Please try again.", "error");
        document.getElementById("confirmModal").classList.remove("show");
    } finally {
        btn.disabled = false;
        pendingDeleteId = null;
        pendingDeleteType = null;
    }
});