export function createRecordingGuReceipt(unitReceiptCode, initialData = {}) {
  const parsedStoreRequests = Array.isArray(initialData.storeRequests)
    ? initialData.storeRequests
    : [];

  return {
    activeTab: 'direct',
    hasDeliveryNoteDirect: true,
    noDeliveryNoteDirect: false,
    directReceiptDate: new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16),
    storeReceiptNotice: '',
    storeReceiptDate: new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16),
    hasDeliveryNoteStore: true,
    directSubmitUrl: initialData.directSubmitUrl || '/unit-bisnis/recording-gu/penerimaan',
    storeSubmitUrl: initialData.storeSubmitUrl || '/unit-bisnis/recording-gu/penerimaan/konfirmasi-transfer',
    csrfToken: initialData.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    isSubmittingDirect: false,
    isSubmittingStore: false,
    selectedStoreRequestId: (function () {
      if (parsedStoreRequests.length > 0) {
        const waiting = parsedStoreRequests.find((r) => r.status === 'menunggu_diterima');
        return waiting ? waiting.id : parsedStoreRequests[0].id;
      }
      return '';
    })(),
    selectedStoreRequestStatusFilter: 'all',
    storeRequests: parsedStoreRequests,
    unitReceiptCode,
    get directReceiptNumber() {
      if (initialData.nextDirectReceiptNumber) {
        return initialData.nextDirectReceiptNumber;
      }
      const date = this.directReceiptDate.slice(0, 10).split('-').reverse().join('');
      return `RCV-${this.unitReceiptCode}-${date}001-0001`;
    },
    directItems: [
      {
        id: null,
        query: '',
        code: '',
        name: '',
        category: '—',
        unit: '',
        qtyReceived: 0,
        condition: 'Baik',
        qtyDamaged: 0,
        qtyShort: 0,
        qtyMore: 0,
        keterangan: '',
        results: [],
        selected: false,
        hasSearched: false,
      },
    ],
    directItemCatalog: Array.isArray(initialData.directItemCatalog) ? initialData.directItemCatalog : [],
    directVendor: '',
    deliveryNoteReason: '',
    receiptNotice: '',
    get filteredStoreRequests() {
      if (this.selectedStoreRequestStatusFilter === 'all') {
        return this.storeRequests;
      }
      return this.storeRequests.filter(
        (request) => request.status === this.selectedStoreRequestStatusFilter
      );
    },
    setStoreRequestFilter(status) {
      this.selectedStoreRequestStatusFilter = status;
      const matched = this.filteredStoreRequests;
      if (matched.length > 0 && !matched.some(r => r.id === this.selectedStoreRequestId)) {
        this.selectedStoreRequestId = matched[0].id;
      }
      this.storeReceiptNotice = '';
    },
    get selectedStoreRequest() {
      return this.storeRequests.find((request) => request.id === this.selectedStoreRequestId)
        || this.filteredStoreRequests[0]
        || this.storeRequests[0]
        || { id: '', poNumber: '', vendor: '', deliveryNote: '', status: '', statusLabel: '', requestDate: '', items: [] };
    },
    get storeReceiptNumber() {
      if (this.selectedStoreRequest?.rcvNumber) {
        return this.selectedStoreRequest.rcvNumber;
      }
      const date = this.storeReceiptDate.slice(0, 10).split('-').reverse().join('');
      return `RCV-${this.unitReceiptCode}-${date}001-0002`;
    },
    storeRequestDifference(item) {
      return Number(item.receivedQty || 0) - Number(item.requestQty || 0);
    },
    stockQuantityPreview(receivedQuantity, damagedQuantity) {
      return Math.max(0, Number(receivedQuantity || 0) - Number(damagedQuantity || 0));
    },
    formatReceiptQuantity(quantity) {
      return Number(quantity || 0).toLocaleString('id-ID', { maximumFractionDigits: 3 });
    },
    directStockQuantityPreview(item) {
      return this.stockQuantityPreview(item.qtyReceived, item.qtyDamaged);
    },
    storeStockQuantityPreview(item) {
      return this.stockQuantityPreview(item.receivedQty, item.damagedQty);
    },
    updateStoreReceivedQuantity(item) {
      const difference = this.storeRequestDifference(item);
      const hasDamage = Number(item.damagedQty || 0) > 0;
      item.shortQty = Math.max(0, -difference);
      item.moreQty = Math.max(0, difference);
      if (difference < 0) item.condition = hasDamage ? 'Rusak dan Kurang' : 'Kurang';
      else if (difference > 0) item.condition = hasDamage ? 'Rusak dan Lebih' : 'Lebih';
      else item.condition = hasDamage ? 'Rusak' : 'Baik';
      item.matchesRequest = difference === 0 && !hasDamage;
      this.storeReceiptNotice = '';
    },
    storeRequestStatus(item) {
      const difference = this.storeRequestDifference(item);
      const hasDamage = Number(item.damagedQty || 0) > 0;
      if (difference > 0 && hasDamage) return 'Lebih dan ada barang rusak';
      if (difference > 0) return 'Lebih dari permintaan';
      if (difference < 0 && hasDamage) return 'Kurang dan ada barang rusak';
      if (difference < 0) return 'Kurang dari permintaan';
      if (hasDamage) return 'Qty sesuai, ada barang rusak';
      return 'Sesuai permintaan';
    },
    storeReceivedTotal() {
      return (this.selectedStoreRequest?.items || []).reduce((total, item) => total + Number(item.receivedQty || 0), 0);
    },
    storeRequestCountByDifference(targetDifference) {
      return (this.selectedStoreRequest?.items || []).filter((item) => {
        const difference = this.storeRequestDifference(item);
        if (targetDifference === 'match') return difference === 0 && Number(item.damagedQty || 0) === 0;
        if (targetDifference === 'short') return difference < 0;
        if (targetDifference === 'over') return difference > 0;
        return Number(item.damagedQty || 0) > 0;
      }).length;
    },
    setStoreItemCondition(item) {
      if (!['Rusak', 'Rusak dan Kurang', 'Rusak dan Lebih'].includes(item.condition)) item.damagedQty = 0;
      if (!['Kurang', 'Rusak dan Kurang'].includes(item.condition)) item.shortQty = 0;
      if (!['Lebih', 'Rusak dan Lebih'].includes(item.condition)) item.moreQty = 0;
      if (['Rusak', 'Rusak dan Kurang', 'Rusak dan Lebih'].includes(item.condition) && Number(item.damagedQty || 0) === 0) item.damagedQty = 1;
      if (['Kurang', 'Rusak dan Kurang'].includes(item.condition) && Number(item.shortQty || 0) === 0) item.shortQty = 1;
      if (['Lebih', 'Rusak dan Lebih'].includes(item.condition) && Number(item.moreQty || 0) === 0) item.moreQty = 1;
      if (['Kurang', 'Rusak dan Kurang'].includes(item.condition)) {
        item.receivedQty = Math.max(0, Number(item.requestQty || 0) - Number(item.shortQty || 0));
      }
      if (['Lebih', 'Rusak dan Lebih'].includes(item.condition)) {
        item.receivedQty = Number(item.requestQty || 0) + Number(item.moreQty || 0);
      }
      if (['Baik', 'Rusak'].includes(item.condition)) {
        item.receivedQty = Number(item.requestQty || 0);
      }
      item.matchesRequest = this.storeRequestDifference(item) === 0 && Number(item.damagedQty || 0) === 0;
      this.storeReceiptNotice = '';
    },
    updateStoreShortQuantity(item) {
      item.shortQty = Math.max(0, Number(item.shortQty || 0));
      item.receivedQty = Math.max(0, Number(item.requestQty || 0) - item.shortQty);
      item.condition = item.shortQty === 0
        ? (Number(item.damagedQty || 0) > 0 ? 'Rusak' : 'Baik')
        : (Number(item.damagedQty || 0) > 0 ? 'Rusak dan Kurang' : 'Kurang');
      item.matchesRequest = item.shortQty === 0 && Number(item.damagedQty || 0) === 0;
      this.storeReceiptNotice = '';
    },
    normalizeStoreShortQuantity(item) {
      item.shortQty = Math.min(Number(item.requestQty || 0), Math.max(0, Number(item.shortQty || 0)));
      this.updateStoreShortQuantity(item);
    },
    updateStoreMoreQuantity(item) {
      item.moreQty = Math.max(0, Number(item.moreQty || 0));
      item.receivedQty = Number(item.requestQty || 0) + item.moreQty;
      item.condition = item.moreQty === 0
        ? (Number(item.damagedQty || 0) > 0 ? 'Rusak' : 'Baik')
        : (Number(item.damagedQty || 0) > 0 ? 'Rusak dan Lebih' : 'Lebih');
      item.matchesRequest = item.moreQty === 0 && Number(item.damagedQty || 0) === 0;
      this.storeReceiptNotice = '';
    },
    updateStoreDamagedQuantity(item) {
      item.damagedQty = Math.max(0, Number(item.damagedQty || 0));
      const difference = this.storeRequestDifference(item);
      if (difference < 0) item.condition = item.damagedQty > 0 ? 'Rusak dan Kurang' : 'Kurang';
      else if (difference > 0) item.condition = item.damagedQty > 0 ? 'Rusak dan Lebih' : 'Lebih';
      else item.condition = item.damagedQty > 0 ? 'Rusak' : 'Baik';
      item.matchesRequest = difference === 0 && item.damagedQty === 0;
      this.storeReceiptNotice = '';
    },
    normalizeStoreDamagedQuantity(item) {
      item.damagedQty = Math.min(Number(item.receivedQty || 0), Math.max(0, Number(item.damagedQty || 0)));
      this.updateStoreDamagedQuantity(item);
    },
    toggleStoreItemMatch(item, isChecked) {
      if (isChecked) {
        this.markStoreItemMatchesRequest(item);
        return;
      }
      item.matchesRequest = false;
    },
    markStoreItemMatchesRequest(item) {
      item.receivedQty = Number(item.requestQty || 0);
      item.shortQty = 0;
      item.moreQty = 0;
      item.condition = 'Baik';
      item.damagedQty = 0;
      item.matchesRequest = true;
      this.storeReceiptNotice = '';
    },
    markStoreRequestComplete() {
      (this.selectedStoreRequest?.items || []).forEach((item) => this.markStoreItemMatchesRequest(item));
    },
    resetStoreRequest() {
      (this.selectedStoreRequest?.items || []).forEach((item) => {
        item.receivedQty = item.initialReceivedQty;
        item.condition = item.initialCondition;
        item.damagedQty = item.initialDamagedQty;
        item.shortQty = item.initialShortQty;
        item.moreQty = item.initialMoreQty;
        item.note = item.initialNote;
        item.matchesRequest = item.initialMatchesRequest;
      });
      this.storeReceiptDate = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);
      this.storeReceiptNotice = '';
    },
    simulateStoreReceipt() {
      const request = this.selectedStoreRequest;
      const invalidQuantity = request.items.some((item) => Number(item.receivedQty) < 0
        || Number(item.damagedQty || 0) > Number(item.receivedQty || 0));
      if (invalidQuantity) {
        this.storeReceiptNotice = 'Periksa kembali Qty Diterima dan Qty Rusak. Qty tidak boleh negatif dan qty rusak tidak boleh melebihi jumlah diterima.';
        return;
      }
      const allMatch = request.items.every((item) => this.storeRequestDifference(item) === 0 && Number(item.damagedQty || 0) === 0);
      const status = allMatch
        ? 'seluruh barang sesuai permintaan'
        : 'penerimaan memiliki selisih jumlah dan/atau barang rusak';
      this.storeReceiptNotice = `Simulasi ${this.storeReceiptNumber} untuk ${request.id}: ${status}. Data belum disimpan.`;
    },
    simulateDirectReceipt() {
      const selectedItems = this.directItems.filter((item) => item.selected);
      if (!this.directVendor.trim()) {
        this.receiptNotice = 'Isi nama supplier sebelum menjalankan simulasi.';
        return;
      }
      if (selectedItems.length === 0 || selectedItems.some((item) => Number(item.qtyReceived) <= 0)) {
        this.receiptNotice = 'Pilih minimal satu barang dan isi qty diterima lebih dari nol.';
        return;
      }
      const sj = this.hasDeliveryNoteDirect ? 'dengan surat jalan' : 'tanpa surat jalan';
      this.receiptNotice = `Simulasi ${this.directReceiptNumber} dari ${this.directVendor} (${sj}) selesai untuk ${selectedItems.length} barang. Data belum disimpan.`;
    },
    async submitDirectReceipt() {
      const selectedItems = this.directItems.filter((item) => item.selected);
      if (!this.directVendor.trim()) {
        this.receiptNotice = 'Isi nama supplier sebelum menyimpan penerimaan.';
        return;
      }
      if (!this.hasDeliveryNoteDirect && !this.deliveryNoteReason.trim()) {
        this.receiptNotice = 'Alasan tanpa surat jalan wajib diisi jika surat jalan tidak ada.';
        return;
      }
      if (selectedItems.length === 0 || selectedItems.some((item) => Number(item.qtyReceived) <= 0)) {
        this.receiptNotice = 'Pilih minimal satu barang dan isi qty diterima lebih dari nol.';
        return;
      }

      this.isSubmittingDirect = true;
      this.receiptNotice = '';

      const payload = {
        nama_supplier: this.directVendor.trim(),
        has_surat_jalan: Boolean(this.hasDeliveryNoteDirect),
        alasan_tanpa_sj: !this.hasDeliveryNoteDirect ? this.deliveryNoteReason.trim() : null,
        tanggal_terima: this.directReceiptDate ? this.directReceiptDate.slice(0, 10) : null,
        waktu_terima: this.directReceiptDate ? this.directReceiptDate.slice(11, 16) : null,
        items: selectedItems.map((item) => ({
          barang_id: item.id,
          qty_diterima: Number(item.qtyReceived),
          satuan: item.unit || '',
          kondisi: item.condition || 'Baik',
          qty_rusak: Number(item.qtyDamaged || 0),
          qty_kurang: Number(item.qtyShort || 0),
          qty_lebih: Number(item.qtyMore || 0),
          catatan: item.keterangan || null,
        })),
      };

      try {
        const response = await fetch(this.directSubmitUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken,
          },
          body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok) {
          const errors = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal menyimpan penerimaan.');
          this.receiptNotice = 'Gagal: ' + errors;
          this.isSubmittingDirect = false;
          return;
        }

        this.receiptNotice = '✓ ' + (data.message || 'Penerimaan berhasil disimpan!');

        setTimeout(() => {
          window.location.reload();
        }, 1200);
      } catch (err) {
        this.receiptNotice = 'Terjadi kesalahan jaringan atau server saat menyimpan penerimaan.';
        this.isSubmittingDirect = false;
      }
    },
    async submitStoreReceipt() {
      const request = this.selectedStoreRequest;
      if (!request || !request.items || request.items.length === 0) {
        this.storeReceiptNotice = 'Tidak ada item permintaan yang dipilih.';
        return;
      }

      if (request.status === 'sudah_diterima') {
        this.storeReceiptNotice = 'Permintaan ini sudah selesai diterima sebelumnya.';
        return;
      }

      const invalidQuantity = request.items.some((item) => Number(item.receivedQty) < 0
        || Number(item.damagedQty || 0) > Number(item.receivedQty || 0));
      if (invalidQuantity) {
        this.storeReceiptNotice = 'Periksa kembali Qty Diterima dan Qty Rusak. Qty tidak boleh negatif dan qty rusak tidak boleh melebihi jumlah diterima.';
        return;
      }

      this.isSubmittingStore = true;
      this.storeReceiptNotice = '';

      const payload = {
        request_id: request.id,
        items: request.items.map((item) => ({
          transfer_id: item.id,
          qty_diterima: Number(item.receivedQty || 0),
          qty_rusak: Number(item.damagedQty || 0),
          catatan_discrepancy: item.note || null,
        })),
      };

      try {
        const response = await fetch(this.storeSubmitUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken,
          },
          body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok) {
          const errors = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal mengonfirmasi penerimaan.');
          this.storeReceiptNotice = 'Gagal: ' + errors;
          this.isSubmittingStore = false;
          return;
        }

        this.storeReceiptNotice = '✓ ' + (data.message || 'Penerimaan Store berhasil dikonfirmasi!');
        request.status = 'sudah_diterima';
        request.statusLabel = 'Sudah Diterima';

        setTimeout(() => {
          window.location.reload();
        }, 1200);
      } catch (err) {
        this.storeReceiptNotice = 'Terjadi kesalahan jaringan atau server saat memproses konfirmasi.';
        this.isSubmittingStore = false;
      }
    },
    abbreviatedUnit(unit) {
      const normalized = String(unit || '').trim().toLowerCase();
      const unitAbbreviations = {
        kilogram: 'kg', kilograms: 'kg', kilo: 'kg', kg: 'kg',
        gram: 'g', grams: 'g', g: 'g',
        liter: 'l', litre: 'l', liters: 'l', litres: 'l', l: 'l',
        mililiter: 'ml', milliliter: 'ml', millilitre: 'ml', ml: 'ml',
        piece: 'pcs', pieces: 'pcs', pc: 'pcs', pcs: 'pcs',
        pack: 'pack', paket: 'pack', package: 'pack',
        roll: 'roll', gulung: 'roll',
        dus: 'dus', box: 'dus', carton: 'dus', karton: 'dus',
        sak: 'sak', sack: 'sak', karung: 'sak',
        lembar: 'lbr', sheet: 'lbr', lbr: 'lbr',
        balok: 'blk', meter: 'm', metre: 'm', m: 'm',
        centimeter: 'cm', centimetre: 'cm', cm: 'cm',
        ton: 'ton', unit: 'unit', buah: 'buah',
      };
      return unitAbbreviations[normalized] || normalized;
    },
    cancelDirectReceipt() {
      this.directVendor = '';
      this.hasDeliveryNoteDirect = true;
      this.noDeliveryNoteDirect = false;
      this.deliveryNoteReason = '';
      this.directReceiptDate = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);
      this.directItems.splice(1);
      this.resetDirectItem(0);
      this.receiptNotice = 'Form dikosongkan. Perubahan hanya berlaku di pratinjau frontend.';
    },
    addDirectItem() {
      this.directItems.push({
        id: null,
        query: '',
        code: '',
        name: '',
        category: '—',
        unit: '',
        qtyReceived: 0,
        condition: 'Baik',
        qtyDamaged: 0,
        qtyShort: 0,
        qtyMore: 0,
        keterangan: '',
        results: [],
        selected: false,
        hasSearched: false,
      });
    },
    searchDirectItem(index) {
      const row = this.directItems[index];
      const query = row.query.trim().toLowerCase();
      row.hasSearched = query.length > 0;
      row.results = query
        ? this.directItemCatalog.filter((item) => `${item.code} ${item.name} ${item.category}`.toLowerCase().includes(query)).slice(0, 8)
        : [];
    },
    highlightDirectItem(value, query) {
      const text = String(value ?? '');
      const terms = String(query ?? '').trim().split(/\s+/).filter(Boolean);
      const escapeHtml = (textToEscape) => String(textToEscape).replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
      })[character]);

      if (terms.length === 0) return escapeHtml(text);

      const escapeRegExp = (term) => term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const matcher = new RegExp(terms.sort((left, right) => right.length - left.length).map(escapeRegExp).join('|'), 'gi');
      let html = '';
      let lastIndex = 0;
      let match;

      while ((match = matcher.exec(text)) !== null) {
        html += escapeHtml(text.slice(lastIndex, match.index));
        html += `<mark style="padding:0 1px;border-radius:2px;background:rgba(245,190,66,.38);color:inherit;">${escapeHtml(match[0])}</mark>`;
        lastIndex = match.index + match[0].length;
      }

      return html + escapeHtml(text.slice(lastIndex));
    },
    selectDirectItem(index, item) {
      const row = this.directItems[index];
      row.id = item.id;
      row.code = item.code;
      row.name = item.name;
      row.category = item.category || '—';
      row.unit = this.abbreviatedUnit(item.unit || '');
      row.query = item.name;
      row.selected = true;
      row.results = [];
      row.hasSearched = false;
      this.receiptNotice = '';
    },
    resetDirectItem(index) {
      const row = this.directItems[index];
      row.id = null;
      row.code = '';
      row.name = '';
      row.category = '—';
      row.unit = '';
      row.query = '';
      row.selected = false;
      row.condition = 'Baik';
      row.qtyReceived = 0;
      row.qtyDamaged = 0;
      row.qtyShort = 0;
      row.qtyMore = 0;
      row.keterangan = '';
      row.results = [];
      row.hasSearched = false;
    },
    removeDirectItem(index) {
      if (this.directItems.length > 1) {
        this.directItems.splice(index, 1);
      }
    },
  };
}
