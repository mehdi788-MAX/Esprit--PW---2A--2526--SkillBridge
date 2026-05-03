// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

// Pie Chart Example
var ctx = document.getElementById("myPieChart");
if (ctx) {
  var pieConfig = window.dashboardPieConfig || {
    labels: [
      "Demandes sans proposition",
      "Demandes avec une proposition",
      "Demandes avec plusieurs propositions"
    ],
    data: [0, 0, 0],
    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
    cutoutPercentage: 80
  };

  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: pieConfig.labels,
      datasets: [{
        data: pieConfig.data,
        backgroundColor: pieConfig.backgroundColor,
        hoverBackgroundColor: pieConfig.hoverBackgroundColor,
        hoverBorderColor: "rgba(234, 236, 244, 1)",
      }],
    },
    options: {
      maintainAspectRatio: false,
      tooltips: {
        backgroundColor: "rgb(255,255,255)",
        bodyFontColor: "#858796",
        borderColor: '#dddfeb',
        borderWidth: 1,
        xPadding: 15,
        yPadding: 15,
        displayColors: false,
        caretPadding: 10,
        callbacks: {
          label: function(tooltipItem, data) {
            var label = data.labels[tooltipItem.index] || '';
            var value = data.datasets[0].data[tooltipItem.index] || 0;
            return label + ': ' + value;
          }
        }
      },
      legend: {
        display: false
      },
      cutoutPercentage: pieConfig.cutoutPercentage || 80,
    },
  });
}
