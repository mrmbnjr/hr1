/* ==========================
   Login - Show/Hide Password
========================== */

const password = document.getElementById("password");
const passwordToggle = document.getElementById("togglePassword");

if (password && passwordToggle) {

    passwordToggle.addEventListener("click", () => {

        if (password.type === "password") {
            password.type = "text";
            passwordToggle.innerHTML = "🙈";
        } else {
            password.type = "password";
            passwordToggle.innerHTML = "👁";
        }

    });

}


/* ==========================
   Sidebar
========================== */

const sidebar = document.querySelector(".sidebar");
const toggle = document.querySelector(".menu-toggle");
const overlay = document.querySelector(".sidebar-overlay");

toggle.addEventListener("click", () => {

    if (window.innerWidth <= 768) {
        sidebar.classList.toggle("active");
        overlay.classList.toggle("active");
    } else {
        sidebar.classList.toggle("collapsed");
    }

});

overlay.addEventListener("click", () => {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
});