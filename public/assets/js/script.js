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

// Graph
const employeeChart = document.getElementById("employeeGrowthChart");

if (employeeChart && window.employeeGrowthData) {

    new Chart(employeeChart, {

        type: "line",

        data: {

            labels: window.employeeGrowthData.labels,

            datasets: [{

                data: window.employeeGrowthData.data,

                borderColor: "#3b82f6",

                backgroundColor: "rgba(59,130,246,.15)",

                fill: true,

                tension: .4,

                pointRadius: 4,

                pointHoverRadius: 6

            }]

        },

        options: {

            responsive: true,

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                x: {
                    grid: {
                        display: false
                    }
                },

                y: {
                    beginAtZero: false
                }

            }

        }

    });
}

// Calendar
const calendarTitle = document.getElementById("calendarTitle");
const calendarBody = document.getElementById("calendarBody");

const prevBtn = document.getElementById("prevMonth");
const nextBtn = document.getElementById("nextMonth");

let currentDate = new Date();

function renderCalendar() {

    calendarBody.innerHTML = "";

    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    calendarTitle.textContent =
        currentDate.toLocaleString("default", {
            month: "long",
            year: "numeric"
        });

    const firstDay = new Date(year, month, 1);

    let startDay = firstDay.getDay();

    startDay = startDay === 0 ? 7 : startDay;

    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let day = 1;

    for (let week = 0; week < 6; week++) {

        const row = document.createElement("tr");

        for (let i = 1; i <= 7; i++) {

            const cell = document.createElement("td");

            if ((week === 0 && i < startDay) || day > daysInMonth) {

                cell.textContent = "";

            } else {

                cell.textContent = day;

                const today = new Date();

                if (
                    day === today.getDate() &&
                    month === today.getMonth() &&
                    year === today.getFullYear()
                ) {
                    cell.classList.add("today");
                }

                day++;
            }

            row.appendChild(cell);

        }

        calendarBody.appendChild(row);

        if (day > daysInMonth) {
            break;
        }
    }
}

renderCalendar();

prevBtn.addEventListener("click", () => {

    currentDate.setMonth(currentDate.getMonth() - 1);

    renderCalendar();

});

nextBtn.addEventListener("click", () => {

    currentDate.setMonth(currentDate.getMonth() + 1);

    renderCalendar();

});