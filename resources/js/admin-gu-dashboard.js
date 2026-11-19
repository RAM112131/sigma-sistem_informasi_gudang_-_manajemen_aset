import Chart from 'chart.js/auto';

const chartInstances = [];
let movementChart = null;

export function mountAdminGUDashboard() {
  const dashboard = document.querySelector('[data-admin-gu-dashboard]');
  if (!dashboard) {
    return;
  }

  destroyAdminGUDashboardCharts();

  const dataElement = dashboard.querySelector('[data-admin-gu-chart-data]');
  if (!dataElement) {
    throw new Error('Data grafik dashboard Admin GU tidak ditemukan.');
  }

  let chartData = JSON.parse(dataElement.textContent || '{}');
  const movementCanvas = dashboard.querySelector('[data-admin-gu-movement-chart]');

  if (movementCanvas && chartData.movements?.has_data) {
    movementChart = createMovementChart(movementCanvas, chartData.movements);
  }

  const chartForm = dashboard.querySelector('[data-admin-gu-chart-form]');
  if (chartForm) {
    bindMovementChartFilters(dashboard, chartForm, (movements) => {
      chartData = { ...chartData, movements };
      dataElement.textContent = JSON.stringify(chartData);
    });
  }

  const divisionCanvas = dashboard.querySelector('[data-admin-gu-division-chart]');

  if (divisionCanvas && chartData.divisions?.has_data) {
    chartInstances.push(createDivisionChart(divisionCanvas, chartData.divisions));
  }
}

function createDivisionChart(canvas, divisions) {
  return new Chart(canvas, {
    type: 'bar',
    data: {
      labels: divisions.labels,
      datasets: [{
        label: 'Transaksi Pengeluaran',
        data: divisions.values,
        backgroundColor: '#145C54',
        borderRadius: 4,
        maxBarThickness: 26,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (context) => `${new Intl.NumberFormat('id-ID').format(context.parsed.x)} transaksi`,
          },
        },
      },
      scales: {
        x: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Jumlah transaksi',
            color: '#5B6B79',
            font: { size: 9 },
          },
          ticks: {
            color: '#5B6B79',
            precision: 0,
            font: { size: 9 },
          },
          grid: { color: 'rgba(91, 107, 121, 0.16)', borderDash: [2, 4] },
        },
        y: {
          grid: { display: false },
          ticks: {
            color: '#5B6B79',
            font: { size: 9 },
          },
        },
      },
    },
  });
}

function createMovementChart(canvas, movements) {
  return new Chart(canvas, {
    type: 'line',
    data: {
      labels: movements.labels,
      datasets: [
        {
          label: 'Transaksi Masuk',
          data: movements.incoming,
          borderColor: '#145C54',
          backgroundColor: 'rgba(20, 92, 84, 0.08)',
          fill: true,
          tension: 0.38,
          borderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 4.5,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#145C54',
          pointBorderWidth: 1.8,
        },
        {
          label: 'Transaksi Keluar',
          data: movements.outgoing,
          borderColor: '#C1443C',
          backgroundColor: 'rgba(193, 68, 60, 0.08)',
          fill: false,
          tension: 0.38,
          borderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 4.5,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#C1443C',
          pointBorderWidth: 1.8,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index',
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (context) => `${context.dataset.label}: ${new Intl.NumberFormat('id-ID').format(context.parsed.y)} transaksi`,
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            color: '#5B6B79',
            maxRotation: 0,
            autoSkip: true,
            maxTicksLimit: 7,
            font: { size: 9 },
          },
        },
        y: {
          beginAtZero: true,
          ticks: {
            color: '#5B6B79',
            count: 5,
            precision: 0,
            font: { size: 9 },
          },
          grid: { color: 'rgba(91, 107, 121, 0.16)', borderDash: [2, 4] },
        },
      },
    },
  });
}

function bindMovementChartFilters(dashboard, form, updateChartData) {
  const periodInput = form.querySelector('[data-chart-period]');
  const weekButton = form.querySelector('[data-chart-week]');
  const startDate = form.querySelector('[data-chart-start-date]');
  const endDate = form.querySelector('[data-chart-end-date]');
  const errorMessage = dashboard.querySelector('[data-chart-filter-error]');
  const statusMessage = dashboard.querySelector('[data-chart-filter-status]');
  let activeRequest = null;
  let statusTimeout = null;

  const setLoading = (isLoading) => {
    form.setAttribute('aria-busy', String(isLoading));
    weekButton.disabled = isLoading;
  };

  const updateMovementChart = (movements) => {
    const chartPanel = dashboard.querySelector('[data-movement-chart-panel]');
    const chartFooter = dashboard.querySelector('[data-movement-chart-footer]');
    const emptyState = dashboard.querySelector('[data-movement-chart-empty]');
    const canvas = dashboard.querySelector('[data-admin-gu-movement-chart]');

    chartPanel.hidden = !movements.has_data;
    chartFooter.hidden = !movements.has_data;
    emptyState.hidden = movements.has_data;

    if (!movements.has_data) {
      movementChart?.destroy();
      movementChart = null;

      return;
    }

    const averageValue = dashboard.querySelector('[data-chart-average]');
    averageValue.textContent = `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(movements.average_outgoing_transactions_per_day)} transaksi`;

    const ratio = dashboard.querySelector('[data-chart-ratio]');
    ratio.textContent = movements.incoming_ratio === null
      ? '-'
      : `${movements.incoming_ratio > 0 ? '+' : ''}${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(movements.incoming_ratio)}%`;
    ratio.setAttribute('aria-label', movements.incoming_ratio === null ? 'Tidak tersedia' : ratio.textContent);
    ratio.classList.toggle('is-positive', movements.incoming_ratio !== null && movements.incoming_ratio >= 0);
    ratio.classList.toggle('is-negative', movements.incoming_ratio !== null && movements.incoming_ratio < 0);

    if (movementChart) {
      movementChart.data.labels = movements.labels;
      movementChart.data.datasets[0].data = movements.incoming;
      movementChart.data.datasets[1].data = movements.outgoing;
      movementChart.update();

      return;
    }

    movementChart = createMovementChart(canvas, movements);
  };

  const applyFilter = async () => {
    if (!form.reportValidity()) {
      return;
    }

    if (startDate.value > endDate.value) {
      errorMessage.textContent = 'Tanggal akhir harus sama dengan atau setelah tanggal mulai.';
      errorMessage.hidden = false;
      statusMessage.hidden = true;
      statusMessage.textContent = '';
      endDate.focus();

      return;
    }

    activeRequest?.abort();
    const request = new AbortController();
    activeRequest = request;

    if (statusTimeout !== null) {
      window.clearTimeout(statusTimeout);
      statusTimeout = null;
    }

    errorMessage.hidden = true;
    errorMessage.textContent = '';
    statusMessage.hidden = false;
    statusMessage.textContent = 'Memuat grafik...';
    const query = new URLSearchParams(new FormData(form));
    setLoading(true);

    try {
      const response = await fetch(`${form.action}?${query.toString()}`, {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        signal: request.signal,
      });
      let payload;

      try {
        payload = await response.json();
      } catch {
        throw new Error('Respons server tidak dapat dibaca. Coba lagi.');
      }

      if (!response.ok) {
        const validationMessages = Object.values(payload.errors || {}).flat();
        throw new Error(validationMessages.join(' ') || payload.message || 'Grafik gagal diperbarui. Coba lagi.');
      }

      startDate.value = payload.chartRange.start_date;
      endDate.value = payload.chartRange.end_date;
      periodInput.value = payload.chartRange.period;
      weekButton.setAttribute('aria-pressed', String(payload.chartRange.period === 'week'));
      weekButton.classList.toggle('is-active', payload.chartRange.period === 'week');
      updateChartData(payload.movements);
      updateMovementChart(payload.movements);
      statusMessage.textContent = 'Grafik diperbarui.';
      statusTimeout = window.setTimeout(() => {
        statusMessage.hidden = true;
        statusMessage.textContent = '';
        statusTimeout = null;
      }, 1500);
    } catch (error) {
      if (error.name === 'AbortError') {
        return;
      }

      errorMessage.textContent = error.message || 'Grafik gagal diperbarui. Coba lagi.';
      errorMessage.hidden = false;
      statusMessage.hidden = true;
      statusMessage.textContent = '';
    } finally {
      if (activeRequest === request) {
        activeRequest = null;
        setLoading(false);
      }
    }
  };

  const markRangeChanged = () => {
    periodInput.value = 'custom';
    weekButton.setAttribute('aria-pressed', 'false');
    weekButton.classList.remove('is-active');
    errorMessage.hidden = true;
    errorMessage.textContent = '';
    applyFilter();
  };

  [startDate, endDate].forEach((dateInput) => {
    dateInput.addEventListener('click', () => {
      if (typeof dateInput.showPicker === 'function') {
        dateInput.showPicker();
      }
    });
  });

  startDate.addEventListener('change', () => {
    markRangeChanged();
  });

  endDate.addEventListener('change', () => {
    markRangeChanged();
  });

  weekButton.addEventListener('click', () => {
    periodInput.value = 'week';
    applyFilter();
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    periodInput.value = 'custom';
    applyFilter();
  });
}

function destroyAdminGUDashboardCharts() {
  movementChart?.destroy();
  movementChart = null;
  chartInstances.forEach((chart) => chart.destroy());
  chartInstances.length = 0;
}

document.addEventListener('turbo:before-cache', destroyAdminGUDashboardCharts);
