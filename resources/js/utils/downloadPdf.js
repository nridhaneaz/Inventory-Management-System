import { jsPDF } from 'jspdf';
import html2canvas from 'html2canvas';

export const downloadInvoicePdf = async (elementId, filename = 'invoice.pdf') => {
    const element = document.getElementById(elementId);
    if (!element) return;

    const canvas = await html2canvas(element, {
        scale: 2,
        useCORS: true,
        logging: false,
    });

    const imgData = canvas.toDataURL('image/png');
    const pdf = new jsPDF('p', 'mm', 'a4');
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);

    const pdfBlob = pdf.output('blob');
    const blobUrl = URL.createObjectURL(pdfBlob);
    const pdfWindow = window.open(blobUrl, '_blank');

    if (!pdfWindow) {
        pdf.save(filename);
        return;
    }

    setTimeout(() => URL.revokeObjectURL(blobUrl), 15000);
};
