import { Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

export function registerSantaCruzSyncAlpine() {
    document.addEventListener('alpine:init', () => {
        Alpine.data('santaCruzSync', (cfg) => ({
            insuranceId: cfg.insuranceId,
            searchTestsUrl: cfg.searchTestsUrl,
            modalOpen: false,
            mapCode: '',
            mapName: '',
            searchQ: '',
            searchResults: [],
            selectedTestId: null,
            selectedTestLabel: '',
            openMappingModal(code, name) {
                this.mapCode = code;
                this.mapName = name || '';
                this.searchQ = '';
                this.searchResults = [];
                this.selectedTestId = null;
                this.selectedTestLabel = '';
                this.modalOpen = true;
            },
            pickTest(t) {
                this.selectedTestId = t.id;
                this.selectedTestLabel = `${t.code} — ${t.name}`;
            },
            async searchTests() {
                if (!this.searchQ || this.searchQ.length < 2) {
                    this.searchResults = [];
                    return;
                }
                let url = `${this.searchTestsUrl}?q=${encodeURIComponent(this.searchQ)}`;
                if (this.insuranceId) {
                    url += `&insurance_id=${encodeURIComponent(this.insuranceId)}`;
                }
                const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
                if (!r.ok) {
                    return;
                }
                const data = await r.json();
                this.searchResults = Array.isArray(data) ? data : [];
            },
        }));
    });
}
