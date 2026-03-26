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

    async _tryPort(baseUrl) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 3000);
        try {
            const response = await fetch(`${baseUrl}/available?type=printer`, {
                signal: controller.signal,
            });
            clearTimeout(timeout);
            if (!response.ok) throw new Error('Bad response');
            return await response.text();
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
                text = await this._tryPort(url);
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

        let printers;
        try {
            const parsed = JSON.parse(text);
            printers = Array.isArray(parsed) ? parsed : [parsed];
            printers = printers.map(p => ({
                name: p.name || 'Impresora Zebra',
                uid: p.uid || '',
                connection: p.connection || 'unknown',
                deviceType: p.deviceType || 'printer',
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
            '^PW480',
            '^LL240',
            '^FO30,15^BY2,2,70^BCN,70,Y,N,N^FD' + protocol + '^FS',
            '^FO30,115^A0N,24,24^FD' + customerName + '^FS',
            '^FO30,145^A0N,20,20^FD' + sampleType + ' | MAT: ' + materials + '^FS',
            '^FO30,175^A0N,20,20^FD' + entryDate + '^FS',
            '^PQ1',
            '^XZ',
        ].join('\n');
    },

    _sanitizeZPL(text) {
        if (!text) return '';
        return text.replace(/[\^~]/g, '').replace(/[^\x20-\x7E\u00C0-\u024F]/g, '');
    },

    async printLabel(zplContent) {
        if (!this.selectedPrinter) {
            throw new Error('No hay impresora seleccionada');
        }

        const response = await fetch(`${this.API_URL}/write`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                device: {
                    name: this.selectedPrinter.name,
                    uid: this.selectedPrinter.uid,
                    connection: this.selectedPrinter.connection,
                    deviceType: this.selectedPrinter.deviceType,
                },
                data: zplContent,
            }),
        });

        if (!response.ok) {
            const errorText = await response.text().catch(() => 'Error desconocido');
            throw new Error('Error al enviar a la impresora: ' + errorText);
        }

        return true;
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
