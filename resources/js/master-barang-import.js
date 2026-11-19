const fields = ['kode_barang', 'nama_barang', 'kategori', 'satuan'];
const suggestionLists = {
  kategori: 'mbi-category-suggestions',
  satuan: 'mbi-unit-suggestions',
};
const batchSize = 100;
const reviewPageSize = 20;
const automaticReviewDelay = 500;
const databaseName = 'sigma-master-barang-imports';

function openDatabase() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(databaseName, 1);

    request.onupgradeneeded = () => {
      const database = request.result;
      if (!database.objectStoreNames.contains('runs')) {
        database.createObjectStore('runs', { keyPath: 'token' });
      }
      if (!database.objectStoreNames.contains('rows')) {
        database.createObjectStore('rows', { keyPath: ['token', 'line'] });
      }
    };

    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(new Error(
      'Penyimpanan sementara browser tidak dapat dibuka. Izinkan penyimpanan situs, lalu coba lagi.'
    ));
    request.onblocked = () => reject(new Error(
      'Penyimpanan impor sedang dipakai tab lain. Tutup tab SIGMA lain, lalu coba lagi.'
    ));
  });
}

function transactionRequest(transaction, storeName, operation) {
  return new Promise((resolve, reject) => {
    const request = operation(transaction.objectStore(storeName));
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function saveRun(database, run) {
  const transaction = database.transaction('runs', 'readwrite');
  await transactionRequest(transaction, 'runs', store => store.put(run));
}

async function getRun(database, token) {
  const transaction = database.transaction('runs', 'readonly');
  return transactionRequest(transaction, 'runs', store => store.get(token));
}

async function saveRows(database, rows) {
  if (rows.length === 0) {
    return;
  }

  const transaction = database.transaction('rows', 'readwrite');
  const store = transaction.objectStore('rows');

  rows.forEach(row => store.put(row));

  await new Promise((resolve, reject) => {
    transaction.oncomplete = resolve;
    transaction.onerror = () => reject(transaction.error);
    transaction.onabort = () => reject(transaction.error);
  });
}

function getRows(database, token, offset, limit) {
  return new Promise((resolve, reject) => {
    const transaction = database.transaction('rows', 'readonly');
    const store = transaction.objectStore('rows');
    const range = IDBKeyRange.bound([token, 0], [token, Number.MAX_SAFE_INTEGER]);
    const request = store.openCursor(range);
    const rows = [];
    let seen = 0;

    request.onsuccess = () => {
      const cursor = request.result;
      if (!cursor || rows.length >= limit) {
        resolve(rows);
        return;
      }

      if (seen >= offset) {
        rows.push(cursor.value);
      }

      seen++;
      cursor.continue();
    };
    request.onerror = () => reject(request.error);
  });
}

function getNextRows(database, token, afterLine, limit) {
  return new Promise((resolve, reject) => {
    const transaction = database.transaction('rows', 'readonly');
    const store = transaction.objectStore('rows');
    const lowerBound = afterLine === null ? [token, 0] : [token, afterLine];
    const range = IDBKeyRange.lowerBound(lowerBound, afterLine !== null);
    const request = store.openCursor(range);
    const rows = [];

    request.onsuccess = () => {
      const cursor = request.result;
      if (!cursor || rows.length >= limit || cursor.key[0] !== token) {
        resolve(rows);
        return;
      }

      rows.push(cursor.value);
      cursor.continue();
    };
    request.onerror = () => reject(request.error);
  });
}

function serverErrorMessage(status) {
  if (status === 401) {
    return 'Sesi Anda sudah berakhir. Silakan masuk kembali.';
  }
  if (status === 403) {
    return 'Akun Anda tidak memiliki izin untuk melakukan impor ini.';
  }
  if (status === 413) {
    return 'Ukuran file terlalu besar. Gunakan file Excel yang ukurannya tidak lebih dari 50 MB.';
  }
  if (status === 419) {
    return 'Sesi halaman sudah berakhir. Muat ulang halaman, lalu coba lagi.';
  }
  if (status === 422) {
    return 'Data yang dikirim belum sesuai. Periksa kembali file dan data barang Anda.';
  }
  if (status === 429) {
    return 'Permintaan terlalu sering. Tunggu sebentar, lalu coba lagi.';
  }
  if (status >= 500) {
    return 'Terjadi kendala pada server. Coba lagi beberapa saat, atau hubungi administrator.';
  }

  return 'Permintaan belum dapat diproses. Periksa kembali data Anda, lalu coba lagi.';
}

function readableError(error, fallback) {
  if (error instanceof Error && error.message.trim() !== '') {
    return error.message;
  }

  return fallback;
}

function formatCount(value, label = 'Jumlah data') {
  const count = Number(value);
  if (!Number.isSafeInteger(count) || count < 0) {
    throw new Error(`${label} tidak tersedia. Periksa file kembali sebelum melanjutkan.`);
  }

  return count.toLocaleString('id-ID');
}

async function requestJson(url, rows, warehouseIds, signal) {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    },
    body: JSON.stringify({ warehouse_ids: warehouseIds, rows }),
    signal,
  });
  let payload = {};
  try {
    payload = await response.json();
  } catch {
    if (response.ok) {
      throw new Error('Server mengirim respons yang tidak dapat dibaca. Muat ulang halaman dan coba lagi.');
    }
  }

  if (!response.ok) {
    const validationMessages = Object.values(payload.errors ?? {}).flat();
    throw new Error(
      validationMessages.join(' ')
      || serverErrorMessage(response.status)
    );
  }

  return payload;
}

function showMessage(root, message, success = false) {
  const element = root.querySelector('[data-message]');
  element.textContent = message;
  element.classList.toggle('mbi-alert-success', success);
  element.setAttribute('aria-hidden', 'false');
}

function showFileMessage(root, message, success = false) {
  const alert = root.querySelector('[data-message]');
  alert.textContent = '';
  alert.setAttribute('aria-hidden', 'true');

  const feedback = root.querySelector('[data-file-feedback]');
  feedback.textContent = message;
  feedback.classList.toggle('mbi-alert-success', success);
  feedback.setAttribute('aria-hidden', 'false');
}

function clearMessage(root) {
  const message = root.querySelector('[data-message]');
  message.textContent = '';
  message.setAttribute('aria-hidden', 'true');

  const fileFeedback = root.querySelector('[data-file-feedback]');
  fileFeedback.textContent = '';
  fileFeedback.setAttribute('aria-hidden', 'true');
}

function setBusy(root, section, busy, label = '') {
  const element = root.querySelector(section);
  element.setAttribute('aria-hidden', String(!busy));
  if (label !== '') {
    const labelElement = element.querySelector('[data-progress-label], [data-paste-label], [data-confirm-label]');
    if (labelElement) {
      labelElement.textContent = label;
    }
  }
}

function updateProgress(root, section, current, total) {
  const element = root.querySelector(section);
  const bar = element.querySelector('progress');
  const value = element.querySelector('[data-progress-value], [data-paste-value], [data-confirm-value]');
  bar.value = total > 0 ? Math.round((current / total) * 100) : 0;
  value.textContent = `${formatCount(current, 'Jumlah baris yang diperiksa')} / ${formatCount(total, 'Jumlah seluruh baris')}`;
}

function rowFields(row = {}) {
  return Object.fromEntries(fields.map(field => [field, String(row[field] ?? '')]));
}

function setRowResult(cell, result = null) {
  const status = document.createElement('span');
  status.className = 'mbi-status';
  status.textContent = result?.status_label || 'Belum diperiksa';
  if (typeof result?.status === 'string' && /^[a-z-]+$/.test(result.status)) {
    status.classList.add(`mbi-status-${result.status}`);
  }
  cell.replaceChildren(status);

  const issues = Array.isArray(result?.issues) ? result.issues.join(' ') : '';
  if (issues !== '') {
    const note = document.createElement('p');
    note.className = 'mbi-result-note';
    note.textContent = issues;
    cell.append(note);
  }

  const preparationNotes = Array.isArray(result?.preparation_notes)
    ? result.preparation_notes.join(' ')
    : '';
  if (preparationNotes !== '') {
    const note = document.createElement('p');
    note.className = 'mbi-result-note mbi-preparation-note';
    note.textContent = preparationNotes;
    cell.append(note);
  } else if (!result) {
    const note = document.createElement('p');
    note.className = 'mbi-result-note';
    note.textContent = 'Menunggu data untuk diperiksa.';
    cell.append(note);
  }
}

function makeGridRow(tbody, index, initial = {}, reviewed = false, displayNumber = index + 1) {
  const row = document.createElement('tr');
  row.dataset.line = String(initial.line ?? displayNumber);
  const number = document.createElement('td');
  number.textContent = String(displayNumber);
  row.append(number);

  fields.forEach((field, columnIndex) => {
    const cell = document.createElement('td');
    const input = document.createElement('input');
    input.type = 'text';
    input.autocomplete = 'off';
    input.dataset.gridField = field;
    input.dataset.gridColumn = String(columnIndex);
    input.setAttribute('aria-label', `${field.replaceAll('_', ' ')} baris ${displayNumber}`);
    if (suggestionLists[field]) {
      input.setAttribute('list', suggestionLists[field]);
      input.setAttribute('aria-describedby', 'mbi-reference-hint');
    }
    input.value = initial[field] ?? '';
    input.readOnly = reviewed;
    cell.append(input);
    row.append(cell);
  });

  const resultCell = document.createElement('td');
  resultCell.className = 'mbi-result-cell';
  setRowResult(resultCell, reviewed ? initial : null);
  row.append(resultCell);

  const action = document.createElement('td');
  const remove = document.createElement('button');
  remove.type = 'button';
  remove.className = 'btn btn-outline';
  remove.textContent = 'Hapus';
  remove.setAttribute('aria-label', `Hapus baris ${displayNumber}`);
  remove.disabled = reviewed;
  remove.addEventListener('click', () => {
    row.remove();
    renumberGrid(tbody);
    tbody.dispatchEvent(new Event('input', { bubbles: true }));
  });
  action.append(remove);
  row.append(action);
  tbody.append(row);
}

function renumberGrid(tbody) {
  [...tbody.rows].forEach((row, index) => {
    row.dataset.line = String(index + 1);
    row.cells[0].textContent = String(index + 1);
    row.querySelectorAll('input').forEach(input => {
      input.setAttribute('aria-label', `${input.dataset.gridField.replaceAll('_', ' ')} baris ${index + 1}`);
    });
  });
}

function gridRows(tbody) {
  return [...tbody.rows].map((row, index) => ({
    line: Number(row.dataset.line) || index + 1,
    ...Object.fromEntries([...row.querySelectorAll('input')].map(input => [input.dataset.gridField, input.value.trim()])),
  })).filter(row => fields.some(field => row[field] !== ''));
}

function createGrid(root) {
  const tbody = root.querySelector('[data-grid]');
  if (!tbody) {
    throw new Error('Grid tempel tidak ditemukan. Muat ulang halaman dan coba lagi.');
  }
  const pasteHistory = [];

  function undoPaste() {
    const operation = pasteHistory.pop();
    if (!operation) {
      return;
    }

    operation.changes.forEach(({ input, pastedValue, previousValue }) => {
      if (input.isConnected && input.value === pastedValue) {
        input.value = previousValue;
      }
    });

    operation.addedRows.forEach(row => {
      if (
        row.isConnected
        && [...row.querySelectorAll('input[data-grid-field]')].every(input => input.value === '')
      ) {
        row.remove();
      }
    });

    renumberGrid(tbody);
  }

  function clearPasteHistory() {
    pasteHistory.length = 0;
  }

  tbody.replaceChildren();
  for (let index = 0; index < 20; index++) {
    makeGridRow(tbody, index);
  }
  tbody.addEventListener('keydown', event => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z' && pasteHistory.length > 0) {
      event.preventDefault();
      undoPaste();
      tbody.dispatchEvent(new Event('input', { bubbles: true }));
      return;
    }

    const input = event.target.closest('input[data-grid-field]');
    if (event.key === 'Enter' && input) {
      event.preventDefault();
      const currentRow = [...tbody.rows].indexOf(input.closest('tr'));
      const column = Number(input.dataset.gridColumn);
      if (currentRow < 0 || !Number.isInteger(column)) {
        return;
      }

      if (currentRow + 1 >= tbody.rows.length) {
        makeGridRow(tbody, tbody.rows.length);
      }

      tbody.rows[currentRow + 1].querySelectorAll('input')[column]?.focus();
    }
  });

  tbody.addEventListener('paste', event => {
    let target = event.target.closest('input[data-grid-field]');
    if (!target) {
      const cell = event.target.closest('td');
      if (cell) {
        target = cell.querySelector('input[data-grid-field]');
      }
    }
    if (!target) {
      target = tbody.rows[0]?.querySelector('input[data-grid-field]');
    }
    if (!target) {
      return;
    }

    const clipboard = event.clipboardData?.getData('text/plain');
    if (!clipboard) {
      return;
    }

    // Always intercept paste so we can normalise tabs/newlines from Excel.
    // If there are no tabs or newlines, treat it as a single-cell paste.
    event.preventDefault();
    const lines = clipboard.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n');
    if (lines.at(-1) === '') {
      lines.pop();
    }
    if (lines.length === 0) {
      return;
    }

    const startRow = [...tbody.rows].indexOf(target.closest('tr'));
    const startColumn = Number(target.dataset.gridColumn);
    const requiredRows = startRow + lines.length;
    const addedRows = [];
    while (tbody.rows.length < requiredRows) {
      makeGridRow(tbody, tbody.rows.length);
      addedRows.push(tbody.rows[tbody.rows.length - 1]);
    }

    const changes = [];
    lines.forEach((line, rowOffset) => {
      line.split('\t').forEach((value, columnOffset) => {
        const input = tbody.rows[startRow + rowOffset]?.querySelectorAll('input')[startColumn + columnOffset];
        if (input) {
          let cleanVal = value.trim();
          if (cleanVal.startsWith('"') && cleanVal.endsWith('"') && cleanVal.length >= 2) {
            cleanVal = cleanVal.slice(1, -1).replaceAll('""', '"');
          }
          changes.push({
            input,
            previousValue: input.value,
            pastedValue: cleanVal,
          });
          input.value = cleanVal;
        }
      });
    });

    if (changes.length > 0) {
      pasteHistory.push({ changes, addedRows });
      tbody.dispatchEvent(new Event('input', { bubbles: true }));
    }
  });

  return { clearPasteHistory };
}

function selectedWarehouses(root) {
  const options = [...root.querySelectorAll('[data-warehouse-option]')];

  if (options.length > 0) {
    const selected = options.filter(option => option.checked);
    return {
      ids: selected.map(option => Number(option.value)),
      names: selected.map(option => option.dataset.warehouseName),
    };
  }

  return {
    ids: JSON.parse(root.dataset.fixedWarehouseIds || '[]').map(Number),
    names: JSON.parse(root.dataset.fixedWarehouseNames || '[]'),
  };
}

function createRunToken() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }

  if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hexadecimal = [...bytes].map(byte => byte.toString(16).padStart(2, '0')).join('');

    return [
      hexadecimal.slice(0, 8),
      hexadecimal.slice(8, 12),
      hexadecimal.slice(12, 16),
      hexadecimal.slice(16, 20),
      hexadecimal.slice(20),
    ].join('-');
  }

  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}-${Math.random().toString(36).slice(2)}`;
}

function createAnalysisRun(root, total, destinations, source = 'grid') {
  const token = createRunToken();
  return {
    token,
    source,
    userId: Number(root.dataset.userId),
    warehouseIds: destinations.ids,
    warehouseNames: destinations.names,
    total,
    createdAt: Date.now(),
    summary: {},
  };
}

function createAnalysisTotals(total) {
  return {
    total,
    new: 0,
    reused: 0,
    imported: 0,
    registered: 0,
    duplicate: 0,
    conflict: 0,
    invalid: 0,
    ignored: 0,
  };
}

async function analyzeBatch(root, database, run, batch, seenCodes, totals, processed, onStage = () => {}, signal) {
  const firstLine = batch[0]?.line;
  const lastLine = batch.at(-1)?.line;
  const lineRange = firstLine === lastLine
    ? `baris ${firstLine}`
    : `baris ${firstLine} sampai ${lastLine}`;
  onStage(`mengirim pemeriksaan ${lineRange} ke server`);
  const response = await requestJson(root.dataset.analyzeUrl, batch, run.warehouseIds, signal);
  if (!Array.isArray(response.rows) || response.rows.length !== batch.length) {
    throw new Error(`Hasil pemeriksaan ${lineRange} dari server tidak lengkap.`);
  }

  const reviewedRows = response.rows.map(row => {
    const code = row.kode_barang.trim().toUpperCase();
    if (code !== '' && seenCodes.has(code)) {
      row.status = 'duplicate';
      row.status_label = 'Duplikat dalam file';
      row.issues = [`Kode ini sudah muncul di baris ${seenCodes.get(code)}.`];
    } else if (code !== '') {
      seenCodes.set(code, row.line);
    }

    totals[row.status] = (totals[row.status] ?? 0) + 1;
    return { token: run.token, ...row };
  });

  onStage(`menyimpan hasil pemeriksaan ${lineRange} di browser`);
  await saveRows(database, reviewedRows);
  const current = processed + batch.length;
  updateProgress(root, '[data-paste-busy]', current, run.total);
  updateProgress(root, '[data-busy]', current, run.total);
  return current;
}

async function finalizeAnalysis(database, run, totals) {
  run.summary = totals;
  await saveRun(database, run);
  return run.token;
}

async function analyzeRows(root, database, sourceRows, source = 'grid', signal) {
  const destinations = selectedWarehouses(root);
  if (destinations.ids.length === 0) {
    throw new Error('Pilih setidaknya satu gudang tujuan sebelum meninjau data.');
  }

  const run = createAnalysisRun(root, sourceRows.length, destinations, source);
  await saveRun(database, run);
  const seenCodes = new Map();
  const totals = createAnalysisTotals(sourceRows.length);
  let processed = 0;

  setBusy(root, '[data-paste-busy]', true, 'Memeriksa data per kelompok…');
  setBusy(root, '[data-busy]', true, 'Memeriksa data per kelompok…');

  for (let offset = 0; offset < sourceRows.length; offset += batchSize) {
    const batch = sourceRows.slice(offset, offset + batchSize).map(row => ({
      ...rowFields(row),
      line: Number(row.line),
    }));
    processed = await analyzeBatch(root, database, run, batch, seenCodes, totals, processed, () => {}, signal);
  }

  return finalizeAnalysis(database, run, totals);
}

async function analyzeExcelStream(root, database, response, onStage) {
  const total = Number(response.headers.get('X-Import-Total'));
  if (!Number.isSafeInteger(total) || total < 1 || !response.body) {
    throw new Error('File Excel tidak menghasilkan data barang yang dapat ditinjau.');
  }

  const destinations = selectedWarehouses(root);
  if (destinations.ids.length === 0) {
    throw new Error('Pilih setidaknya satu gudang tujuan sebelum meninjau data.');
  }

  const run = createAnalysisRun(root, total, destinations, 'excel');
  onStage('menyiapkan tinjauan sementara di browser');
  await saveRun(database, run);
  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  const seenCodes = new Map();
  const totals = createAnalysisTotals(total);
  let pendingRows = [];
  let buffer = '';
  let processed = 0;

  setBusy(root, '[data-busy]', true, 'Memeriksa data dari Excel per kelompok…');

  async function processLines(text) {
    buffer += text;
    const lines = buffer.split(/\r?\n/);
    buffer = lines.pop();

    for (const line of lines) {
      if (line.trim() === '') {
        continue;
      }

      pendingRows.push(JSON.parse(line));
      if (pendingRows.length === batchSize) {
        processed = await analyzeBatch(root, database, run, pendingRows, seenCodes, totals, processed, onStage);
        pendingRows = [];
      }
    }
  }

  while (true) {
    onStage('membaca data barang dari file Excel');
    const { value, done } = await reader.read();
    if (done) {
      break;
    }

    await processLines(decoder.decode(value, { stream: true }));
  }

  onStage('membaca data barang dari file Excel');
  await processLines(decoder.decode());
  if (buffer.trim() !== '') {
    pendingRows.push(JSON.parse(buffer));
  }
  if (pendingRows.length > 0) {
    processed = await analyzeBatch(root, database, run, pendingRows, seenCodes, totals, processed, onStage);
  }
  if (processed !== total) {
    throw new Error('Jumlah baris Excel yang diterima tidak sesuai. Unggah ulang file dan coba kembali.');
  }

  onStage('menyelesaikan tinjauan di browser');
  return finalizeAnalysis(database, run, totals);
}

function initializeInput(root) {
  const tbody = root.querySelector('[data-grid]');
  const parseButton = root.querySelector('[data-parse-excel]');
  const clearGridButton = root.querySelector('[data-clear-grid]');
  const reviewResults = root.querySelector('[data-review-results]');
  const confirmButton = root.querySelector('[data-confirm-import]');
  let database = null;
  let activeRun = null;
  let currentPage = 0;
  let reviewTimer = null;
  let reviewController = null;
  let reviewSequence = 0;

  const grid = createGrid(root);

  async function displayRun(token) {
    grid.clearPasteHistory();
    database = await openDatabase();
    activeRun = await getRun(database, token);
    if (
      !activeRun
      || activeRun.userId !== Number(root.dataset.userId)
      || !Array.isArray(activeRun.warehouseIds)
      || activeRun.warehouseIds.length === 0
    ) {
      throw new Error('Hasil pemeriksaan tidak ditemukan di penyimpanan browser.');
    }
    activeRun.total = Number(activeRun.total);
    if (!Number.isSafeInteger(activeRun.total) || activeRun.total < 1) {
      throw new Error('Jumlah baris hasil pemeriksaan tidak ditemukan. Periksa file kembali.');
    }

    currentPage = 0;
    reviewResults.hidden = false;
    root.querySelector('[data-review-pagination]').hidden = false;
    confirmButton.disabled = false;
    await renderInputRun(root, database, activeRun, currentPage);
  }

  function clearRun() {
    clearTimeout(reviewTimer);
    reviewController?.abort();
    reviewController = null;
    reviewSequence++;
    grid.clearPasteHistory();
    activeRun = null;
    currentPage = 0;
    reviewResults.hidden = true;
    confirmButton.disabled = true;
    tbody.replaceChildren();
    for (let index = 0; index < 20; index++) {
      makeGridRow(tbody, index);
    }
    clearGridButton.textContent = 'Kosongkan grid';
  }

  async function displayGridRun(token, sequence) {
    const run = await getRun(database, token);
    if (sequence !== reviewSequence) {
      return;
    }
    if (!run) {
      throw new Error('Hasil pemeriksaan otomatis tidak ditemukan di penyimpanan browser.');
    }
    const rows = await getRows(database, token, 0, run.total);
    if (sequence !== reviewSequence) {
      return;
    }

    activeRun = run;
    reviewResults.hidden = false;
    root.querySelector('[data-review-pagination]').hidden = true;
    const resultsByLine = new Map(rows.map(row => [Number(row.line), row]));
    [...tbody.rows].forEach(row => {
      const reviewedRow = resultsByLine.get(Number(row.dataset.line));
      setRowResult(
        row.querySelector('.mbi-result-cell'),
        reviewedRow ?? null,
      );
      const unitInput = row.querySelector('input[data-grid-field="satuan"]');
      if (unitInput && reviewedRow?.satuan) {
        unitInput.value = reviewedRow.satuan;
      }
    });

    const validRows = Number(run.summary.new ?? 0) + Number(run.summary.reused ?? 0);
    const blockers = getReviewBlockers(run.summary ?? {});
    const canSaveAllRows = validRows > 0 && blockers.length === 0;
    confirmButton.disabled = !canSaveAllRows;
    confirmButton.textContent = 'Simpan';
    root.querySelector('[data-import-feedback]').textContent = canSaveAllRows
      ? 'Semua baris terisi lolos pemeriksaan. Simpan untuk mendaftarkan semuanya ke database.'
      : blockers.length > 0
        ? `Belum disimpan. Perbaiki atau hapus baris bermasalah terlebih dahulu: ${blockers.join(', ')}.`
        : 'Belum ada baris barang yang dapat disimpan.';
    renderSummary(root, run.summary ?? {});
  }

  function scheduleGridReview() {
    clearTimeout(reviewTimer);
    reviewController?.abort();
    reviewController = null;
    reviewSequence++;
    const sequence = reviewSequence;
    activeRun = null;
    confirmButton.disabled = true;

    const rows = gridRows(tbody);
    [...tbody.rows].forEach(row => {
      const hasValues = [...row.querySelectorAll('input[data-grid-field]')]
        .some(input => input.value.trim() !== '');
      setRowResult(row.querySelector('.mbi-result-cell'), hasValues
        ? { status_label: 'Menunggu pemeriksaan' }
        : null);
    });

    if (rows.length === 0) {
      reviewResults.hidden = true;
      root.querySelector('[data-review-pagination]').hidden = true;
      setBusy(root, '[data-paste-busy]', false);
      setBusy(root, '[data-busy]', false);
      clearMessage(root);
      return;
    }

    reviewResults.hidden = false;
    root.querySelector('[data-review-pagination]').hidden = true;
    root.querySelector('[data-summary]').replaceChildren();
    root.querySelector('[data-import-feedback]').textContent = 'Data berubah. Pemeriksaan otomatis akan segera diperbarui.';
    setBusy(root, '[data-paste-busy]', false);
    setBusy(root, '[data-busy]', false);
    clearMessage(root);

    reviewTimer = window.setTimeout(async () => {
      const controller = new AbortController();
      reviewController = controller;
      try {
        [...tbody.rows].forEach(row => {
          if ([...row.querySelectorAll('input[data-grid-field]')].some(input => input.value.trim() !== '')) {
            setRowResult(row.querySelector('.mbi-result-cell'), {
              status_label: 'Sedang diperiksa',
              issues: ['Mengirim data untuk diperiksa.'],
            });
          }
        });
        database = await openDatabase();
        const token = await analyzeRows(root, database, rows, 'grid', controller.signal);
        if (sequence === reviewSequence && !controller.signal.aborted) {
          await displayGridRun(token, sequence);
        }
      } catch (error) {
        if (sequence !== reviewSequence || controller.signal.aborted) {
          return;
        }
        const message = readableError(error, 'Pemeriksaan otomatis gagal. Periksa koneksi Anda, lalu coba lagi.');
        [...tbody.rows].forEach(row => {
          if ([...row.querySelectorAll('input[data-grid-field]')].some(input => input.value.trim() !== '')) {
            setRowResult(row.querySelector('.mbi-result-cell'), {
              status: 'invalid',
              status_label: 'Pemeriksaan gagal',
              issues: [message],
            });
          }
        });
        root.querySelector('[data-import-feedback]').textContent = message;
        showMessage(root, message);
      } finally {
        if (sequence === reviewSequence) {
          setBusy(root, '[data-paste-busy]', false);
          setBusy(root, '[data-busy]', false);
          reviewController = null;
        }
      }
    }, automaticReviewDelay);
  }

  tbody.addEventListener('input', scheduleGridReview);
  root.addEventListener('change', event => {
    if (event.target.matches('[data-warehouse-option]')) {
      if (activeRun?.source === 'excel') {
        parseButton.click();
      } else {
        scheduleGridReview();
      }
    }
  });

  root.querySelector('#master-barang-excel').addEventListener('change', event => {
    clearRun();
    if (event.currentTarget.files[0]) {
      parseButton.click();
    }
  });

  clearGridButton.addEventListener('click', () => {
    clearRun();
  });

  root.querySelector('[data-previous-page]').addEventListener('click', async () => {
    if (!activeRun || currentPage === 0) {
      return;
    }
    currentPage--;
    await renderInputRun(root, database, activeRun, currentPage);
  });
  root.querySelector('[data-next-page]').addEventListener('click', async () => {
    if (!activeRun || (currentPage + 1) * reviewPageSize >= activeRun.total) {
      return;
    }
    currentPage++;
    await renderInputRun(root, database, activeRun, currentPage);
  });

  parseButton.addEventListener('click', async event => {
    const file = root.querySelector('#master-barang-excel').files[0];
    if (!file) {
      showFileMessage(root, 'Pilih file .xlsx terlebih dahulu. Data yang diketik atau ditempel di grid akan diperiksa otomatis.');
      return;
    }

    clearRun();

    const button = event.currentTarget;
    const warehouseOptions = [...root.querySelectorAll('[data-warehouse-option]')];
    const previousDisabledStates = warehouseOptions.map(option => option.disabled);
    warehouseOptions.forEach(option => {
      option.disabled = true;
    });
    button.disabled = true;
    clearMessage(root);
    setBusy(root, '[data-busy]', true, 'Membaca sheet Master Barang…');
    let stage = 'menyiapkan penyimpanan sementara di browser';

    try {
      const destinations = selectedWarehouses(root);
      if (destinations.ids.length === 0) {
        throw new Error('Pilih setidaknya satu gudang tujuan sebelum memeriksa file.');
      }

      const database = await openDatabase();
      const form = new FormData();
      form.append('file', file);
      destinations.ids.forEach(id => form.append('warehouse_ids[]', String(id)));
      stage = 'mengunggah dan membaca file Excel di server';
      const response = await fetch(root.dataset.parseUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: form,
      });
      if (!response.ok) {
        let payload = {};
        try {
          payload = await response.json();
        } catch {
          throw new Error(serverErrorMessage(response.status));
        }
        const messages = Object.values(payload.errors ?? {}).flat();
        throw new Error(
          messages.join(' ')
          || serverErrorMessage(response.status)
        );
      }

      const token = await analyzeExcelStream(root, database, response, currentStage => {
        stage = currentStage;
      });
      await displayRun(token);
      setBusy(root, '[data-busy]', false);
      showFileMessage(root, 'Pemeriksaan selesai. Hasilnya sudah ditampilkan pada grid Tempel dari Excel.', true);
    } catch (error) {
      console.error(`Pemeriksaan impor Master Barang gagal saat ${stage}.`, error);
      showFileMessage(root, readableError(
        error,
        `Pemeriksaan berhenti saat ${stage}. Belum ada barang yang diimpor. Sampaikan tahap ini kepada administrator.`,
      ));
      setBusy(root, '[data-busy]', false);
    } finally {
      button.disabled = false;
      warehouseOptions.forEach((option, index) => {
        option.disabled = previousDisabledStates[index];
      });
    }
  });

  confirmButton.addEventListener('click', async () => {
    if (!activeRun || !database) {
      return;
    }

    const validRows = Number(activeRun.summary.new ?? 0) + Number(activeRun.summary.reused ?? 0);
    const blockers = getReviewBlockers(activeRun.summary ?? {});
    if (validRows < 1 || blockers.length > 0) {
      showFileMessage(
        root,
        blockers.length > 0
          ? `Belum ada data yang disimpan. Perbaiki atau hapus baris bermasalah terlebih dahulu: ${blockers.join(', ')}.`
          : 'Belum ada baris barang yang lolos pemeriksaan untuk diimpor.',
      );
      return;
    }
    const destinations = (activeRun.warehouseNames ?? []).join(', ');
    if (!window.confirm(`Semua ${formatCount(validRows, 'Jumlah baris')} baris sudah lolos pemeriksaan. Simpan semuanya ke ${destinations || 'gudang tujuan'}?`)) {
      return;
    }

    confirmButton.disabled = true;
    const progressWrap = root.querySelector('[data-confirm-progress-wrap]');
    progressWrap.setAttribute('aria-hidden', 'false');
    const editableGridInputs = activeRun.source === 'grid'
      ? [...tbody.querySelectorAll('input[data-grid-field]')]
      : [];
    editableGridInputs.forEach(input => {
      input.readOnly = true;
    });
    let afterLine = null;

    try {
      const allRows = [];
      while (true) {
        const pageRows = await getNextRows(database, activeRun.token, afterLine, batchSize);
        if (pageRows.length === 0) {
          break;
        }

        afterLine = pageRows.at(-1).line;
        if (pageRows.some(row => !['new', 'reused'].includes(row.status))) {
          throw new Error('Ada baris yang belum lolos pemeriksaan. Tidak ada data yang disimpan.');
        }
        allRows.push(...pageRows);
        root.querySelector('[data-confirm-label]').textContent = 'Menyiapkan seluruh data untuk disimpan sekaligus…';
        root.querySelector('[data-confirm-value]').textContent = `${formatCount(allRows.length, 'Jumlah baris yang disiapkan')} / ${formatCount(activeRun.total, 'Jumlah seluruh baris')}`;
        root.querySelector('[data-confirm-progress]').value = Math.round((allRows.length / activeRun.total) * 50);
      }

      if (allRows.length !== activeRun.total) {
        throw new Error('Data tinjauan tidak lengkap. Tidak ada data yang disimpan; periksa ulang file.');
      }

      root.querySelector('[data-confirm-label]').textContent = 'Memeriksa ulang dan menyimpan semua data dalam satu transaksi…';
      const response = await requestJson(root.dataset.confirmUrl, allRows, activeRun.warehouseIds);
      if (
        !Array.isArray(response.rows)
        || response.rows.length !== allRows.length
        || !Number.isSafeInteger(response.success_count)
        || !Number.isSafeInteger(response.skipped_count)
        || !Number.isSafeInteger(response.registered_warehouse_count)
        || response.success_count !== allRows.length
        || response.skipped_count !== 0
      ) {
        throw new Error('Server belum menyimpan seluruh data. Tidak ada impor sebagian; periksa catatan pemeriksaan lalu coba lagi.');
      }

      const updatedRows = response.rows.map(row => ({ token: activeRun.token, ...row }));
      await saveRows(database, updatedRows);
      const imported = response.success_count;
      const registeredWarehouseCount = response.registered_warehouse_count;
      allRows.forEach(row => {
        activeRun.summary[row.status] = Math.max(0, (activeRun.summary[row.status] ?? 0) - 1);
      });
      updatedRows.forEach(row => {
        activeRun.summary[row.status] = (activeRun.summary[row.status] ?? 0) + 1;
      });
      activeRun.completedAt = Date.now();
      activeRun.imported = imported;
      activeRun.skipped = 0;
      activeRun.registeredWarehouseCount = registeredWarehouseCount;
      await saveRun(database, activeRun);
      const feedback = root.querySelector('[data-import-feedback]');
      feedback.textContent = `Selesai. Semua ${formatCount(imported, 'Jumlah barang berhasil diimpor')} barang berhasil disimpan dan didaftarkan pada ${formatCount(registeredWarehouseCount, 'Jumlah lokasi terdaftar')} lokasi.`;
      confirmButton.textContent = 'Simpan selesai';
      root.querySelector('[data-confirm-progress]').value = 100;
      await renderInputRun(root, database, activeRun, currentPage);
      showFileMessage(root, feedback.textContent, true);
    } catch (error) {
      showFileMessage(root, readableError(error, 'Impor belum berhasil. Tidak ada data yang disimpan; periksa kembali seluruh data.'));
      confirmButton.disabled = false;
      editableGridInputs.forEach(input => {
        input.readOnly = false;
      });
      root.querySelector('[data-import-feedback]').textContent = 'Belum ada data yang disimpan.';
    } finally {
      progressWrap.setAttribute('aria-hidden', 'true');
    }
  });
}

function renderSummary(root, summary) {
  const labels = [
    ['total', 'Total baris'],
    ['new', 'Master baru'],
    ['reused', 'Master digunakan'],
    ['imported', 'Berhasil diimpor'],
    ['registered', 'Sudah terdaftar'],
    ['duplicate', 'Duplikat'],
    ['conflict', 'Konflik'],
    ['invalid', 'Perlu dilengkapi'],
    ['ignored', 'Baris kosong'],
  ];
  root.querySelector('[data-summary]').innerHTML = labels.map(([key, label]) => `
    <div class="mbi-stat"><span>${label}</span><strong>${formatCount(summary[key] ?? 0, label)}</strong></div>
  `).join('');
}

function getReviewBlockers(summary) {
  const accepted = Number(summary.new ?? 0) + Number(summary.reused ?? 0);
  const total = Number(summary.total ?? 0);
  const counted = [
    'new',
    'reused',
    'imported',
    'registered',
    'duplicate',
    'conflict',
    'invalid',
    'ignored',
  ].reduce((sum, key) => sum + Number(summary[key] ?? 0), 0);

  if (!Number.isSafeInteger(total) || total < 1 || counted !== total || accepted > total) {
    return ['hasil pemeriksaan tidak lengkap'];
  }

  return [
    ['imported', 'sudah diimpor'],
    ['registered', 'sudah terdaftar'],
    ['duplicate', 'duplikat'],
    ['conflict', 'konflik'],
    ['invalid', 'perlu diperbaiki'],
    ['ignored', 'baris kosong'],
  ]
    .filter(([key]) => Number(summary[key] ?? 0) > 0)
    .map(([key, label]) => `${formatCount(summary[key], `Jumlah ${label}`)} ${label}`);
}

async function renderInputRun(root, database, run, currentPage) {
  const rows = await getRows(database, run.token, currentPage * reviewPageSize, reviewPageSize);
  const tbody = root.querySelector('[data-grid]');
  tbody.replaceChildren();
  rows.forEach((row, index) => makeGridRow(
    tbody,
    index,
    row,
    true,
    currentPage * reviewPageSize + index + 1,
  ));

  const validRows = Number(run.summary.new ?? 0) + Number(run.summary.reused ?? 0);
  const blockers = getReviewBlockers(run.summary ?? {});
  const canImportAllRows = validRows > 0 && blockers.length === 0;
  const confirmButton = root.querySelector('[data-confirm-import]');
  confirmButton.disabled = !canImportAllRows || Boolean(run.completedAt);
  confirmButton.textContent = run.completedAt ? 'Simpan selesai' : 'Simpan';
  root.querySelector('[data-import-feedback]').textContent = run.completedAt
    ? 'Impor sudah selesai. Status setiap baris terlihat pada kolom Status / catatan pemeriksaan.'
    : canImportAllRows
      ? 'Semua baris lolos pemeriksaan. Simpan untuk menyimpan seluruhnya ke database.'
      : blockers.length > 0
        ? `Belum disimpan. Perbaiki atau hapus baris bermasalah terlebih dahulu: ${blockers.join(', ')}.`
        : 'Belum ada baris barang yang dapat diimpor.';

  renderSummary(root, run.summary ?? {});
  const pageCount = Math.max(1, Math.ceil(run.total / reviewPageSize));
  root.querySelector('[data-page-label]').textContent = `Halaman ${currentPage + 1} dari ${pageCount} · ${formatCount(run.total, 'Jumlah seluruh baris')} baris`;
  root.querySelector('[data-previous-page]').disabled = currentPage === 0;
  root.querySelector('[data-next-page]').disabled = currentPage + 1 >= pageCount;
}

export function initMasterBarangImport() {
  const inputRoot = document.querySelector('#master-barang-import[data-import-mode="input"]');

  if (inputRoot && inputRoot.dataset.initialized !== 'true') {
    inputRoot.dataset.initialized = 'true';
    try {
      initializeInput(inputRoot);
    } catch (error) {
      showMessage(inputRoot, readableError(error, 'Fitur impor belum siap. Muat ulang halaman, lalu coba lagi.'));
    }
  }
}

document.addEventListener('turbo:load', initMasterBarangImport);
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMasterBarangImport);
} else {
  initMasterBarangImport();
}
