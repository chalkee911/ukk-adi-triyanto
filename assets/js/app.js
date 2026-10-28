(function () {
    'use strict';

    var PDF_ERROR = 'Library PDF gagal dimuat, periksa koneksi internet.';

    document.querySelectorAll('.flash-success').forEach(function (flash) {
        window.setTimeout(function () {
            flash.classList.add('flash-hidden');
            window.setTimeout(function () {
                flash.remove();
            }, 250);
        }, 6000);
    });

    document.querySelectorAll('form[method="post"]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('button[type="submit"]');

            if (button) {
                button.disabled = true;
                button.classList.add('is-loading');
            }
        });
    });

    var deleteModalElement = document.getElementById('deleteModal');

    if (deleteModalElement && window.bootstrap) {
        var deleteModal = new window.bootstrap.Modal(deleteModalElement);
        var deleteName = document.getElementById('deleteName');
        var deleteId = document.getElementById('deleteId');

        document.querySelectorAll('[data-delete-id]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (deleteName) {
                    deleteName.textContent = button.getAttribute('data-delete-name') || 'Barang ini';
                }

                if (deleteId) {
                    deleteId.value = button.getAttribute('data-delete-id') || '';
                }

                deleteModal.show();
            });
        });
    }

    var itemSelect = document.getElementById('itemSelect');

    if (itemSelect) {
        var stockInfo = document.getElementById('stockInfo');
        var stockWarning = document.getElementById('stockWarning');
        var quantityInput = document.getElementById('quantity');

        var updateStockInfo = function () {
            var option = itemSelect.options[itemSelect.selectedIndex];

            if (!option || option.value === '') {
                if (stockInfo) {
                    stockInfo.textContent = 'Pilih barang untuk melihat stok tersedia.';
                    stockInfo.classList.remove('is-low', 'is-empty');
                }

                if (stockWarning) {
                    stockWarning.hidden = true;
                }

                return;
            }

            var stock = Number(option.getAttribute('data-stock') || 0);
            var unit = option.getAttribute('data-unit') || '';

            if (stockInfo) {
                stockInfo.textContent = 'Stok tersedia: ' + stock.toLocaleString('id-ID')
                    + (unit ? ' ' + unit : '');
                stockInfo.classList.toggle('is-empty', stock === 0);
                stockInfo.classList.toggle('is-low', stock > 0 && stock <= 5);
            }

            updateQuantityWarning(stock);
        };

        var updateQuantityWarning = function (stock) {
            if (!stockWarning || !quantityInput) {
                return;
            }

            var quantity = Number(quantityInput.value);

            stockWarning.hidden = !(
                quantityInput.value !== '' &&
                !Number.isNaN(quantity) &&
                quantity > stock
            );
        };

        itemSelect.addEventListener('change', updateStockInfo);

        if (quantityInput) {
            quantityInput.addEventListener('input', function () {
                var option = itemSelect.options[itemSelect.selectedIndex];
                var stock = option ? Number(option.getAttribute('data-stock') || 0) : 0;
                updateQuantityWarning(stock);
            });
        }

        updateStockInfo();
    }

    var pdfButton = document.getElementById('pdfBtn');
    var trxData = document.getElementById('trx-data');

    if (pdfButton && trxData) {
        pdfButton.addEventListener('click', function () {
            if (pdfButton.disabled) {
                return;
            }

            var rows = [];

            try {
                rows = JSON.parse(trxData.textContent || '[]');
            } catch (error) {
                rows = [];
            }

            if (!Array.isArray(rows) || rows.length === 0) {
                return;
            }

            if (!window.jspdf || typeof window.jspdf.jsPDF !== 'function') {
                window.alert(PDF_ERROR);
                return;
            }

            var jsPDF = window.jspdf.jsPDF;
            var doc;

            try {
                doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            } catch (error) {
                window.alert(PDF_ERROR);
                return;
            }

            if (typeof doc.autoTable !== 'function') {
                window.alert(PDF_ERROR);
                return;
            }

            var formatDate = function (value) {
                var parts = String(value || '').split('-');

                if (parts.length === 3) {
                    return parts[2] + '/' + parts[1] + '/' + parts[0];
                }

                return String(value || '');
            };

            var formatNumber = function (value) {
                return Number(value || 0).toLocaleString('id-ID');
            };

            var totalIn = 0;
            var totalOut = 0;

            rows.forEach(function (row) {
                var quantity = Number(row.quantity || 0);

                if (row.type === 'IN') {
                    totalIn += quantity;
                } else if (row.type === 'OUT') {
                    totalOut += quantity;
                }
            });

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(16);
            doc.setTextColor(37, 48, 63);
            doc.text('Riwayat Transaksi Stok', 14, 17);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.setTextColor(90, 102, 120);
            doc.text('Stokara - Sistem Inventaris Barang', 14, 23);

            var filterLines = doc.splitTextToSize(
                pdfButton.getAttribute('data-filter-info') || 'Jenis: Semua jenis | Tanggal: semua',
                265
            );
            doc.text(filterLines, 14, 30);

            var printedAt = new Date();
            var printedText = 'Dicetak: '
                + String(printedAt.getDate()).padStart(2, '0') + '/'
                + String(printedAt.getMonth() + 1).padStart(2, '0') + '/'
                + printedAt.getFullYear() + ' '
                + String(printedAt.getHours()).padStart(2, '0') + ':'
                + String(printedAt.getMinutes()).padStart(2, '0');

            var infoY = 30 + (filterLines.length * 4.5);
            doc.text(printedText, 14, infoY + 2);

            doc.autoTable({
                startY: infoY + 8,
                head: [[
                    'Tanggal',
                    'No. Transaksi',
                    'Kode',
                    'Nama Barang',
                    'Jenis',
                    'Jumlah',
                    'Supplier/Tujuan'
                ]],
                body: rows.map(function (row) {
                    var isIn = row.type === 'IN';
                    var quantity = Number(row.quantity || 0);

                    return [
                        formatDate(row.trx_date),
                        String(row.trx_number || ''),
                        String(row.code || ''),
                        String(row.name || ''),
                        isIn ? 'Masuk' : 'Keluar',
                        (isIn ? '+' : '-') + formatNumber(quantity) + (row.unit ? ' ' + row.unit : ''),
                        String(row.party || '')
                    ];
                }),
                styles: {
                    font: 'helvetica',
                    fontSize: 8,
                    cellPadding: 2.5,
                    textColor: [37, 48, 63],
                    lineColor: [214, 222, 232],
                    lineWidth: 0.15
                },
                headStyles: {
                    fillColor: [74, 109, 148],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [244, 247, 250]
                },
                columnStyles: {
                    0: { cellWidth: 22 },
                    1: { cellWidth: 40 },
                    2: { cellWidth: 24 },
                    3: { cellWidth: 55 },
                    4: { cellWidth: 18 },
                    5: { cellWidth: 25 },
                    6: { cellWidth: 'auto' }
                },
                margin: { left: 14, right: 14, top: 14, bottom: 16 }
            });

            var finalY = doc.lastAutoTable.finalY + 9;

            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(63, 107, 88);
            doc.text('Total masuk: +' + formatNumber(totalIn), 14, finalY);
            doc.setTextColor(140, 59, 59);
            doc.text('Total keluar: -' + formatNumber(totalOut), 14, finalY + 6);

            var pageCount = doc.getNumberOfPages();

            for (var page = 1; page <= pageCount; page += 1) {
                doc.setPage(page);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(8);
                doc.setTextColor(120, 130, 145);
                doc.text(
                    'Halaman ' + page + ' dari ' + pageCount,
                    doc.internal.pageSize.getWidth() - 14,
                    doc.internal.pageSize.getHeight() - 8,
                    { align: 'right' }
                );
            }

            var stamp = String(printedAt.getFullYear())
                + String(printedAt.getMonth() + 1).padStart(2, '0')
                + String(printedAt.getDate()).padStart(2, '0');

            doc.save('riwayat-transaksi-' + stamp + '.pdf');
        });
    }
})();
