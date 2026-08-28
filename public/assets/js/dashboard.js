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

const submissionsTableBody = document.getElementById(
    "submissionsTableBody"
);

const submissionsPageInfo = document.getElementById(
    "submissionsPageInfo"
);

const submissionsPrev = document.getElementById(
    "submissionsPrev"
);

const submissionsNext = document.getElementById(
    "submissionsNext"
);

const applicationSubmissionsCard = document.getElementById(
    "applicationSubmissionsCard"
);

const topAppliedJobsCard = document.getElementById(
    "topAppliedJobsCard"
);

if (applicationSubmissionsCard && topAppliedJobsCard) {
    const applicationGrid = applicationSubmissionsCard.parentElement;
    const jobsGrid = topAppliedJobsCard.parentElement;

    jobsGrid.insertBefore(
        applicationSubmissionsCard,
        jobsGrid.children[0]
    );

    applicationGrid.insertBefore(
        topAppliedJobsCard,
        applicationGrid.children[0]
    );
}


let applicantChart;


let chartState = {

    view:
        window.applicantGrowthData?.view ?? "year",

    year:
        window.applicantGrowthData?.year ?? new Date().getFullYear(),

    month:
        window.applicantGrowthData?.month ?? new Date().getMonth() + 1,

    weekStart:
        window.applicantGrowthData?.weekStart ?? null,

    page:
        window.applicantGrowthData?.page ?? 1,

    totalPages:
        window.applicantGrowthData?.totalPages ?? 1

};

function escapeHtml(value) {
    const element = document.createElement("div");
    element.textContent = value ?? "";
    return element.innerHTML;
}

function renderSubmissions(data) {
    if (!submissionsTableBody) {
        return;
    }

    const applicants = data.applicants ?? [];

    if (!applicants.length) {
        submissionsTableBody.innerHTML = `
            <tr>
                <td colspan="5" class="submissions-empty">
                    No application submissions for this period.
                </td>
            </tr>`;
    } else {
        submissionsTableBody.innerHTML = applicants.map((applicant) => {
            const status = applicant.application_status ?? "Submitted";
            const statusClass = status.toLowerCase().replaceAll(" ", "-");
            const name = applicant.fullname ?? "";
            const initial = name.charAt(0).toUpperCase() || "?";
            const submitted = applicant.applied_at
                ? new Date(applicant.applied_at).toLocaleDateString(
                    "en-US",
                    { month: "short", day: "numeric", year: "numeric" }
                )
                : "Not provided";

            return `
                <tr>
                    <td>
                        <a class="submission-applicant" href="?page=review&id=${Number(applicant.applicant_id)}">
                            <span class="submission-avatar">${escapeHtml(initial)}</span>
                            <strong>${escapeHtml(name)}</strong>
                        </a>
                    </td>
                    <td>${escapeHtml(applicant.position)}</td>
                    <td>${escapeHtml(applicant.address || "Not provided")}</td>
                    <td class="submission-date">${escapeHtml(submitted)}</td>
                    <td>
                        <span class="submission-status ${escapeHtml(statusClass)}">
                            ${escapeHtml(status)}
                        </span>
                    </td>
                </tr>`;
        }).join("");
    }

    const page = Number(data.page ?? 1);
    const totalPages = Number(data.totalPages ?? 1);
    chartState.page = page;
    chartState.totalPages = totalPages;

    if (submissionsPageInfo) {
        submissionsPageInfo.textContent = `Page ${page} of ${totalPages}`;
    }

    if (submissionsPrev) {
        submissionsPrev.disabled = page <= 1;
    }

    if (submissionsNext) {
        submissionsNext.disabled = page >= totalPages;
    }
}



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

                    backgroundColor: (context) => {
                        const { chart } = context;
                        const { chartArea, ctx } = chart;

                        if (!chartArea) {
                            return "rgba(181, 130, 47, 0.22)";
                        }

                        const gradient = ctx.createLinearGradient(
                            0,
                            chartArea.top,
                            0,
                            chartArea.bottom
                        );

                        gradient.addColorStop(0, "rgb(181, 130, 47)");
                        gradient.addColorStop(1, "rgba(181, 130, 47, 0.09)");

                        return gradient;
                    },
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

        weekStart: chartState.weekStart ?? "",

        pageNumber: chartState.page

    });



    try {


            const response = await fetch(
                `/hr1/public/?page=dashboard-growth&${params}`
            );

        const data = await response.json();



        updateApplicantChart(data);

        renderSubmissions(data);



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
        chartState.page = 1;
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

        chartState.page = 1;
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

        chartState.page = 1;
        loadApplicantChart();
    }
);

submissionsPrev?.addEventListener("click", () => {
    if (chartState.page > 1) {
        chartState.page--;
        loadApplicantChart();
    }
});

submissionsNext?.addEventListener("click", () => {
    const totalPages = Number(
        chartState.totalPages ?? chartState.page
    );

    if (chartState.page < totalPages) {
        chartState.page++;
        loadApplicantChart();
    }
});

renderSubmissions(window.applicantGrowthData ?? {});

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