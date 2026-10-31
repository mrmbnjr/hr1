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
                `?page=dashboard-growth&${params}`
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
const calendarModal = document.getElementById("calendarModal");
const calendarEventForm = document.getElementById("calendarEventForm");
const calendarEventList = document.getElementById("calendarEventList");
const addCalendarEvent = document.getElementById("addCalendarEvent");
const deleteCalendarEvent = document.getElementById("deleteCalendarEvent");
const calendarViewToggle = document.getElementById("calendarViewToggle");
const calendarSlides = document.getElementById("calendarSlides");
const calendarPagination = document.getElementById("calendarPagination");
const calendarPrevPage = document.getElementById("calendarPrevPage");
const calendarNextPage = document.getElementById("calendarNextPage");
const calendarPageInfo = document.getElementById("calendarPageInfo");

const prevBtn = document.getElementById("prevMonth");
const nextBtn = document.getElementById("nextMonth");

let currentDate = new Date();
let calendarNotesPage = 1;
const calendarNotesPerPage = 4;
const calendarStorageKey = "hr1-calendar-events";
let calendarEvents = [];

try {
    calendarEvents = JSON.parse(localStorage.getItem(calendarStorageKey) || "[]");
    calendarEvents = calendarEvents.filter((event) => event.source !== "interview");
} catch (error) {
    console.error("Unable to restore calendar events.", error);
}

const formatDateKey = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};

const scheduledInterviews = (window.scheduledInterviews || []).map((interview) => {
    const interviewDate = new Date(interview.interview_date.replace(" ", "T"));
    const date = formatDateKey(interviewDate);
    const time = `${String(interviewDate.getHours()).padStart(2, "0")}:${String(interviewDate.getMinutes()).padStart(2, "0")}`;

    return {
        id: `interview-${interview.interview_id}`,
        source: "interview",
        title: `${interview.round_type}: ${interview.applicant_name}`,
        date,
        time,
        notes: `${interview.position_title} · ${interview.interview_type}${interview.location ? ` · ${interview.location}` : ""}`,
        interview
    };
});

calendarEvents = [...calendarEvents, ...scheduledInterviews];

const saveCalendarEvents = () => {
    try {
        localStorage.setItem(
            calendarStorageKey,
            JSON.stringify(calendarEvents.filter((event) => event.source !== "interview"))
        );
        window.dispatchEvent(new CustomEvent("calendarEventsChanged"));
    } catch (error) {
        console.error("Unable to save calendar events.", error);
    }
};

const openCalendarModal = (date = formatDateKey(new Date()), event = null) => {
    if (!calendarModal || !calendarEventForm) return;
    document.getElementById("calendarModalTitle").textContent = event ? "Edit note or event" : "Add note or event";
    document.getElementById("calendarEventId").value = event?.id || "";
    document.getElementById("calendarEventTitle").value = event?.title || "";
    document.getElementById("calendarEventDate").value = event?.date || date;
    document.getElementById("calendarEventTime").value = event?.time || "";
    document.getElementById("calendarEventNotes").value = event?.notes || "";
    deleteCalendarEvent.hidden = !event;
    calendarModal.hidden = false;
    document.getElementById("calendarEventTitle").focus();
};

const closeCalendarModal = () => {
    if (calendarModal) calendarModal.hidden = true;
};

const renderCalendarEvents = () => {
    if (!calendarEventList) return;
    const today = formatDateKey(new Date());
    const upcoming = calendarEvents
        .filter((event) => event.date >= today)
        .sort((a, b) => `${a.date} ${a.time || ""}`.localeCompare(`${b.date} ${b.time || ""}`))
    const totalPages = Math.max(1, Math.ceil(upcoming.length / calendarNotesPerPage));
    calendarNotesPage = Math.min(calendarNotesPage, totalPages);
    const pageStart = (calendarNotesPage - 1) * calendarNotesPerPage;
    const pageEvents = upcoming.slice(pageStart, pageStart + calendarNotesPerPage);

    calendarEventList.innerHTML = pageEvents.length
        ? pageEvents.map((event) => `
            <button type="button" class="calendar-event-item" data-event-id="${event.id}">
                <span class="calendar-event-date">${new Date(`${event.date}T00:00:00`).toLocaleDateString("en-US", { month: "short", day: "numeric" })}</span>
                <span class="calendar-event-info">
                    <strong>${escapeHtml(event.title)}</strong>
                    <small>${escapeHtml(event.time ? `${formatEventTime(event.time)} · ${event.notes || ""}` : event.notes || "No additional details")}</small>
                </span>
                <i class="fa-solid fa-chevron-right"></i>
            </button>`).join("")
        : '<p class="calendar-event-empty">No upcoming events. Add a note to stay organized.</p>';

    if (calendarPagination && calendarPageInfo && calendarPrevPage && calendarNextPage) {
        calendarPagination.hidden = upcoming.length <= calendarNotesPerPage;
        calendarPageInfo.textContent = `Page ${calendarNotesPage} of ${totalPages}`;
        calendarPrevPage.disabled = calendarNotesPage === 1;
        calendarNextPage.disabled = calendarNotesPage === totalPages;
    }
};

const formatEventTime = (time) => {
    const [hours, minutes] = time.split(":").map(Number);
    const date = new Date();
    date.setHours(hours, minutes, 0, 0);
    return date.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" });
};

function renderCalendar() {

    if (!calendarBody || !calendarTitle) {
        return;
    }

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
                const dateKey = formatDateKey(new Date(year, month, day));
                const dayEvents = calendarEvents.filter((event) => event.date === dateKey);
                cell.dataset.date = dateKey;
                cell.classList.add("calendar-day");
                if (dayEvents.length) {
                    cell.classList.add("has-events");
                    cell.title = dayEvents.map((event) => event.title).join(", ");
                    cell.insertAdjacentHTML("beforeend", `<span class="calendar-event-dot" aria-hidden="true"></span>`);
                }

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

        renderCalendarEvents();
    }
}

renderCalendar();

prevBtn?.addEventListener("click", () => {

    currentDate.setMonth(currentDate.getMonth() - 1);

    renderCalendar();

});

nextBtn?.addEventListener("click", () => {

    currentDate.setMonth(currentDate.getMonth() + 1);

    renderCalendar();

});

calendarBody?.addEventListener("click", (event) => {
    const cell = event.target.closest(".calendar-day");
    if (!cell) return;
    const eventForDay = calendarEvents.find((item) => item.date === cell.dataset.date);
    openCalendarModal(cell.dataset.date, eventForDay || null);
});

calendarEventList?.addEventListener("click", (event) => {
    const item = event.target.closest(".calendar-event-item");
    if (!item) return;
    const calendarEvent = calendarEvents.find((entry) => entry.id === item.dataset.eventId);
    if (!calendarEvent) return;
    if (calendarEvent.source === "interview") {
        calendarViewToggle?.click();
        return;
    }
    openCalendarModal(calendarEvent.date, calendarEvent);
});

addCalendarEvent?.addEventListener("click", () => openCalendarModal());

calendarViewToggle?.addEventListener("click", () => {
    const showingNotes = calendarSlides.classList.toggle("show-notes");
    calendarViewToggle.setAttribute("aria-expanded", String(showingNotes));
    calendarViewToggle.querySelector("span").textContent = showingNotes ? "Calendar" : "Notes";
    calendarViewToggle.querySelector("i").className = showingNotes
        ? "fa-solid fa-calendar-days"
        : "fa-solid fa-note-sticky";
});

calendarPrevPage?.addEventListener("click", () => {
    if (calendarNotesPage > 1) {
        calendarNotesPage--;
        renderCalendarEvents();
    }
});

calendarNextPage?.addEventListener("click", () => {
    calendarNotesPage++;
    renderCalendarEvents();
});

calendarEventForm?.addEventListener("submit", (event) => {
    event.preventDefault();
    const id = document.getElementById("calendarEventId").value || `event-${Date.now()}`;
    const nextEvent = {
        id,
        title: document.getElementById("calendarEventTitle").value.trim(),
        date: document.getElementById("calendarEventDate").value,
        time: document.getElementById("calendarEventTime").value,
        notes: document.getElementById("calendarEventNotes").value.trim()
    };
    calendarEvents = [
        ...calendarEvents.filter((item) => item.id !== id && item.source !== "interview"),
        ...scheduledInterviews,
        nextEvent
    ];
    saveCalendarEvents();
    closeCalendarModal();
    renderCalendar();
});

deleteCalendarEvent?.addEventListener("click", () => {
    const id = document.getElementById("calendarEventId").value;
    calendarEvents = calendarEvents.filter((item) => item.id !== id && item.source !== "interview");
    calendarEvents = [...calendarEvents, ...scheduledInterviews];
    saveCalendarEvents();
    closeCalendarModal();
    renderCalendar();
});

calendarModal?.addEventListener("click", (event) => {
    if (event.target.closest("[data-calendar-close]")) closeCalendarModal();
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && calendarModal && !calendarModal.hidden) {
        closeCalendarModal();
    }
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

// ===============================================================
// EMPLOYEE DASHBOARD
//   • request-status doughnut, kept in step with the light/dark theme
//   • count-up on the status tiles
// Both blocks bail out when their elements are absent, so this file
// stays safe to load on the admin dashboard as well.
// ===============================================================

const employeeRequestCanvas = document.getElementById(
    "employeeRequestChart"
);

const employeeRequestStats = window.employeeRequestStats;

const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
).matches;

/* Reads a design token off :root so canvas colours follow the theme. */
function themeColor(token, fallback) {
    const value = getComputedStyle(document.documentElement)
        .getPropertyValue(token)
        .trim();

    return value || fallback;
}

let employeeRequestChart = null;

if (
    employeeRequestCanvas &&
    employeeRequestStats &&
    typeof Chart !== "undefined"
) {
    // Palette comes from PHP so the chart slices and the legend dots
    // are generated from one source.
    const palette = employeeRequestStats.palette ?? {
        pending: "#d97706",
        approved: "#2e8b57",
        completed: "#2563eb",
        rejected: "#c2413b"
    };

    employeeRequestChart = new Chart(employeeRequestCanvas, {
        type: "doughnut",
        data: {
            labels: ["Pending", "Approved", "Completed", "Rejected"],
            datasets: [{
                data: [
                    employeeRequestStats.pending,
                    employeeRequestStats.approved,
                    employeeRequestStats.completed,
                    employeeRequestStats.rejected
                ],
                backgroundColor: [
                    palette.pending,
                    palette.approved,
                    palette.completed,
                    palette.rejected
                ],
                // The card surface, not a hard-coded cream: otherwise the
                // slice gaps glow white in dark mode.
                borderColor: themeColor("--card-bg", "#fbf3e6"),
                borderWidth: 4,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "72%",
            animation: prefersReducedMotion ? false : { duration: 700 },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // The theme toggle rewrites data-theme on <html>; repaint the borders.
    new MutationObserver(function () {
        employeeRequestChart.data.datasets[0].borderColor =
            themeColor("--card-bg", "#fbf3e6");

        employeeRequestChart.update("none");
    }).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["data-theme"]
    });
}

// ---------- Status tile count-up ----------

const employeeTileValues = document.querySelectorAll(
    ".emp-tile-value[data-count]"
);

if (employeeTileValues.length && !prefersReducedMotion) {
    employeeTileValues.forEach(function (element) {
        const target = Number(element.dataset.count ?? 0);

        if (!Number.isFinite(target) || target <= 0) {
            return;
        }

        const duration = 620;
        const startedAt = performance.now();

        function step(now) {
            const progress = Math.min((now - startedAt) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);

            element.textContent = Math.round(target * eased);

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                element.textContent = target;
            }
        }

        element.textContent = "0";
        requestAnimationFrame(step);
    });
}