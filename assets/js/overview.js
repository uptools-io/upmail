/**
 * Overview page functionality.
 *
 * Handles chart initialization and date range filtering.
 */
jQuery(document).ready(function($) {
    // Initialize datepicker
    $('.date-input').datepicker({
        dateFormat: 'yy-mm-dd',
        maxDate: '0'
    });

    // Calculate cumulative data
    var cumulativeData = upmail_chart_data.dailyData.reduce((acc, curr, i) => {
        acc.push((acc[i-1] || 0) + curr);
        return acc;
    }, []);

    // Initialize ApexCharts
    var options = {
        series: [
            {
                name: 'Daily Emails',
                type: 'column',
                data: upmail_chart_data.dailyData
            },
            {
                name: 'Total Emails',
                type: 'line',
                data: upmail_chart_data.cumulativeData
            }
        ],
        chart: {
            type: 'line',
            height: 400,
            toolbar: {
                show: false
            },
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
        },
        stroke: {
            width: [0, 2],
            curve: 'smooth'
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '60%',
            }
        },
        colors: ['#1f2937', '#6b7280'],
        dataLabels: {
            enabled: false
        },
        markers: {
            size: [0, 3],
            strokeWidth: 2,
            hover: {
                size: 4
            }
        },
        xaxis: {
            categories: upmail_chart_data.labels,
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            },
            labels: {
                style: {
                    colors: '#6b7280',
                    fontSize: '12px'
                }
            }
        },
        yaxis: [
            {
                title: {
                    text: 'Daily Emails',
                    style: {
                        color: '#1f2937'
                    }
                },
                labels: {
                    style: {
                        colors: '#6b7280',
                        fontSize: '12px'
                    }
                }
            },
            {
                opposite: true,
                title: {
                    text: 'Total Emails',
                    style: {
                        color: '#6b7280'
                    }
                },
                labels: {
                    style: {
                        colors: '#6b7280',
                        fontSize: '12px'
                    }
                }
            }
        ],
        grid: {
            borderColor: '#e5e7eb',
            strokeDashArray: 4,
            xaxis: {
                lines: {
                    show: false
                }
            }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function (val) {
                    return val + ' emails'
                }
            },
            marker: {
                show: false
            },
            fixed: {
                enabled: true,
                position: 'right',
                offsetY: 0
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            markers: {
                width: 8,
                height: 8,
                radius: 4
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#upmail-stats-chart"), options);
    chart.render();

    // Handle date range filter
    $('#upmail-apply-date').on('click', function() {
        var startDate = $('#upmail-start-date').val();
        var endDate = $('#upmail-end-date').val();
        if (startDate && endDate) {
            window.location.href = '?page=upmail-settings&tab=overview&start_date=' + startDate + '&end_date=' + endDate;
        }
    });
}); 