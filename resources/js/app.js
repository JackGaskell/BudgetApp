import './bootstrap';

import Alpine from 'alpinejs';

Alpine.data('budgetShell', ({ addOpen, addTab }) => ({
    addOpen,
    addTab,
    mobileNavOpen: false,
    init() {
        this.$watch('addOpen', (value) => {
            document.documentElement.classList.toggle('overflow-hidden', value);
        });

        window.addEventListener('keydown', (e) => {
            if (!e || e.defaultPrevented) {
                return;
            }
            const el = e.target;
            if (
                ! el
                || el.tagName === 'INPUT'
                || el.tagName === 'TEXTAREA'
                || el.tagName === 'SELECT'
                || el.isContentEditable
            ) {
                return;
            }
            if (e.key !== 'n' && e.key !== 'N') {
                return;
            }
            if (e.metaKey || e.ctrlKey || e.altKey) {
                return;
            }
            e.preventDefault();
            this.openAdd('expense');
        });
    },
    openAdd(tab = 'expense') {
        this.addTab = tab;
        this.addOpen = true;
        this.mobileNavOpen = false;
        this.$nextTick(() => {
            const panel = this.$refs.addTransactionPanel;
            if (!panel) {
                return;
            }
            const id = this.addTab === 'expense' ? '#modal_add_expense_name' : '#modal_add_income_name';
            panel.querySelector(id)?.focus();
        });
    },
    closeAdd() {
        this.addOpen = false;
    },
    setAddTab(tab) {
        this.addTab = tab;
        this.$nextTick(() => {
            const panel = this.$refs.addTransactionPanel;
            if (!panel) {
                return;
            }
            const id = tab === 'expense' ? '#modal_add_expense_name' : '#modal_add_income_name';
            panel.querySelector(id)?.focus();
        });
    },
}));

window.Alpine = Alpine;

Alpine.start();
