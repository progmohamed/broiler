import './broiler-vaccinations';
import './farm-assistant';

const quickAddDialog = document.querySelector('[data-quick-add-dialog]');
const quickAddButton = document.querySelector('[data-quick-add-open]');

quickAddButton?.addEventListener('click', () => {
    quickAddDialog?.showModal();
});

quickAddDialog?.addEventListener('click', (event) => {
    if (event.target === quickAddDialog) {
        quickAddDialog.close();
    }
});

document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (! window.confirm(form.dataset.confirmMessage ?? 'هل أنت متأكد من الحذف؟')) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-sale-calculator]').forEach((form) => {
    const productType = form.querySelector('[data-sale-product-type]');
    const customProductRow = form.querySelector('[data-custom-product-row]');
    const customProduct = form.querySelector('[data-custom-product]');
    const quantity = form.querySelector('[data-sale-quantity]');
    const unitPrice = form.querySelector('[data-sale-unit-price]');
    const total = form.querySelector('[data-sale-total]');

    const updateProductField = () => {
        const isOther = productType?.value === 'other';
        customProductRow?.classList.toggle('hidden', ! isOther);

        if (customProduct) {
            customProduct.required = isOther;
        }
    };

    const updateTotal = () => {
        const amount = Number(quantity?.value || 0) * Number(unitPrice?.value || 0);

        if (total) {
            total.textContent = amount.toLocaleString('ar-EG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }
    };

    productType?.addEventListener('change', updateProductField);
    quantity?.addEventListener('input', updateTotal);
    unitPrice?.addEventListener('input', updateTotal);
    updateProductField();
    updateTotal();
});

document.querySelector('[data-print-page]')?.addEventListener('click', () => {
    window.print();
});

document.querySelector('[data-confirm-restore]')?.closest('form')?.addEventListener('submit', (event) => {
    if (! window.confirm('سيتم استبدال كل البيانات الحالية. هل تريد المتابعة؟')) {
        event.preventDefault();
    }
});

const openHashDetails = () => {
    const targetId = window.location.hash.slice(1);

    if (! targetId) {
        return;
    }

    const target = document.getElementById(targetId);

    if (target instanceof HTMLDetailsElement) {
        target.open = true;
    }
};

window.addEventListener('hashchange', openHashDetails);
openHashDetails();

const reportTabs = Array.from(document.querySelectorAll('[data-report-tab]'));
const reportPanels = Array.from(document.querySelectorAll('[data-report-panel]'));

const selectReport = (reportId, updateHash = false) => {
    if (! reportPanels.some((panel) => panel.id === reportId)) {
        return;
    }

    reportTabs.forEach((tab) => {
        tab.setAttribute('aria-selected', String(tab.dataset.reportTab === reportId));
    });

    reportPanels.forEach((panel) => {
        panel.hidden = panel.id !== reportId;
    });

    if (updateHash) {
        window.history.replaceState(null, '', `#${reportId}`);
    }
};

if (reportTabs.length > 0) {
    const requestedReport = window.location.hash.slice(1);
    const initialReport = reportPanels.some((panel) => panel.id === requestedReport)
        ? requestedReport
        : reportTabs[0].dataset.reportTab;

    selectReport(initialReport);

    reportTabs.forEach((tab) => {
        tab.addEventListener('click', () => selectReport(tab.dataset.reportTab, true));
    });

    window.addEventListener('hashchange', () => selectReport(window.location.hash.slice(1)));
}
