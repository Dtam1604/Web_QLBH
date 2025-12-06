/**
 * Revenue Chart - Khởi tạo biểu đồ doanh thu
 * @param {Array} chartData - Dữ liệu biểu đồ từ server
 */
function initRevenueChart(chartData) {
  const chartElement = document.getElementById('revenueChart');
  
  if (!chartElement || !chartData || chartData.length === 0) {
    return;
  }

  const labels = chartData.map(item => item.bucket);
  const totalRevenue = chartData.map(item => parseFloat(item.total_revenue || 0));
  const paidRevenue = chartData.map(item => parseFloat(item.paid_revenue || 0));

  new Chart(chartElement.getContext('2d'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Tổng doanh thu',
          data: totalRevenue,
          borderColor: '#764ba2',
          backgroundColor: 'rgba(118,75,162,0.2)',
          fill: true,
          tension: 0.3
        },
        {
          label: 'Đã thanh toán',
          data: paidRevenue,
          borderColor: '#4caf50',
          backgroundColor: 'rgba(76,175,80,0.2)',
          fill: true,
          tension: 0.3
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { 
          position: 'bottom',
          labels: {
            padding: 15,
            usePointStyle: true
          }
        },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: function(context) {
              return context.dataset.label + ': ' + 
                     parseFloat(context.parsed.y).toLocaleString('vi-VN') + ' đ';
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return value.toLocaleString('vi-VN') + ' đ';
            }
          }
        },
        x: {
          ticks: {
            maxRotation: 45,
            minRotation: 0
          }
        }
      },
      interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
      }
    }
  });
}

