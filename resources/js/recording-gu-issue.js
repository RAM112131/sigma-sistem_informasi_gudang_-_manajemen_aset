function newRow() {
  return {
    uid: crypto.randomUUID(),
    query: '',
    item: null,
    qty: '',
    note: '',
    results: [],
    searching: false,
  };
}

function localDate() {
  const date = new Date();
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
  return date.toISOString().slice(0, 10);
}

function localTime() {
  const date = new Date();
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
  return date.toISOString().slice(11, 16);
}

function localDateTime() {
  const date = new Date();
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
  return date.toISOString().slice(0, 16);
}

function dateOffset(days) {
  const date = new Date(`${localDate()}T12:00:00`);
  date.setDate(date.getDate() + days);
  return date.toISOString().slice(0, 10);
}

export function createRecordingGuIssue(
  draftScope = 'preview',
  operatorName = 'Recording GU',
  unitPrefix = 'SHT',
  dynamicItems = null,
  savedTransactions = [],
  storeUrl = '',
  knownRequesters = [],
  requesterStoreUrl = '',
  initialDepartmentReturnOptions = [],
  initialReturnRecords = [],
  borrowedItemsUrl = '',
  returnOptionsUrl = '',
  returnStoreUrl = '',
) {
  const resolvedItems = Array.isArray(dynamicItems)
    ? dynamicItems.map((item) => ({ ...item }))
    : [];

  return {
    draftScope,
    operatorName,
    unitPrefix,
    storeUrl,
    requesterStoreUrl,
    borrowedItemsUrl,
    returnOptionsUrl,
    returnStoreUrl,
    today: localDate(),
    activeView: 'input',
    notice: '',
    noticeType: 'info',
    date: localDate(),
    occurredTime: localTime(),
    entryMode: 'live',
    requesterQuery: '',
    requester: '',
    department: '',
    requesterResults: [],
    note: '',
    rows: [newRow()],
    saving: false,
    savingRequester: false,
    savingReturn: false,
    loadingBorrowedItems: false,
    loadingReturnOptions: false,
    search: '',
    historySubView: 'transactions', // 'transactions' | 'returns'
    historyStatus: '',
    historyDate: '',
    historyDepartment: '',
    exceptionsOnly: false,
    reviewDate: localDate(),
    reconciliationTransaction: null,
    documentReceivedConfirmed: false,
    reconciliationClerk: '',
    reconciliationNote: '',
    selectedReturnDepartment: '',
    returnDate: localDate(),
    serverDepartmentReturnOptions: Array.isArray(initialDepartmentReturnOptions) ? initialDepartmentReturnOptions : [],
    serverBorrowedItems: null,
    returnInputs: {},
    returnRecords: Array.isArray(initialReturnRecords) ? initialReturnRecords : [],
    expandedTransactionId: null,
    lastSavedTransactionId: null,
    savedFlashMessage: '',
    savedFlashTimeout: null,
    bpbPreview: null,
    items: resolvedItems,
    requesters: (function () {
      // Seed dari riwayat DB — sudah terurut dari backend.
      // Draft lokal bisa menambahkan entri baru via addRequester().
      const base = Array.isArray(knownRequesters) && knownRequesters.length > 0
        ? [...knownRequesters]
        : [];
      // Pastikan tidak duplikat (case-insensitive)
      return [...new Map(base.map((r) => [r.trim().toLocaleLowerCase('id'), r.trim()])).values()];
    })(),
    transactions: Array.isArray(savedTransactions)
      ? savedTransactions.map((transaction) => ({ ...transaction, items: transaction.items.map((item) => ({ ...item })) }))
      : [],
    draftSavedAt: '',
    finalizeConfirmed: false,
    showVerifyModal: false,
    verifyingTransaction: null,
    verifyBonKortas: '',
    verifyPhysicalDocMatch: true,
    verifyNotes: '',
    verifyItems: [],
    verifyRejectMode: false,
    verifyRejectReason: '',

    get filteredReturnRecords() {
      return this.returnRecords.filter((record) => {
        if (this.historyDepartment && record.department !== this.historyDepartment) {
          return false;
        }
        if (this.historyDate && record.returnedAt.slice(0, 10) !== this.historyDate) {
          return false;
        }
        if (this.search) {
          const q = this.search.toLowerCase();
          const match = (record.department || '').toLowerCase().includes(q) ||
                        (record.itemName || '').toLowerCase().includes(q) ||
                        (record.note || '').toLowerCase().includes(q);
          if (!match) return false;
        }
        return true;
      });
    },

    get existingTodayTransactionForRequester() {
      const dept = (this.department || this.requester || '').trim().toLocaleLowerCase('id');
      if (!dept) return null;
      return this.transactions.find((tx) =>
        tx.date === localDate() &&
        ((tx.department || '').trim().toLocaleLowerCase('id') === dept ||
         (tx.requester || '').trim().toLocaleLowerCase('id') === dept)
      ) || null;
    },
    get todayTransactions() {
      return this.transactions.filter((transaction) => transaction.date === localDate());
    },
    get pendingTransactions() {
      return this.transactions
        .filter((transaction) => transaction.status === 'Menunggu Rekonsiliasi')
        .sort((first, second) => first.date.localeCompare(second.date) || first.occurredTime.localeCompare(second.occurredTime));
    },
    get latePendingCount() {
      return this.pendingTransactions.filter((transaction) => transaction.date < localDate()).length;
    },
    get reviewTransactions() {
      return this.transactions.filter((transaction) => transaction.date === this.reviewDate);
    },
    get reviewPendingCount() {
      return this.reviewTransactions.filter((transaction) => transaction.status === 'Menunggu Rekonsiliasi').length;
    },
    get reviewFinalized() {
      return this.reviewTransactions.length > 0 && this.reviewTransactions.every((transaction) => transaction.status === 'Final');
    },
    get historyDepartments() {
      return [...new Set(this.transactions.map((transaction) => transaction.department || transaction.requester).filter(Boolean))]
        .sort((first, second) => first.localeCompare(second, 'id'));
    },
    getReturnDepartment(record) {
      const transaction = this.transactions.find((item) => item.id === record.transactionId);
      return transaction?.department || transaction?.requester || '—';
    },
    get searchedPendingTransactions() {
      const query = this.search.trim().toLocaleLowerCase('id');
      return this.pendingTransactions.filter((transaction) => !query || [
        transaction.bpb,
        transaction.requester,
        ...transaction.items.map((item) => item.name),
      ].some((value) => String(value || '').toLocaleLowerCase('id').includes(query)));
    },
    get filteredTransactions() {
      const query = this.search.trim().toLocaleLowerCase('id');
      return this.transactions.filter((transaction) => {
        const matchesQuery = !query || [
          transaction.bpb,
          transaction.requester,
          ...transaction.items.map((item) => item.name),
        ].some((value) => String(value || '').toLocaleLowerCase('id').includes(query));
        const matchesStatus = !this.historyStatus || transaction.status === this.historyStatus;
        const matchesDate = !this.historyDate || transaction.date === this.historyDate;
        const matchesDepartment = !this.historyDepartment || (transaction.department || transaction.requester) === this.historyDepartment;
        const matchesExceptions = !this.exceptionsOnly || transaction.exceptions.length > 0;
        return matchesQuery && matchesStatus && matchesDate && matchesDepartment && matchesExceptions;
      });
    },
    get isBackdate() {
      return this.date < localDate();
    },
    get existingTransactionForSelectedDateAndRequester() {
      const dept = (this.department || this.requester || '').trim().toLocaleLowerCase('id');
      if (!dept || !this.date) return null;
      return this.transactions.find((tx) =>
        tx.date === this.date &&
        ((tx.department || '').trim().toLocaleLowerCase('id') === dept ||
         (tx.requester || '').trim().toLocaleLowerCase('id') === dept)
      ) || null;
    },
    get existingTodayTransactionForRequester() {
      return this.existingTransactionForSelectedDateAndRequester;
    },
    get issueLineCount() {
      return this.rows.filter((row) => row.item && Number(row.qty) > 0).length;
    },
    get invalidRows() {
      return this.rows.some((row) => !row.item || !Number.isFinite(Number(row.qty)) || !(Number(row.qty) > 0));
    },
    syncRealTime() {
      if (this.entryMode === 'live') {
        this.date = localDate();
        this.occurredTime = localTime();
      }
    },
    onDateChange() {
      if (this.date === localDate()) {
        this.entryMode = 'live';
      } else {
        this.entryMode = 'late-paper';
      }
    },
    setToday() {
      this.date = localDate();
      this.occurredTime = localTime();
      this.entryMode = 'live';
    },
    init() {
      this.syncRealTime();
      if (!window.__guIssueClockInterval) {
        window.__guIssueClockInterval = setInterval(() => {
          if (this.activeView === 'input' && this.entryMode === 'live') {
            this.syncRealTime();
          }
        }, 1000);
      }
      this.$watch('rows', () => this.saveDraftLocally(), { deep: true });
      ['requesterQuery', 'requester', 'department', 'note', 'date', 'occurredTime', 'entryMode']
        .forEach((key) => this.$watch(key, () => this.saveDraftLocally()));
      this.$watch('selectedReturnDepartment', () => this.onReturnDepartmentChange());
      this.restoreDraft();
      if (this.entryMode === 'live') {
        this.syncRealTime();
      }
    },
    setView(view) {
      this.activeView = view;
      this.notice = '';
      if (view === 'input' && this.entryMode === 'live') {
        this.syncRealTime();
      }
      if (view === 'return') {
        this.fetchReturnOptions();
      }
    },
    setNotice(message, type = 'info') {
      this.notice = message;
      this.noticeType = type;
    },
    startLateEntry(selectedDate = null) {
      this.entryMode = 'late-paper';
      this.date = selectedDate || this.reviewDate || dateOffset(-1);
      this.occurredTime = '12:00';
      this.resetForm(false);
      this.setView('input');
      this.setNotice(`Mencatat susulan bon kertas untuk tanggal ${this.formatDateIndo(this.date)}. Tanggal ini akan dicatat ke dalam rekapitulasi harian tanggal tersebut.`, 'info');
    },
    searchItems(index) {
      const row = this.rows[index];
      const query = row.query.trim().toLocaleLowerCase('id');
      row.searching = true;
      row.results = query
        ? this.items.filter((item) => `${item.name} ${item.code} ${item.category}`.toLocaleLowerCase('id').includes(query))
        : this.items.slice(0, 5);
    },
    searchRequesters() {
      const query = this.requesterQuery.trim().toLocaleLowerCase('id');
      this.requesterResults = this.requesters.filter((department) => department.toLocaleLowerCase('id').includes(query));
    },
    chooseRequester(department) {
      this.requester = department;
      this.requesterQuery = department;
      this.department = department;
      this.requesterResults = [];
    },
    async addRequester() {
      const department = this.requesterQuery.trim();
      if (!department) return;
      const existingDepartment = this.requesters.find((option) => option.toLocaleLowerCase('id') === department.toLocaleLowerCase('id'));
      if (existingDepartment) {
        this.chooseRequester(existingDepartment);
        return;
      }

      this.savingRequester = true;

      try {
        const response = await fetch(this.requesterStoreUrl, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ bagian_peminta: department }),
        });
        let payload;

        try {
          payload = await response.json();
        } catch {
          throw new Error('Server mengirim respons yang tidak dapat dibaca. Coba lagi.');
        }

        if (!response.ok) {
          const validationMessage = payload.errors?.bagian_peminta?.[0];
          throw new Error(validationMessage || payload.message || 'Nama peminta tidak dapat disimpan.');
        }

        const savedName = String(payload.data?.name || '').trim();
        if (!savedName) {
          throw new Error('Server tidak mengembalikan nama area yang tersimpan.');
        }

        const alreadyListed = this.requesters.some(
          (option) => option.toLocaleLowerCase('id') === savedName.toLocaleLowerCase('id'),
        );
        if (!alreadyListed) {
          this.requesters.unshift(savedName);
        }

        this.chooseRequester(savedName);
        this.setNotice(`Area peminta “${savedName}” berhasil disimpan dan siap digunakan.`, 'success');
      } catch (error) {
        this.setNotice(
          error instanceof Error ? error.message : 'Area peminta tidak dapat disimpan. Coba lagi.',
          'error',
        );
      } finally {
        this.savingRequester = false;
      }
    },
    selectItem(index, item) {
      const row = this.rows[index];
      row.item = item;
      row.query = item.name;
      row.searching = false;
      row.results = [];
      this.$nextTick(() => this.$refs[`qty-${index}`]?.focus());
    },
    clearItem(index) {
      const row = this.rows[index];
      row.item = null;
      row.query = '';
      row.qty = '';
      this.searchItems(index);
    },
    addRow() {
      this.rows.push(newRow());
      this.$nextTick(() => this.$refs[`item-${this.rows.length - 1}`]?.focus());
    },
    removeRow(index) {
      if (this.rows.length > 1) this.rows.splice(index, 1);
    },
    handleRowEnter(index, event) {
      if (event.shiftKey) return;
      event.preventDefault();
      if (index === this.rows.length - 1) {
        this.addRow();
        return;
      }
      this.$refs[`item-${index + 1}`]?.focus();
    },
    rowException(row) {
      if (!row.item) return '';
      const requestedQty = this.rows
        .filter((candidate) => candidate.item?.id === row.item.id)
        .reduce((total, candidate) => total + Number(candidate.qty || 0), 0);
      if (requestedQty > Number(row.item.stock)) return 'Melebihi stok';
      return '';
    },
    draftStorageKey() {
      return `sigma-recording-gu-issue-draft-${this.draftScope}`;
    },
    validateIssue() {
      if (this.entryMode !== 'late-paper') {
        this.syncRealTime();
      }
      if (!this.date) {
        this.setNotice('Tanggal transaksi tidak valid.', 'error');
        return false;
      }
      if (!this.occurredTime) {
        this.setNotice('Jam transaksi tidak valid.', 'error');
        return false;
      }
      if (this.transactions.some((transaction) => transaction.date === this.date && transaction.status === 'Final')) {
        this.setNotice('Tanggal ini sudah difinalisasi. Hubungi Admin GU untuk pembukaan kunci.', 'error');
        return false;
      }
      if (!this.requester) {
        this.setNotice('Pilih bagian peminta dari dropdown pencarian.', 'error');
        return false;
      }
      if (this.invalidRows) {
        this.setNotice('Lengkapi barang dan jumlah keluar (harus lebih dari nol) pada setiap baris.', 'error');
        return false;
      }
      this.notice = '';
      return true;
    },
    async saveIssue(next = false) {
      if (this.saving || !this.validateIssue()) return;
      this.saving = true;

      if (this.entryMode !== 'late-paper') {
        this.syncRealTime();
      }
      const targetDept = (this.department || this.requester).trim();
      try {
        const response = await fetch(this.storeUrl, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
          body: JSON.stringify({
            tanggal: this.date,
            waktu_keluar: this.occurredTime,
            entry_mode: this.entryMode,
            bagian_peminta: targetDept,
            catatan: this.note.trim() || null,
            items: this.rows.map((row) => ({
              barang_id: row.item.id,
              qty: Number(row.qty),
              catatan: row.note.trim() || null,
            })),
          }),
        });
        const payload = await response.json();

        if (!response.ok) {
          const validationMessage = Object.values(payload.errors || {}).flat()[0];
          throw new Error(validationMessage || payload.message || 'Pencatatan pengeluaran gagal disimpan.');
        }

        const transaction = payload.data;
        if (!transaction || !Array.isArray(transaction.items)) {
          throw new Error('Respons penyimpanan pengeluaran tidak valid.');
        }

        const wasAccumulated = this.transactions.some((existing) => existing.id === transaction.id);
        this.transactions = [
          transaction,
          ...this.transactions.filter((existing) => existing.id !== transaction.id),
        ];
        this.lastSavedTransactionId = transaction.id;
        this.setNotice(
          wasAccumulated
            ? `Pengeluaran untuk ${targetDept} ditambahkan ke BPB ${transaction.bpb}.`
            : `Pengeluaran untuk ${targetDept} berhasil dicatat pada BPB ${transaction.bpb}.`,
          'success',
        );
        this.showFlashMessage(`✓ Pengeluaran berhasil dicatat pada BPB ${transaction.bpb}.`);
        this.clearDraftLocally();
        this.resetForm();
        this.syncRealTime();
      } catch (error) {
        this.setNotice(
          error instanceof Error ? error.message : 'Pencatatan pengeluaran gagal disimpan.',
          'error',
        );
      } finally {
        this.saving = false;
      }
    },
    resetForm(clearMode = true) {
      this.requesterQuery = '';
      this.requester = '';
      this.department = '';
      this.requesterResults = [];
      this.note = '';
      this.rows = [newRow()];
      if (clearMode) this.entryMode = 'live';
      this.clearDraftLocally();
    },
    copyPrevious() {
      const previous = this.transactions.find((transaction) => transaction.date === dateOffset(-1));
      if (!previous) {
        this.setNotice('Tidak ada transaksi kemarin untuk disalin.', 'error');
        return;
      }
      this.requester = previous.requester;
      this.requesterQuery = previous.requester;
      this.department = previous.department;
      this.note = '';
      this.rows = previous.items.map((issued) => {
        const item = this.items.find((candidate) => candidate.name === issued.name) || null;
        return { ...newRow(), item, query: item?.name || issued.name, qty: issued.qty, note: issued.note || '' };
      });
      this.setNotice('Rincian transaksi kemarin disalin sebagai draft. Periksa tanggal sebelum mencatat.', 'info');
    },
    saveDraftLocally() {
      if (!this.$el) return;
      try {
        const draft = {
          date: this.date,
          occurredTime: this.occurredTime,
          entryMode: this.entryMode,
          requesterQuery: this.requesterQuery,
          requester: this.requester,
          department: this.department,
          note: this.note,
          rows: this.rows.map(({ results, searching, ...row }) => row),
        };
        localStorage.setItem(this.draftStorageKey(), JSON.stringify(draft));
        this.draftSavedAt = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
      } catch (error) {
        this.setNotice('Draft lokal tidak dapat disimpan di browser ini.', 'error');
      }
    },
    restoreDraft() {
      try {
        const rawDraft = localStorage.getItem(this.draftStorageKey());
        if (!rawDraft) return;
        const draft = JSON.parse(rawDraft);
        if (!draft || typeof draft !== 'object' || !Array.isArray(draft.rows)) {
          throw new TypeError('Format draft tidak valid.');
        }
        Object.assign(this, draft);
        if (typeof draft.department === 'string' && draft.department.trim()) {
          this.requester = draft.department.trim();
          this.requesterQuery = draft.department.trim();
          this.department = draft.department.trim();
        } else if (typeof draft.requester === 'string' && draft.requester.trim()) {
          this.requester = draft.requester.trim();
          this.requesterQuery = draft.requester.trim();
          this.department = draft.requester.trim();
        }
        delete this.kind;
        delete this.paperNumber;
        delete this.paperSignaturePresent;
        delete this.paperClerk;
        delete this.withoutForm;
        delete this.withoutFormReason;
        delete this.afterHours;
        delete this.spk;
        this.rows = draft.rows.map((row) => ({
          ...row,
          item: row.item ? this.items.find((item) => item.id === row.item.id) || row.item : null,
          results: [],
          searching: false,
        }));
        this.draftSavedAt = 'dipulihkan';
      } catch (error) {
        localStorage.removeItem(this.draftStorageKey());
        this.setNotice('Draft lokal tidak dapat dibaca dan telah dibersihkan. Silakan masukkan ulang data.', 'error');
      }
    },
    clearDraftLocally() {
      try {
        localStorage.removeItem(this.draftStorageKey());
        this.draftSavedAt = '';
      } catch (error) {
        this.setNotice('Draft tersimpan di tampilan, tetapi tidak dapat dihapus dari penyimpanan browser.', 'error');
      }
    },
    openReconciliation(transaction) {
      this.reconciliationTransaction = transaction;
      this.documentReceivedConfirmed = false;
      this.reconciliationClerk = '';
      this.reconciliationNote = '';
      this.notice = '';
    },
    confirmReconciliation() {
      if (!this.reconciliationTransaction || !this.documentReceivedConfirmed) {
        this.setNotice('Konfirmasikan bahwa dokumen fisik sudah diterima sebelum mencocokkan transaksi.', 'error');
        return;
      }
      if (!this.pendingTransactions.some((transaction) => transaction.id === this.reconciliationTransaction.id)) {
        this.setNotice('Transaksi ini sudah dicocokkan atau tidak lagi berada dalam antrean.', 'error');
        this.reconciliationTransaction = null;
        return;
      }
      const reconciledAt = new Date().toISOString();
      const transactionId = this.reconciliationTransaction.id;
      this.transactions = this.transactions.map((transaction) => (
        transaction.id === transactionId
          ? {
              ...transaction,
              status: 'Tercatat',
              paperReceivedAt: reconciledAt,
              reconciledAt,
              reconciledBy: 'Admin GU (pratinjau)',
              paperClerk: this.reconciliationClerk.trim(),
              reconciliationNote: this.reconciliationNote.trim(),
            }
          : transaction
      ));
      this.reconciliationTransaction = null;
      this.setNotice('Dokumen diterima dan transaksi dicocokkan pada pratinjau. Tidak ada transaksi baru atau perubahan server.', 'success');
    },
    finalizeReviewDate() {
      if (this.reviewTransactions.length === 0) {
        this.setNotice('Tidak ada transaksi untuk difinalisasi pada tanggal ini.', 'error');
        return;
      }
      if (this.reviewFinalized) {
        this.setNotice('Semua transaksi pada tanggal ini sudah difinalisasi.', 'error');
        return;
      }
      if (this.reviewPendingCount > 0) {
        this.setNotice('Masih ada transaksi menunggu rekonsiliasi. Cocokkan semua dokumen sebelum finalisasi.', 'error');
        return;
      }
      this.transactions = this.transactions.map((transaction) => (
        transaction.date === this.reviewDate ? { ...transaction, status: 'Final' } : transaction
      ));
      this.setNotice('Transaksi pada tanggal ini difinalisasi di pratinjau saja. Tidak ada perubahan server.', 'success');
    },
    formatDateIndo(dateStr) {
      if (!dateStr) return '';
      const parts = dateStr.split('-');
      if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
      }
      return dateStr;
    },
    getTransactionsForDepartment(deptName) {
      if (!deptName) return [];
      const dept = deptName.trim().toLocaleLowerCase('id');
      return this.transactions.filter((tx) =>
        ((tx.department || '').trim().toLocaleLowerCase('id') === dept ||
         (tx.requester || '').trim().toLocaleLowerCase('id') === dept)
      );
    },
    async fetchBorrowedItems() {
      if (!this.selectedReturnDepartment || !this.borrowedItemsUrl) {
        this.serverBorrowedItems = null;
        return;
      }
      this.loadingBorrowedItems = true;
      try {
        const params = new URLSearchParams({
          department: this.selectedReturnDepartment,
        });
        const res = await fetch(`${this.borrowedItemsUrl}?${params.toString()}`, {
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        if (res.ok) {
          const payload = await res.json();
          if (Array.isArray(payload.data)) {
            this.serverBorrowedItems = payload.data;
          }
        }
      } catch (err) {
        console.error('Failed to fetch borrowed items from server', err);
      } finally {
        this.loadingBorrowedItems = false;
      }
    },
    async fetchReturnOptions() {
      if (!this.returnOptionsUrl) return;
      this.loadingReturnOptions = true;
      try {
        const res = await fetch(this.returnOptionsUrl, {
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        if (res.ok) {
          const payload = await res.json();
          if (Array.isArray(payload.data)) {
            this.serverDepartmentReturnOptions = payload.data;
          }
        }
      } catch (err) {
        console.error('Failed to fetch return options from server', err);
      } finally {
        this.loadingReturnOptions = false;
      }
    },
    onReturnDepartmentChange() {
      if (!this.selectedReturnDepartment) {
        this.serverBorrowedItems = null;
        return;
      }

      this.returnInputs = {};
      this.serverBorrowedItems = null;
      this.fetchBorrowedItems();
    },
    get departmentReturnOptions() {
      // Selalu gunakan data dari server — di-fetch ulang saat tab retur dibuka / tanggal berubah
      return Array.isArray(this.serverDepartmentReturnOptions) ? this.serverDepartmentReturnOptions : [];
    },
    get departmentsWithIssuesOnSelectedReturnDate() {
      return this.departmentReturnOptions.map((opt) => opt.name);
    },
    get departmentsWithTodayIssues() {
      return this.departmentReturnOptions;
    },
    get borrowedItemsForSelectedDepartment() {
      if (!this.selectedReturnDepartment) return [];

      // Jika data dinamis dari server sudah terambil, gunakan langsung
      if (Array.isArray(this.serverBorrowedItems)) {
        return this.serverBorrowedItems;
      }

      const dept = this.selectedReturnDepartment.trim().toLocaleLowerCase('id');
      const deptTransactions = this.transactions.filter((tx) => {
        const matchDept = (tx.department || '').trim().toLocaleLowerCase('id') === dept ||
                          (tx.requester || '').trim().toLocaleLowerCase('id') === dept;
        return matchDept;
      });

      const itemsMap = new Map();
      deptTransactions.forEach((tx) => {
        tx.items.forEach((item) => {
          if (!itemsMap.has(item.name)) {
            itemsMap.set(item.name, {
              name: item.name,
              category: item.category,
              unit: item.unit,
              totalTaken: 0,
              totalReturned: 0,
            });
          }
          const entry = itemsMap.get(item.name);
          entry.totalTaken = Number((entry.totalTaken + Number(item.qty)).toFixed(3));
        });
      });

      // Hitung total retur yang sudah tersimpan untuk divisi & barang ini
      this.returnRecords.forEach((record) => {
        const recDept = (record.department || '').trim().toLocaleLowerCase('id');
        if (recDept === dept && itemsMap.has(record.itemName)) {
          const entry = itemsMap.get(record.itemName);
          entry.totalReturned = Number((entry.totalReturned + Number(record.qty)).toFixed(3));
        }
      });

      const result = [];
      itemsMap.forEach((entry) => {
        const availableToReturn = Math.max(0, Number((entry.totalTaken - entry.totalReturned).toFixed(3)));
        if (availableToReturn === 0) return;

        result.push({
          ...entry,
          availableToReturn,
        });
      });

      return result;
    },
    validateReturnInput(item) {
      const currentVal = Number(this.returnInputs[item.name] || 0);
      if (currentVal < 0) {
        this.returnInputs[item.name] = 0;
      } else if (currentVal > item.availableToReturn) {
        this.returnInputs[item.name] = item.availableToReturn;
        this.setNotice(`Maksimal sisa/retur untuk ${item.name} adalah ${item.availableToReturn} ${item.unit}.`, 'warning');
      }
    },
    calculateFinalUsage(totalTaken, totalReturned, inputQty) {
      const sisa = Number(inputQty || 0);
      return Math.max(0, Number((totalTaken - totalReturned - sisa).toFixed(3)));
    },
    async saveDepartmentReturns() {
      if (!this.selectedReturnDepartment) {
        this.setNotice('Pilih bagian peminta terlebih dahulu.', 'error');
        return;
      }
      const borrowed = this.borrowedItemsForSelectedDepartment;
      const itemsToReturn = borrowed.filter((item) => Number(this.returnInputs[item.name] || 0) > 0);

      if (itemsToReturn.length === 0) {
        this.setNotice('Isi angka lebih dari 0 pada kolom INPUT QTY SISA/RETUR sebelum menyimpan.', 'error');
        return;
      }

      this.savingReturn = true;
      const returnDate = this.returnDate || localDate();

      // 1. Simpan ke Database Backend secara dinamis
      if (this.returnStoreUrl) {
        try {
          const response = await fetch(this.returnStoreUrl, {
            method: 'POST',
            headers: {
              Accept: 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
              bagian_peminta: this.selectedReturnDepartment,
              tanggal_kembali: returnDate,
              items: itemsToReturn.map((item) => ({
                name: item.name,
                qty_kembali: Number(this.returnInputs[item.name]),
              })),
            }),
          });

          const payload = await response.json();
          if (!response.ok) {
            throw new Error(payload.message || 'Penyimpanan pengembalian barang gagal.');
          }

          if (payload.data) {
            if (Array.isArray(payload.data.transactions)) {
              this.transactions = payload.data.transactions;
            }
            if (Array.isArray(payload.data.borrowedItems)) {
              this.serverBorrowedItems = payload.data.borrowedItems;
            }
            if (Array.isArray(payload.data.departmentOptions)) {
              this.serverDepartmentReturnOptions = payload.data.departmentOptions;
            }
            if (Array.isArray(payload.data.returnRecords)) {
              this.returnRecords = payload.data.returnRecords;
            }
          }

          itemsToReturn.forEach((item) => {
            this.returnInputs[item.name] = 0;
          });

          this.setNotice(payload.message || 'Pengembalian sisa barang berhasil disimpan ke server.', 'success');
          this.showFlashMessage('✓ Pengembalian sisa barang berhasil dicatat ke database.');
          return;
        } catch (error) {
          this.setNotice(error instanceof Error ? error.message : 'Penyimpanan pengembalian gagal.', 'error');
          return;
        } finally {
          this.savingReturn = false;
        }
      }

      // Fallback lokal jika endpoint tidak tersedia
      const deptKey = this.selectedReturnDepartment.trim().toLocaleLowerCase('id');
      const deptTransactions = this.transactions.filter((tx) => {
        const matchDept = (tx.department || '').trim().toLocaleLowerCase('id') === deptKey ||
                          (tx.requester || '').trim().toLocaleLowerCase('id') === deptKey;
        return matchDept;
      });

      const summary = [];
      const returnedTimestamp = `${returnDate}T12:00:00.000Z`;
      itemsToReturn.forEach((item) => {
        const qtyToReturn = Number(this.returnInputs[item.name]);
        const finalUsage = this.calculateFinalUsage(item.totalTaken, item.totalReturned, qtyToReturn);

        this.adjustItemStock(item.name, qtyToReturn);

        const record = {
          id: crypto.randomUUID(),
          transactionId: deptTransactions[0]?.id ?? null,
          department: this.selectedReturnDepartment,
          itemName: item.name,
          qty: qtyToReturn,
          unit: item.unit,
          condition: 'Layak pakai (kembali)',
          totalTaken: item.totalTaken,
          finalUsage,
          returnedAt: returnedTimestamp,
          recordedAt: new Date().toISOString(),
          note: `Pengembalian sisa barang bagian ${this.selectedReturnDepartment} pada ${this.formatDateIndo(returnDate)}`,
        };
        this.returnRecords.unshift(record);

        summary.push(`${item.name} (+${qtyToReturn} ${item.unit}, pemakaian riil: ${finalUsage} ${item.unit})`);
        this.returnInputs[item.name] = 0;
      });

      this.setNotice(
        `Retur sisa untuk ${this.selectedReturnDepartment} berhasil disimpan! Stok gudang bertambah: [${summary.join(', ')}].`,
        'success'
      );
      this.savingReturn = false;
    },
    returnedQty(transactionId, itemIndex) {
      const transaction = this.transactions.find((tx) => tx.id === transactionId);
      if (!transaction || !transaction.items || !transaction.items[itemIndex]) return 0;
      const item = transaction.items[itemIndex];
      const dept = (transaction.department || transaction.requester || '').trim().toLocaleLowerCase('id');

      return this.returnRecords
        .filter((rec) => {
          const matchDept = (rec.department || '').trim().toLocaleLowerCase('id') === dept;
          const matchItem = rec.itemName === item.name;
          const matchTx = rec.transactionId ? rec.transactionId === transactionId : true;
          return matchDept && matchItem && matchTx;
        })
        .reduce((total, rec) => Number((total + Number(rec.qty || 0)).toFixed(3)), 0);
    },
    finalUsageQty(transactionId, itemIndex) {
      const transaction = this.transactions.find((tx) => tx.id === transactionId);
      if (!transaction || !transaction.items || !transaction.items[itemIndex]) return 0;
      const item = transaction.items[itemIndex];
      const returned = this.returnedQty(transactionId, itemIndex);
      return Math.max(0, Number((Number(item.qty) - returned).toFixed(3)));
    },
    adjustItemStock(itemName, quantity) {
      const inventoryItem = this.items.find((item) => item.name === itemName);
      if (inventoryItem) inventoryItem.stock = Number((inventoryItem.stock + quantity).toFixed(3));
    },
    openTransaction(transaction) {
      this.bpbPreview = transaction;
    },
    printBpb(transaction) {
      this.bpbPreview = transaction;
    },
    printBpbDocument() {
      window.print();
    },
    toggleTransactionDetails(transaction) {
      if (!transaction || !transaction.id) return;
      this.expandedTransactionId = this.expandedTransactionId === transaction.id ? null : transaction.id;
    },
    openVerifyModal(transaction) {
      if (!transaction) return;
      this.verifyingTransaction = transaction;
      this.verifyBonKortas = '';
      this.verifyPhysicalDocMatch = true;
      this.verifyNotes = '';
      this.verifyRejectMode = false;
      this.verifyRejectReason = '';
      this.verifyItems = (transaction.items || []).map((item) => {
        const inventoryItem = this.items.find((inv) => inv.name === item.name || inv.code === item.code);
        const currentStock = inventoryItem ? Number(inventoryItem.stock || 0) : 0;
        const initialHpp = Number(item.hpp || inventoryItem?.hpp || inventoryItem?.price || 0);
        const initialQty = Number(item.qty || 0);
        return {
          id: item.id,
          name: item.name,
          code: item.code || '-',
          category: item.category || 'Bahan & Logistik',
          unit: item.unit || 'unit',
          ajuanQty: initialQty,
          qty: initialQty,
          hpp: initialHpp,
          stok: currentStock,
          subtotal: Math.round(initialQty * initialHpp),
          note: item.note || '',
        };
      });
      this.showVerifyModal = true;
    },
    closeVerifyModal() {
      this.showVerifyModal = false;
      this.verifyingTransaction = null;
      this.verifyItems = [];
      this.verifyRejectMode = false;
      this.verifyRejectReason = '';
    },
    updateVerifySubtotal(item) {
      const q = Math.max(0, Number(item.qty || 0));
      const p = Math.max(0, Number(item.hpp || 0));
      item.subtotal = Math.round(q * p);
    },
    get verifyTotalNominal() {
      return this.verifyItems.reduce((acc, curr) => acc + (Number(curr.subtotal) || 0), 0);
    },
    get verifyHasZeroHpp() {
      return this.verifyItems.some((item) => !(Number(item.hpp) > 0));
    },
    showFlashMessage(msg) {
      this.savedFlashMessage = msg;
      if (this.savedFlashTimeout) clearTimeout(this.savedFlashTimeout);
      this.savedFlashTimeout = setTimeout(() => {
        this.savedFlashMessage = '';
      }, 7000);
    },
    viewTransactionInHistory(transaction) {
      if (!transaction) return;
      this.setView('history');
      this.historySubView = 'transactions';
      this.historyDate = transaction.date;
      this.historyDepartment = '';
      this.expandedTransactionId = transaction.id;
      this.$nextTick(() => {
        const el = document.getElementById(`issue-details-${transaction.id}`);
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });
    },
  };
}
