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

// ===============================
// Applicant Submission Chart
// ===============================

const applicantChartCanvas = document.getElementById(
    "applicantGrowthChart"
);

const growthFilter = document.getElementById(
    "growthFilter"
);

const prevPeriod = document.getElementById(
    "prevPeriod"
);

const nextPeriod = document.getElementById(
    "nextPeriod"
);

const currentPeriod = document.getElementById(
    "currentPeriod"
);

const growthSubtitle = document.getElementById(
    "growthSubtitle"
);


let applicantChart;


let chartState = {

    view:
        window.applicantGrowthData?.view ?? "year",

    year:
        window.applicantGrowthData?.year ?? new Date().getFullYear(),

    month:
        window.applicantGrowthData?.month ?? new Date().getMonth() + 1,

    weekStart:
        window.applicantGrowthData?.weekStart ?? null

};



// Create chart first

if (applicantChartCanvas) {


    applicantChart = new Chart(
        applicantChartCanvas,
        {

            type: "line",


            data: {

                labels:
                    window.applicantGrowthData?.labels ?? [],


                datasets: [{

                    label: "Applications",

                    data:
                        window.applicantGrowthData?.data ?? [],

                    backgroundColor: "#b5822fa4",
                    borderColor: "#6F1414",

                    tension: 0.4,

                    fill: true,

                    pointRadius: 4,

                    pointHoverRadius: 6

                }]

            },


            options: {

                responsive: true,


                maintainAspectRatio: false,


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

                        beginAtZero: true,

                        ticks: {

                            precision: 0

                        }

                    }

                }

            }

        }

    );

}



// Update chart

function updateApplicantChart(data) {


    if (!applicantChart) {
        return;
    }


    applicantChart.data.labels =
        data.labels;


    applicantChart.data.datasets[0].data =
        data.data;


    applicantChart.update();


}




// Load chart data

async function loadApplicantChart() {


    const params = new URLSearchParams({

        view: chartState.view,

        year: chartState.year,

        month: chartState.month,

        weekStart: chartState.weekStart ?? ""

    });



    try {


            const response = await fetch(
                `/hr1/public/?page=dashboard-growth&${params}`
            );

        const data = await response.json();



        updateApplicantChart(data);



        currentPeriod.textContent =
            data.period;



        growthSubtitle.textContent =
            data.subtitle;

    } catch(error) {
        console.error(
            "Chart loading error:",
            error
        );
    }
}

// Change Year / Month / Week

growthFilter?.addEventListener(
    "change",
    () => {
        chartState.view =
            growthFilter.value;
        loadApplicantChart();
    }
);

// Previous button
prevPeriod?.addEventListener(
    "click",
    () => {

        if (chartState.view === "year") {
            chartState.year--;
        } else if (chartState.view === "month") {
            chartState.month--;
            if (chartState.month < 1) {
                chartState.month = 12;
                chartState.year--;
            }
        } else if (chartState.view === "week") {
            const date =
                new Date(chartState.weekStart);

            date.setDate(
                date.getDate() - 7
            );

            chartState.weekStart =
                date.toISOString().split("T")[0];
        }

        loadApplicantChart();
    }
);

// Next button
nextPeriod?.addEventListener(
    "click",
    () => {

        if (chartState.view === "year") {
            chartState.year++;
        } else if (chartState.view === "month") {
            chartState.month++;

            if (chartState.month > 12) {
                chartState.month = 1;
                chartState.year++;
            }
        }

        else if (chartState.view === "week") {
            const date =
                new Date(chartState.weekStart);

            date.setDate(
                date.getDate() + 7
            );

            chartState.weekStart =
                date.toISOString().split("T")[0];
        }

        loadApplicantChart();
    }
);

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

// Bar Graph
const jobCanvas = document.getElementById("jobApplicantsChart");

if (jobCanvas && window.jobApplicantsChart) {

    new Chart(jobCanvas, {
        type: "bar",
        data: {
            labels: window.jobApplicantsChart.labels,
            datasets: [{
                label: "Applicants",
                data: window.jobApplicantsChart.data,

                borderRadius: 8,
                borderWidth: 1,

                backgroundColor: "#b5822fc1",
                borderColor: "#6F1414",

                categoryPercentage: 0.7,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                }
            },
            title: {
                display: true,
                text: "Applicants by Job Position"
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

}