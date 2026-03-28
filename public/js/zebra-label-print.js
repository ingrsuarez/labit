/**
 * Zebra Browser Print - Label Printing Module
 *
 * Requires Zebra Browser Print application installed locally.
 * https://www.zebra.com/us/en/support-downloads/printer-software/browser-print.html
 */
const ZebraLabelPrint = {
    API_URL: null,
    selectedPrinter: null,
    printers: [],
    _defaultDevice: null,

    _getCandidateUrls() {
        if (window.location.protocol === 'https:') {
            return [
                'https://localhost:9101',
                'http://localhost:9100',
            ];
        }
        return [
            'http://localhost:9100',
            'https://localhost:9101',
        ];
    },

    async _fetchWithTimeout(url, options, ms) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), ms || 3000);
        try {
            const response = await fetch(url, { ...options, signal: controller.signal });
            clearTimeout(timeout);
            return response;
        } catch (e) {
            clearTimeout(timeout);
            throw e;
        }
    },

    async getAvailablePrinters() {
        const urls = this._getCandidateUrls();
        let text = null;

        for (const url of urls) {
            try {
                const resp = await this._fetchWithTimeout(
                    `${url}/available?type=printer`, {}, 3000
                );
                if (!resp.ok) continue;
                text = await resp.text();
                this.API_URL = url;
                break;
            } catch {
                continue;
            }
        }

        if (!text) {
            throw new Error(
                'No se detectó Zebra Browser Print. ' +
                'Asegúrese de que esté instalado y ejecutándose.'
            );
        }

        try {
            const defResp = await this._fetchWithTimeout(
                `${this.API_URL}/default?type=printer`, {}, 3000
            );
            if (defResp.ok) {
                const defText = await defResp.text();
                try {
                    this._defaultDevice = JSON.parse(defText);
                } catch { /* not JSON, ignore */ }
            }
        } catch { /* /default not available */ }

        let printers;
        try {
            const parsed = JSON.parse(text);
            const list = Array.isArray(parsed) ? parsed : [parsed];
            printers = list.map(p => ({
                name: p.name || p.uid || 'Impresora Zebra',
                uid: p.uid || p.name || '',
                connection: p.connection || 'unknown',
                deviceType: p.deviceType || 'printer',
                _raw: p,
            }));
        } catch {
            printers = this._parsePrintersFromText(text);
        }

        this.printers = printers;

        const saved = localStorage.getItem('zebra_selected_printer');
        if (saved && this.printers.find(p => p.name === saved)) {
            this.selectedPrinter = this.printers.find(p => p.name === saved);
        } else if (this.printers.length > 0) {
            this.selectedPrinter = this.printers[0];
        }

        return this.printers;
    },

    _parsePrintersFromText(responseText) {
        const printers = [];
        if (!responseText || responseText.trim() === '') return printers;

        const lines = responseText.split('\n').filter(l => l.trim());
        for (const line of lines) {
            const parts = line.split('\t');
            printers.push({
                name: parts[0] || line.trim(),
                uid: parts[1] || parts[0] || line.trim(),
                connection: parts[2] || 'unknown',
                deviceType: parts[3] || 'printer',
            });
        }
        return printers;
    },

    selectPrinter(printerName) {
        this.selectedPrinter = this.printers.find(p => p.name === printerName) || null;
        if (this.selectedPrinter) {
            localStorage.setItem('zebra_selected_printer', printerName);
        }
    },

    generateZPL(data) {
        const customerName = this._sanitizeZPL(data.customer_name).substring(0, 30);
        const protocol = this._sanitizeZPL(data.protocol_number);
        const materials = this._sanitizeZPL(data.materials);
        const entryDate = this._sanitizeZPL(data.entry_date);
        const sampleType = this._sanitizeZPL(data.sample_type);

        return [
            '^XA',
            '^CI28',
            '^PW400',
            '^LL200',
            '^FO40,10^BY2,2,50^BCN,50,Y,N,N^FD' + protocol + '^FS',
            '^FO30,85^A0N,20,20^FD' + customerName + '^FS',
            '^FO30,110^A0N,18,18^FD' + sampleType + ' | MAT: ' + materials + '^FS',
            '^FO30,135^A0N,18,18^FD' + entryDate + '^FS',
            '^PQ1',
            '^XZ',
        ].join('\n');
    },

    _sanitizeZPL(text) {
        if (!text) return '';
        return text.replace(/[\^~]/g, '').replace(/[^\x20-\x7E\u00C0-\u024F]/g, '');
    },

    _getDeviceForWrite() {
        if (this._defaultDevice) return this._defaultDevice;
        if (this.selectedPrinter && this.selectedPrinter._raw) return this.selectedPrinter._raw;
        return {
            name: this.selectedPrinter.name,
            uid: this.selectedPrinter.uid,
            connection: this.selectedPrinter.connection,
            deviceType: this.selectedPrinter.deviceType,
        };
    },

    async printLabel(zplContent) {
        if (!this.selectedPrinter) {
            throw new Error('No hay impresora seleccionada');
        }

        const device = this._getDeviceForWrite();
        const strategies = [
            {
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify({ device, data: zplContent }),
            },
            {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'device=' + encodeURIComponent(JSON.stringify(device)) +
                      '&data=' + encodeURIComponent(zplContent),
            },
            {
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: zplContent,
            },
        ];

        let lastError = '';
        for (const strategy of strategies) {
            try {
                const response = await this._fetchWithTimeout(
                    `${this.API_URL}/write`,
                    { method: 'POST', headers: strategy.headers, body: strategy.body },
                    5000
                );
                if (response.ok) return true;
                lastError = await response.text().catch(() => '');
            } catch (e) {
                lastError = e.message;
            }
        }

        throw new Error('Error al enviar a la impresora: ' + lastError);
    },

    async printSampleLabel(labelDataUrl, copies = 1) {
        const response = await fetch(labelDataUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) throw new Error('No se pudieron obtener los datos de la muestra');

        const data = await response.json();
        let zpl = this.generateZPL(data);

        if (copies > 1) {
            zpl = zpl.replace('^PQ1', '^PQ' + copies);
        }

        return await this.printLabel(zpl);
    },
};

/**
 * Alpine.js component for the label printing modal
 */
function zebraPrintModal() {
    return {
        open: false,
        loading: false,
        printing: false,
        error: null,
        success: false,
        printers: [],
        selectedPrinter: '',
        copies: 1,
        zebraAvailable: false,
        labelDataUrl: '',

        async init() {
            const saved = localStorage.getItem('zebra_selected_printer');
            if (saved) this.selectedPrinter = saved;
        },

        async openModal(labelDataUrl) {
            this.labelDataUrl = labelDataUrl;
            this.open = true;
            this.error = null;
            this.success = false;
            this.loading = true;

            try {
                this.printers = await ZebraLabelPrint.getAvailablePrinters();
                this.zebraAvailable = true;
                if (ZebraLabelPrint.selectedPrinter) {
                    this.selectedPrinter = ZebraLabelPrint.selectedPrinter.name;
                }
                if (this.printers.length === 1) {
                    this.selectedPrinter = this.printers[0].name;
                    this.onPrinterChange();
                }
            } catch (e) {
                this.zebraAvailable = false;
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        closeModal() {
            this.open = false;
            this.error = null;
            this.success = false;
        },

        onPrinterChange() {
            ZebraLabelPrint.selectPrinter(this.selectedPrinter);
        },

        async print() {
            if (!this.selectedPrinter) {
                this.error = 'Seleccione una impresora';
                return;
            }

            this.printing = true;
            this.error = null;
            this.success = false;

            try {
                ZebraLabelPrint.selectPrinter(this.selectedPrinter);
                await ZebraLabelPrint.printSampleLabel(this.labelDataUrl, this.copies);
                this.success = true;
                setTimeout(() => { this.success = false; }, 3000);
            } catch (e) {
                if (e.message.includes('Error al enviar')) {
                    this.error = 'No se pudo enviar a la impresora. Verificá que esté encendida y conectada.';
                } else {
                    this.error = e.message;
                }
            } finally {
                this.printing = false;
            }
        },
    };
}
