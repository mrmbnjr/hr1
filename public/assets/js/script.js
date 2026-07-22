document.addEventListener("DOMContentLoaded", () => {

    const sidebar = document.getElementById("ramyumSidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebarClose = document.getElementById("sidebarClose");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    if (!sidebar || !sidebarToggle) {
        console.log("Sidebar elements not found");
        return;
    }

    sidebarToggle.addEventListener("click", () => {

        if (window.innerWidth <= 960) {

            const isOpen = sidebar.classList.toggle("is-open");

            sidebarOverlay?.classList.toggle("is-visible", isOpen);

            sidebarToggle.setAttribute("aria-expanded", isOpen);

        } else {
            document.body.classList.toggle("sidebar-hidden");
        }

    });

    sidebarClose?.addEventListener("click", () => {
        sidebar.classList.remove("is-open");
        sidebarOverlay?.classList.remove("is-visible");
        sidebarToggle.setAttribute("aria-expanded", "false");
    });

    sidebarOverlay?.addEventListener("click", () => {
        sidebar.classList.remove("is-open");
        sidebarOverlay?.classList.remove("is-visible");
        sidebarToggle.setAttribute("aria-expanded", "false");
    });

});

// Job Postings
document.addEventListener("DOMContentLoaded", () => {

    // Highlight search input when typing
    const search = document.querySelector('.filter-group input');

    if(search){
        search.addEventListener("input", function(){
            this.classList.toggle("has-value", this.value.trim() !== "");
        });
    }

});

document.addEventListener("DOMContentLoaded", () => {

    // Highlight completed fields
    document.querySelectorAll("input, textarea, select").forEach(field => {

        const update = () => {
            field.classList.toggle(
                "has-value",
                field.value.trim() !== ""
            );
        };

        update();

        field.addEventListener("input", update);
        field.addEventListener("change", update);

    });

    // Prevent double submit
    const form = document.querySelector(".form-card");

    if(form){

        form.addEventListener("submit", () => {

            const btn = form.querySelector(".btn-primary");

            if(btn){

                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            }

        });

    }

});