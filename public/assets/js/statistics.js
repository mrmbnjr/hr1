document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | Check Statistics Data
        |--------------------------------------------------------------------------
        */

        if (
            typeof window.recruitmentStatistics ===
            'undefined'
        ) {
            return;
        }


        const data =
            window.recruitmentStatistics;



        /*
        |--------------------------------------------------------------------------
        | Application Trend Chart
        |--------------------------------------------------------------------------
        */

        const trendCanvas =
            document.getElementById(
                'applicationTrendChart'
            );


        if (
            trendCanvas &&
            typeof Chart !== 'undefined'
        ) {

            new Chart(
                trendCanvas,
                {
                    type: 'line',

                    data: {

                        labels:
                            data.trendLabels,

                        datasets: [
                            {
                                label:
                                    'Applications',

                                data:
                                    data.trendValues,

                                tension:
                                    0.35,

                                fill:
                                    true,

                                pointRadius:
                                    3,

                                pointHoverRadius:
                                    5,

                                borderWidth:
                                    2,

                                borderColor:
                                    '#6f1414',

                                backgroundColor:
                                    'rgba(242, 193, 78, 0.22)',

                                pointBackgroundColor:
                                    '#6f1414',

                                pointBorderColor:
                                    '#faf6ee',

                                pointHoverBackgroundColor:
                                    '#cf9a2c'
                            }
                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio:
                            false,

                        interaction: {
                            mode: 'index',
                            intersect: false
                        },

                        plugins: {

                            legend: {
                                display: false
                            },

                            tooltip: {

                                displayColors:
                                    false,

                                callbacks: {

                                    label:
                                        function (
                                            context
                                        ) {

                                            return (
                                                context.parsed.y +
                                                ' application' +
                                                (
                                                    context.parsed.y === 1
                                                        ? ''
                                                        : 's'
                                                )
                                            );
                                        }
                                }
                            }
                        },

                        scales: {

                            x: {

                                grid: {
                                    display: false
                                },

                                ticks: {
                                    color: '#888',
                                    font: {
                                        size: 10
                                    }
                                }
                            },

                            y: {

                                beginAtZero: true,

                                ticks: {

                                    precision: 0,

                                    color: '#888',

                                    font: {
                                        size: 10
                                    }
                                },

                                grid: {
                                    color: '#eeeeee'
                                }
                            }
                        }
                    }
                }
            );
        }



        /*
        |--------------------------------------------------------------------------
        | Status Breakdown Chart
        |--------------------------------------------------------------------------
        */

        const statusCanvas =
            document.getElementById(
                'statusBreakdownChart'
            );


        if (
            statusCanvas &&
            typeof Chart !== 'undefined'
        ) {

            new Chart(
                statusCanvas,
                {
                    type: 'doughnut',

                    data: {

                        labels:
                            data.statusLabels,

                        datasets: [
                            {
                                data:
                                    data.statusValues,

                                backgroundColor:
                                    data.statusLabels.map(
                                        function (label) {
                                            const colors = {
                                                'Submitted':
                                                    '#6b7280',
                                                'Under Review':
                                                    '#cf9a2c',
                                                'Interview':
                                                    '#6f1414',
                                                'Hired':
                                                    '#1a7d4c',
                                                'Rejected':
                                                    '#c45c56'
                                            };

                                            return colors[label]
                                                || '#6b7280';
                                        }
                                    ),

                                borderWidth:
                                    3,

                                borderColor:
                                    '#ffffff'
                            }
                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio:
                            false,

                        cutout:
                            '68%',

                        plugins: {

                            legend: {
                                display: false
                            },

                            tooltip: {

                                callbacks: {

                                    label:
                                        function (
                                            context
                                        ) {

                                            const label =
                                                context.label ||
                                                '';

                                            const value =
                                                context.parsed ||
                                                0;

                                            return (
                                                label +
                                                ': ' +
                                                value
                                            );
                                        }
                                }
                            }
                        }
                    }
                }
            );
        }

    }
);