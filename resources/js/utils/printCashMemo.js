export const cashMemoPrintStyles = `
@page { size: A4; margin: 8mm; }
* { box-sizing: border-box; }
html, body { width: 8.27in; margin: 0; padding: 0; background: #fff; }
body { color: #111; font-family: "Noto Sans Bengali", "Segoe UI", sans-serif; }
.cash-memo-print-sheet { width: 8.27in; min-height: 11.69in; background: #fff; }
.cash-memo-print-slot { width: 8.27in; min-height: 11.69in; page-break-after: always; break-after: page; }
.cash-memo-print-slot:last-child { page-break-after: auto; break-after: auto; }
.cash-memo-print-scale { width: 8.27in; }
.cash-memo { --memo-ink: #111; --memo-border: #202020; width: 8.27in; min-height: 11.69in; margin: 0; padding: .12in; border: 1px solid var(--memo-border); background: #fff; color: var(--memo-ink); font-family: "Noto Sans Bengali", "Segoe UI", sans-serif; }
.cash-memo__header-top { display: grid; grid-template-columns: .62in 1fr 1.48in; gap: .08in; align-items: start; }
.cash-memo__logo-wrap, .cash-memo__logo, .cash-memo__logo-placeholder { width: .54in; height: .54in; }
.cash-memo__logo, .cash-memo__logo-placeholder { border: 1px solid var(--memo-ink); border-radius: 50%; }
.cash-memo__logo { object-fit: cover; }
.cash-memo__logo-placeholder { display: flex; align-items: center; justify-content: center; padding: 2px; font-size: 5.5pt; font-weight: 700; line-height: 1.05; text-align: center; }
.cash-memo__brand-name { margin: 0; font-size: 16pt; font-weight: 800; letter-spacing: .03em; line-height: 1.05; }
.cash-memo__brand-desc { margin: 2px 0 0; color: #333; font-size: 7pt; line-height: 1.15; }
.cash-memo__owner { font-size: 7pt; line-height: 1.25; text-align: right; }.cash-memo__owner p { margin: 0; }.cash-memo__owner-name { font-weight: 700; }
.cash-memo__address-bar { margin-top: 3px; padding: 2px 5px; border: 1px solid var(--memo-border); font-size: 8pt; font-weight: 600; line-height: 1.15; text-align: center; }
.cash-memo__title-row { display: grid; grid-template-columns: 1fr auto 1fr; gap: 6px; align-items: center; margin: 5px 0 2px; font-size: 8pt; font-weight: 600; line-height: 1.15; }.cash-memo__memo-title { padding: 3px 12px; border: 1px solid var(--memo-border); text-align: center; }.cash-memo__memo-date { text-align: right; }
.cash-memo__payment-type { margin: 0 0 3px; font-size: 7pt; line-height: 1.15; text-align: right; }.cash-memo__customer { margin-bottom: 4px; font-size: 8pt; line-height: 1.25; }.cash-memo__customer p { margin: 0; }
.cash-memo__table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 7.25pt; line-height: 1.1; }.cash-memo__table th, .cash-memo__table td { padding: 2px 4px; border: 1px solid var(--memo-border); background: #fff; color: var(--memo-ink); vertical-align: middle; }.cash-memo__table th { font-weight: 700; text-align: center; }.cash-memo__table td { text-align: center; }.cash-memo__table th:nth-child(1) { width: 6%; }.cash-memo__table th:nth-child(2) { width: 48%; }.cash-memo__table th:nth-child(3) { width: 16%; }.cash-memo__table th:nth-child(4), .cash-memo__table th:nth-child(5) { width: 15%; }.cash-memo__table td:nth-child(2) { text-align: left; overflow-wrap: anywhere; }
.cash-memo__table tbody tr { height: .18in; }.cash-memo__summary-label { font-weight: 700; text-align: right !important; }.cash-memo__grand-total, .cash-memo__due-amount { font-weight: 800; }.cash-memo__item-note { display: block; margin-top: 1px; color: #444; font-size: 5.75pt; line-height: 1.05; }.cash-memo__empty { padding: 10px; color: #555; }.cash-memo__footer { margin: 4px 0 0; font-size: 12pt; font-weight: 800; line-height: 1.1; text-align: center; }
`;

export const printCashMemo = (elementId) => {
    const sourceMemo = document.getElementById(elementId);
    if (!sourceMemo) return false;

    const printWindow = window.open('', '_blank', 'width=900,height=1100');
    if (!printWindow) return false;

    const itemRows = Array.from(sourceMemo.querySelectorAll('.cash-memo__item-row'));
    const itemsPerPage = 30;
    const itemGroups = itemRows.length
        ? Array.from({ length: Math.ceil(itemRows.length / itemsPerPage) }, (_, index) => itemRows.slice(index * itemsPerPage, index * itemsPerPage + itemsPerPage))
        : [[]];

    const memoMarkup = (rows, showSummary, isContinuation) => {
        const memo = sourceMemo.cloneNode(true);
        memo.removeAttribute('id');

        const tableBody = memo.querySelector('.cash-memo__table tbody');
        tableBody.replaceChildren(...rows.map((row) => row.cloneNode(true)));

        const tableFooter = memo.querySelector('.cash-memo__table tfoot');
        tableFooter.style.display = showSummary ? '' : 'none';

        if (isContinuation) {
            const title = memo.querySelector('.cash-memo__memo-title');
            title.textContent = `${title.textContent} (চলমান)`;
        }

        return memo.outerHTML;
    };
    const memoSlot = (markup) => `<section class="cash-memo-print-slot"><div class="cash-memo-print-scale">${markup}</div></section>`;
    const memoSections = itemGroups.map((rows, index) => memoSlot(memoMarkup(rows, index === itemGroups.length - 1, index > 0)));
    const printSheets = memoSections.map((section) => `<main class="cash-memo-print-sheet">${section}</main>`).join('');

    printWindow.document.write(`
        <!doctype html><html><head><title>Cash Memo</title>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>${cashMemoPrintStyles}</style></head>
        <body>${printSheets}</body></html>
    `);
    printWindow.document.close();
    let startedPrinting = false;
    const startPrint = async () => {
        if (startedPrinting) return;
        startedPrinting = true;
        await printWindow.document.fonts?.ready;
        printWindow.focus();
        printWindow.print();
    };
    printWindow.addEventListener('load', startPrint, { once: true });
    window.setTimeout(startPrint, 750);
    printWindow.onafterprint = () => printWindow.close();
    return true;
};
