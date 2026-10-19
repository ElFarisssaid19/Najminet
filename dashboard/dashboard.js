// Monthly expenses chart on the dashboard. The data comes from
// window.__monthlyExpenses / window.__monthLabels (set by dashboard.php).
// Chart.js resizes it with its container (window resize, drawer, rotation).
(() => {
  const canvas = document.getElementById('expensesChart');
  if (!canvas || typeof Chart === 'undefined') return;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  new Chart(canvas, {
    type: 'line',
    data: {
      labels: Array.isArray(window.__monthLabels) ? window.__monthLabels : [],
      datasets: [{
        label: 'Expenses (MAD)',
        data: Array.isArray(window.__monthlyExpenses) ? window.__monthlyExpenses : [],
        borderColor: '#8b5cf6',
        backgroundColor: 'rgba(139,92,246,0.15)',
        borderWidth: 2.5,
        pointRadius: 0,
        pointHitRadius: 12,
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: reduceMotion ? false : undefined,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#655f88', font: { family: 'Sora', size: 11 }, autoSkip: true, maxRotation: 0 } },
        y: { display: false, beginAtZero: true }
      }
    }
  });
})();
