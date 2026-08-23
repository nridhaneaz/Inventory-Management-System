export const HALF_A4_PRODUCT_LIMIT = 20;

const baseCashMemoPrintStyles = `
* { box-sizing: border-box; }
html, body { width: 100%; margin: 0; padding: 0; background: #fff; }
body { color: #111; font-family: "Noto Sans Bengali", "Segoe UI", sans-serif; }
.cash-memo-print-sheet { width: 100%; padding: 0 10mm; background: #fff; }
.cash-memo-print-slot { width: 100%; padding: 2mm 0; break-inside: avoid; page-break-inside: avoid; display: flex; justify-content: center; }
.cash-memo-print-scale { width: 100%; display: flex; justify-content: center; }
.cash-memo { --memo-ink: #111; --memo-border: #202020; width: 100%; max-width: 100%; min-height: auto; margin: 0; padding: .12in; border: 1px solid var(--memo-border); background: #fff; color: var(--memo-ink); font-family: "Noto Sans Bengali", "Segoe UI", sans-serif; }
@media print {
  html, body { height: auto; }
  body { margin: 0; }
  .cash-memo-print-sheet { overflow: visible; }
  .cash-memo-print-slot, .cash-memo-print-scale { display: block; width: 100%; }
  .cash-memo {
    display: block;
    margin-top: 0;
    break-inside: avoid;
    page-break-inside: avoid;
  }
}
.cash-memo__header-top { display: grid; grid-template-columns: .62in minmax(0, 1fr) minmax(0, 1.48in); gap: .08in; align-items: start; }
.cash-memo__logo-wrap, .cash-memo__logo, .cash-memo__logo-placeholder { width: .54in; height: .54in; }
.cash-memo__logo, .cash-memo__logo-placeholder { border: 1px solid var(--memo-ink); border-radius: 50%; }
.cash-memo__logo { object-fit: cover; }
.cash-memo__logo-placeholder { display: flex; align-items: center; justify-content: center; padding: 2px; font-size: 5.5pt; font-weight: 700; line-height: 1.05; text-align: center; }
.cash-memo__brand-name { margin: 0; font-size: 17pt; font-weight: 800; letter-spacing: .03em; line-height: 1.05; }
.cash-memo__brand-desc { margin: 2px 0 0; color: #333; font-size: 7.5pt; line-height: 1.15; }
.cash-memo__owner { font-size: 7.5pt; line-height: 1.25; text-align: right; }.cash-memo__owner p { margin: 0; }.cash-memo__owner-name { font-weight: 700; }
.cash-memo__address-bar { margin-top: 3px; padding: 2px 5px; border: 1px solid var(--memo-border); font-size: 8pt; font-weight: 600; line-height: 1.15; text-align: center; }
.cash-memo__title-row { display: grid; grid-template-columns: 1fr auto 1fr; gap: 6px; align-items: center; margin: 5px 0 2px; font-size: 8pt; font-weight: 600; line-height: 1.15; }.cash-memo__memo-title { padding: 3px 12px; border: 1px solid var(--memo-border); text-align: center; }.cash-memo__memo-date { text-align: right; }
.cash-memo__payment-type { margin: 0 0 3px; font-size: 7pt; line-height: 1.15; text-align: right; }.cash-memo__customer { margin-bottom: 4px; font-size: 8pt; line-height: 1.25; }.cash-memo__customer p { margin: 0; }
.cash-memo__table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 7pt; line-height: 1.05; }.cash-memo__table th, .cash-memo__table td { padding: 1.5px 3px; border: 1px solid var(--memo-border); background: #fff; color: var(--memo-ink); vertical-align: middle; }.cash-memo__table th { font-weight: 700; text-align: center; }.cash-memo__table td { text-align: center; }.cash-memo__table th:nth-child(1) { width: 6%; }.cash-memo__table th:nth-child(2) { width: 48%; }.cash-memo__table th:nth-child(3) { width: 16%; }.cash-memo__table th:nth-child(4), .cash-memo__table th:nth-child(5) { width: 15%; }.cash-memo__table td:nth-child(2) { text-align: left; overflow-wrap: anywhere; }
.cash-memo__table tbody tr { height: .11in; }.cash-memo__summary-label { font-weight: 700; text-align: right !important; }.cash-memo__grand-total, .cash-memo__due-amount { font-weight: 800; }.cash-memo__item-note { display: block; margin-top: 1px; color: #444; font-size: 5.5pt; line-height: 1; }.cash-memo__empty { padding: 10px; color: #555; }.cash-memo__footer { margin: 3px 0 0; font-size: 10.5pt; font-weight: 800; line-height: 1; text-align: center; }
`;

export const cashMemoPrintStyles = baseCashMemoPrintStyles;

export const resolvePrintFormat = (productCount, selectedFormat = 'auto') => {
  if (selectedFormat === 'half' || selectedFormat === 'full') return selectedFormat;
  return productCount <= HALF_A4_PRODUCT_LIMIT ? 'half' : 'full';
};

const generatePrintStyles = (format, orientation) => {
  const pageSize = `A4 ${orientation === 'landscape' ? 'landscape' : 'portrait'}`;
  const halfHeight = orientation === 'landscape' ? '105mm' : '148.5mm';
  const fullPageHeight = orientation === 'landscape' ? '194mm' : '281mm';
  const formatClass = format === 'half' ? 'half-a4' : 'full-a4';
  const fullA4Styles = format === 'full' ? `\n.full-a4 .cash-memo { padding: 5mm; }\n.full-a4 .cash-memo__brand-name { font-size: 22pt; }\n.full-a4 .cash-memo__brand-desc, .full-a4 .cash-memo__owner { font-size: 9pt; }\n.full-a4 .cash-memo__address-bar { padding: 3px 6px; font-size: 10pt; }\n.full-a4 .cash-memo__title-row { margin: 8px 0 4px; font-size: 10pt; }\n.full-a4 .cash-memo__payment-type, .full-a4 .cash-memo__customer { font-size: 9pt; }\n.full-a4 .cash-memo__table { font-size: 9pt; line-height: 1.15; }\n.full-a4 .cash-memo__table th, .full-a4 .cash-memo__table td { padding: 3px 5px; }\n.full-a4 .cash-memo__table tbody tr { height: .16in; }\n.full-a4 .cash-memo__item-note { font-size: 7pt; }\n.full-a4 .cash-memo__footer { font-size: 14pt; }` : '';
  return `@page { size: ${pageSize}; margin: 8mm; }\n${baseCashMemoPrintStyles}\n.cash-memo-print-sheet { min-height: ${format === 'half' ? halfHeight : fullPageHeight}; }\n.cash-memo-print-slot { ${format === 'half' ? `min-height: ${halfHeight};` : ''} }\n.${formatClass} { width: 100%; }${fullA4Styles}`;
};

const getProductCount = (sourceMemo) => sourceMemo.querySelectorAll('.cash-memo__item-row').length;

export const printCashMemo = (elementId, options = {}) => {
    const sourceMemo = document.getElementById(elementId);
    if (!sourceMemo) return false;

  const selectedFormat = options.format || 'auto';
  const orientation = options.orientation || 'portrait';
  const productCount = getProductCount(sourceMemo);
  const resolvedFormat = resolvePrintFormat(productCount, selectedFormat);

    const printWindow = window.open('', '_blank', 'width=900,height=1100');
    if (!printWindow) return false;

    const memo = sourceMemo.cloneNode(true);
    memo.removeAttribute('id');

    const memoSlot = (markup) => `<section class="cash-memo-print-slot"><div class="cash-memo-print-scale">${markup}</div></section>`;
    memo.classList.add(resolvedFormat === 'half' ? 'half-a4' : 'full-a4');
    const printSheets = `<main class="cash-memo-print-sheet">${memoSlot(memo.outerHTML)}</main>`;

    printWindow.document.write(`
        <!doctype html><html><head><title>Cash Memo</title>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700;800&display=swap" rel="stylesheet">
      <style id="cash-memo-print-style">${generatePrintStyles(resolvedFormat, orientation)}</style></head>
        <body>${printSheets}</body></html>
    `);
    printWindow.document.close();
    let startedPrinting = false;
    const startPrint = async () => {
        if (startedPrinting) return;
        startedPrinting = true;
        await printWindow.document.fonts?.ready;
        const memoElement = printWindow.document.querySelector('.cash-memo');
        const slotElement = printWindow.document.querySelector('.cash-memo-print-slot');
        const overflowedHalfPage = resolvedFormat === 'half' && memoElement && slotElement && memoElement.scrollHeight > slotElement.clientHeight;
        if (overflowedHalfPage && selectedFormat === 'auto') {
            printWindow.document.getElementById('cash-memo-print-style').textContent = generatePrintStyles('full', orientation);
          memoElement.classList.remove('half-a4');
          memoElement.classList.add('full-a4');
            options.onAutoUpgrade?.();
        } else if (overflowedHalfPage) {
          printWindow.close();
          options.onOverflow?.();
          return;
        }
        printWindow.focus();
        printWindow.print();
    };
    printWindow.addEventListener('load', startPrint, { once: true });
    window.setTimeout(startPrint, 750);
    printWindow.onafterprint = () => printWindow.close();
    return true;
};
