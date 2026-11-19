{{-- Container cetak surat jalan — hanya muncul saat browser print, disembunyikan di layar --}}
<div id="sigma-printable-surat-jalan" aria-hidden="true"></div>

<style>
    /* Di layar, container cetak tidak terlihat */
    @media screen {
        #sigma-printable-surat-jalan {
            display: none !important;
        }
    }

    /* Saat mencetak (window.print()) */
    @media print {

        html,
        body {
            background: #FFFFFF !important;
            color: #000000 !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            height: auto !important;
            overflow: visible !important;
        }

        /* Sembunyikan semua elemen body kecuali container surat jalan */
        body>*:not(#sigma-printable-surat-jalan) {
            display: none !important;
            visibility: hidden !important;
        }

        /* Tampilkan container surat jalan penuh di lembar cetak */
        #sigma-printable-surat-jalan {
            display: block !important;
            visibility: visible !important;
            position: static !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 8mm 12mm !important;
            background: #FFFFFF !important;
            color: #000000 !important;
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 9pt !important;
            line-height: 1.4 !important;
            box-sizing: border-box !important;
        }

        #sigma-printable-surat-jalan * {
            visibility: visible !important;
            box-sizing: border-box !important;
            color: #000000 !important;
        }

        #sigma-printable-surat-jalan table {
            display: table !important;
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 12pt !important;
        }

        #sigma-printable-surat-jalan thead {
            display: table-header-group !important;
        }

        #sigma-printable-surat-jalan tbody {
            display: table-row-group !important;
        }

        #sigma-printable-surat-jalan tr {
            display: table-row !important;
            page-break-inside: avoid !important;
        }

        #sigma-printable-surat-jalan th,
        #sigma-printable-surat-jalan td {
            display: table-cell !important;
        }

        @page {
            margin: 6mm 10mm;
            size: auto;
        }
    }
</style>

<script>
    (function() {
        window._sigmaActiveDoc = null;

        /**
         * Pastikan container surat jalan adalah direct child dari document.body,
         * supaya CSS `body > *:not(#sigma-printable-surat-jalan)` bekerja benar saat print.
         */
        function ensureContainerInBody() {
            const el = document.getElementById('sigma-printable-surat-jalan');
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
            return el;
        }

        if (!window._sigmaPrintLifecycleInstalled) {
            window._sigmaPrintLifecycleInstalled = true;
            document.addEventListener('turbo:load', ensureContainerInBody);

            window.addEventListener('beforeprint', function() {
                ensureContainerInBody();
                if (window._sigmaActiveDoc) {
                    const el = document.getElementById('sigma-printable-surat-jalan');
                    if (el && !el.innerHTML.trim()) {
                        el.innerHTML = window.buildSuratJalanHtml(window._sigmaActiveDoc);
                    }
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', ensureContainerInBody, {
                once: true
            });
        } else if (!document.documentElement.hasAttribute('aria-busy')) {
            ensureContainerInBody();
        }

        // =========================================================================
        // BUILD HTML SURAT JALAN — dipanggil dengan objek doc dinamis
        // =========================================================================
        window.buildSuratJalanHtml = function(doc) {
            if (!doc) {
                return '';
            }

            const rows = (doc.items || []).map(function(item, idx) {
                return '<tr style="' + (idx % 2 === 1 ? 'background:#F8FAFC;' : '') +
                    ' border-bottom:1pt solid #CBD5E1;">' +
                    '<td style="text-align:center;padding:6pt 8pt;border:1pt solid #CBD5E1;vertical-align:middle;">' +
                    (item.no || (idx + 1)) + '</td>' +
                    '<td style="padding:6pt 8pt;border:1pt solid #CBD5E1;vertical-align:middle;font-weight:bold;font-size:8pt;">' +
                    (item.kode || '—') + '</td>' +
                    '<td style="padding:6pt 8pt;border:1pt solid #CBD5E1;vertical-align:middle;font-weight:600;">' +
                    (item.nama || '—') + '</td>' +
                    '<td style="padding:6pt 8pt;border:1pt solid #CBD5E1;vertical-align:middle;">' + (
                        item.kategori || '—') + '</td>' +
                    '<td style="text-align:center;padding:6pt 8pt;border:1pt solid #CBD5E1;vertical-align:middle;">' +
                    (item.satuan || '—') + '</td>' +
                    '<td style="text-align:center;padding:6pt 8pt;border:1pt solid #CBD5E1;vertical-align:middle;font-weight:bold;">' +
                    (item.qty || 0).toLocaleString('id-ID') + '</td>' +
                    '</tr>';
            }).join('');

            const kepadaTd = doc.showKepada ?
                '<td style="vertical-align:top;width:50%;font-size:8.5pt;">' +
                '<div style="margin-bottom:3pt;"><span style="color:#64748B;display:inline-block;width:95pt;">Kepada</span>: <strong>' +
                (doc.tujuan || '—') + '</strong></div>' +
                '<div style="margin-bottom:3pt;"><span style="color:#64748B;display:inline-block;width:95pt;">Pemohon / Penerima</span>: <strong>' +
                (doc.pemohon || '—') + '</strong></div>' +
                '</td>' :
                '';

            const pemohonRow = !doc.showKepada ?
                '<div style="margin-bottom:3pt;"><span style="color:#64748B;display:inline-block;width:95pt;">Pemohon</span>: <strong>' +
                (doc.pemohon || '—') + '</strong></div>' :
                '';

            const reqType = doc.nomorPr ?
                '<span style="color:#64748B;">No. Req: </span><strong>' + doc.nomorPr + '</strong>' :
                '<span style="color:#64748B;">Jenis: </span><strong>Pengeluaran Langsung</strong>';

            const catatanHtml = doc.catatan ?
                '<strong>Catatan:</strong><br>' + doc.catatan :
                '<span style="color:#94A3B8;font-style:italic;">Tidak ada catatan tambahan.</span>';

            const totalQty = doc.totalQty || (doc.items ? doc.items.reduce(function(a, it) {
                return a + (it.qty || 0);
            }, 0) : 0);
            const totalJenis = doc.totalJenis || (doc.items ? doc.items.length : 0);

            return ''
                // HEADER
                +
                '<table style="margin-bottom:14pt;width:100%;border-collapse:collapse;">' +
                '<tr>' +
                '<td style="vertical-align:top;width:55%;">' +
                '<div style="font-size:18pt;font-weight:bold;letter-spacing:0.5pt;line-height:1.1;">SURAT JALAN</div>' +
                '<div style="font-size:7.5pt;color:#64748B;margin-top:3pt;">Gudang Store &middot; SIGMA Warehouse System</div>' +
                '</td>' +
                '<td style="vertical-align:top;text-align:right;width:45%;font-size:8.5pt;line-height:1.6;">' +
                '<span style="color:#64748B;">No. Out: </span><strong>' + (doc.nomorKeluar || '—') +
                '</strong><br>' +
                '<span style="color:#64748B;">Tanggal: </span><strong>' + (doc.waktu || '—') + '</strong><br>' +
                reqType +
                '</td>' +
                '</tr>' +
                '</table>'

                // DARI & KEPADA
                +
                '<table style="margin-bottom:14pt;width:100%;border-collapse:collapse;">' +
                '<tr>' +
                '<td style="vertical-align:top;font-size:8.5pt;width:' + (doc.showKepada ? '50%' : '100%') +
                ';">' +
                '<div style="margin-bottom:3pt;"><span style="color:#64748B;display:inline-block;width:95pt;">Dari</span>: <strong>Gudang Store</strong></div>' +
                '<div style="margin-bottom:3pt;"><span style="color:#64748B;display:inline-block;width:95pt;">Petugas</span>: <strong>' +
                (doc.petugas || 'Admin Store') + '</strong></div>' +
                pemohonRow +
                '</td>' +
                kepadaTd +
                '</tr>' +
                '</table>'

                // TABEL BARANG
                +
                '<table style="font-size:8.5pt;margin-bottom:12pt;width:100%;border-collapse:collapse;">' +
                '<thead>' +
                '<tr style="background:#F1F5F9;">' +
                '<th style="width:5%;text-align:center;padding:6pt 8pt;border:1pt solid #CBD5E1;font-size:7.5pt;font-weight:bold;text-transform:uppercase;">No</th>' +
                '<th style="width:14%;text-align:left;padding:6pt 8pt;border:1pt solid #CBD5E1;font-size:7.5pt;font-weight:bold;text-transform:uppercase;">Kode</th>' +
                '<th style="width:36%;text-align:left;padding:6pt 8pt;border:1pt solid #CBD5E1;font-size:7.5pt;font-weight:bold;text-transform:uppercase;">Nama Barang</th>' +
                '<th style="width:20%;text-align:left;padding:6pt 8pt;border:1pt solid #CBD5E1;font-size:7.5pt;font-weight:bold;text-transform:uppercase;">Kategori</th>' +
                '<th style="width:11%;text-align:center;padding:6pt 8pt;border:1pt solid #CBD5E1;font-size:7.5pt;font-weight:bold;text-transform:uppercase;">Satuan</th>' +
                '<th style="width:14%;text-align:center;padding:6pt 8pt;border:1pt solid #CBD5E1;font-size:7.5pt;font-weight:bold;text-transform:uppercase;">Jumlah</th>' +
                '</tr>' +
                '</thead>' +
                '<tbody>' + rows + '</tbody>' +
                '</table>'

                // FOOTER — catatan + total
                +
                '<table style="margin-bottom:20pt;width:100%;border-collapse:collapse;">' +
                '<tr>' +
                '<td style="vertical-align:top;width:55%;font-size:8pt;">' + catatanHtml + '</td>' +
                '<td style="vertical-align:top;text-align:right;width:45%;font-size:8pt;">' +
                'Total Jenis Barang: <b>' + totalJenis + ' Jenis</b><br>' +
                'Total Kuantitas: <b>' + totalQty.toLocaleString('id-ID') + '</b>' +
                '</td>' +
                '</tr>' +
                '</table>'

                // TANDA TANGAN
                +
                '<table style="margin-top:14pt;width:100%;border-collapse:collapse;">' +
                '<tr>' +
                '<td style="width:50%;vertical-align:top;font-size:8.5pt;text-align:center;padding:0 15pt;">' +
                '<div style="font-weight:600;margin-bottom:45pt;">Pengirim (Petugas Store),</div>' +
                '<span style="border-top:1pt solid #94A3B8;padding-top:4pt;font-size:8pt;display:inline-block;width:160pt;">' +
                (doc.petugas || 'Admin Store') + '</span>' +
                '</td>' +
                '<td style="width:50%;vertical-align:top;font-size:8.5pt;text-align:center;padding:0 15pt;">' +
                '<div style="font-weight:600;margin-bottom:45pt;">Penerima / Pemohon,</div>' +
                '<span style="border-top:1pt solid #94A3B8;padding-top:4pt;font-size:8pt;display:inline-block;width:160pt;">' +
                (doc.pemohon || '—') + '</span>' +
                '</td>' +
                '</tr>' +
                '</table>';
        };

        // =========================================================================
        // CETAK LANGSUNG — isi container lalu window.print()
        // =========================================================================
        window.printSuratJalanDirect = function(doc) {
            if (!doc) {
                return;
            }
            window._sigmaActiveDoc = doc;

            const container = ensureContainerInBody();
            if (container) {
                container.innerHTML = window.buildSuratJalanHtml(doc);
            }

            // Beri waktu browser me-render konten sebelum print dialog dibuka
            setTimeout(function() {
                window.focus();
                window.print();
            }, 120);
        };

    })();
</script>
