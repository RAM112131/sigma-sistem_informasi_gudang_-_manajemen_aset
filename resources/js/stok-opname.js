export function createStocktakeRow(initialQuantity, systemQuantity, unitPrice, unit, initialNote, snapshotFresh) {
  return {
    quantity: initialQuantity,
    savedQuantity: initialQuantity,
    systemQuantity,
    unitPrice,
    unit,
    note: initialNote,
    snapshotFresh,
    showNote: false,
    get difference() {
      if (!this.snapshotFresh || this.quantity === '') {
        return null;
      }

      return Math.round((Number(this.quantity) - this.systemQuantity) * 1000) / 1000;
    },
    get status() {
      if (this.difference === null) {
        return 'belum';
      }
      if (this.difference < 0) {
        return 'kurang';
      }
      if (this.difference > 0) {
        return 'lebih';
      }

      return 'sesuai';
    },
    get isSaved() {
      return this.snapshotFresh && this.quantity !== '' && this.quantity === this.savedQuantity;
    },
    get statusBadgeClass() {
      return {
        kurang: 'bg-red-100 text-red-800',
        lebih: 'bg-blue-100 text-blue-800',
        sesuai: 'bg-emerald-100 text-emerald-800',
        belum: 'bg-[#EEF0EE] text-[#5B6B79]',
      }[this.status];
    },
    get statusTextClass() {
      return {
        kurang: 'text-[#B42318]',
        lebih: 'text-[#1D5B52]',
        sesuai: 'text-[#145C54]',
        belum: 'text-[#71807B]',
      }[this.status];
    },
    get differenceClass() {
      if (this.difference < 0) {
        return 'font-semibold text-[#B42318]';
      }
      if (this.difference > 0) {
        return 'font-semibold text-[#145C54]';
      }

      return 'text-[#52615D]';
    },
    formatQuantity(value) {
      return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format(value);
    },
    formatMoney(value) {
      const amount = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.abs(value));

      return `${value < 0 ? '-Rp ' : '+Rp '}${amount}`;
    },
  };
}
