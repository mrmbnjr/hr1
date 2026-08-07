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

tabs.forEach(tab => {

    tab.addEventListener("click", () => {

        tabs.forEach(btn => btn.classList.remove("active"));
        tab.classList.add("active");

        const index = views[tab.dataset.view];

        slider.style.transform = `translateX(-${index * 100}%)`;

        updateHeight(index);

    });

});

window.addEventListener("load", () => {
    updateHeight(0);
});

window.addEventListener("resize", () => {
    const active = document.querySelector(".cs-view-tab.active");
    updateHeight(views[active.dataset.view]);
});

// Add department and position modals
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