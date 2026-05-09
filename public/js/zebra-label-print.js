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

    /**
     * Asegura material_key estable y admite JSON legacy (una sola etiqueta plana).
     *
     * @param {object} data
     * @returns {object[]}
     */
    normalizeLabelRows(data) {
        let labels = Array.isArray(data.labels) ? data.labels : [];
        if (labels.length === 0 && data.protocol_number) {
            const rawMat = data.materials && data.materials !== 'N/A' ? String(data.materials) : '';
            const first = rawMat.split('/').map(s => s.trim()).filter(Boolean)[0] || '?';
            labels = [{
                material_key: 'legacy',
                protocol_number: data.protocol_number,
                customer_name: data.customer_name,
                material: first,
                material_name: data.material_name || '',
                entry_date: data.entry_date,
                branch_name: data.sample_type || '',
                sample_type: data.sample_type || '',
            }];
        }
        return labels.map((row, idx) => ({
            ...row,
            material_key: row.material_key != null && String(row.material_key) !== ''
                ? String(row.material_key)
                : 'idx-' + idx,
        }));
    },

    /** Contenido CODE_128 alineado a BarcodeFormatService::forLabel */
    _barcodeDataForLabel(label) {
        const protocol = this._sanitizeZPL(label.protocol_number);
        const material = this._sanitizeZPL(label.material || '');
        if (!material || material === '?') {
            return protocol;
        }
        return protocol + '^' + material;
    },

    generateZPL(data) {
        if (data.labels && data.labels.length > 0) {
            let zpl = '';
            for (const label of data.labels) {
                const customerName = this._sanitizeZPL(label.customer_name).substring(0, 30);
                const barcodeData = this._barcodeDataForLabel(label);
                const material = this._sanitizeZPL(label.material || '?');
                const entryDate = this._sanitizeZPL(label.entry_date);
                const branchName = this._sanitizeZPL(
                    label.branch_name || label.sample_type || 'CLINICO'
                );

                zpl += '^XA\n';
                zpl += '^CI28\n';
                zpl += '^PW400\n';
                zpl += '^LL200\n';
                zpl += '^FO40,10^BY2,2,65^BCN,65,Y,N,N^FD' + barcodeData + '^FS\n';
                zpl += '^FO30,100^A0N,20,20^FD' + customerName + '^FS\n';
                zpl += '^FO30,125^A0N,18,18^FD' + branchName + ' | ' + material + '^FS\n';
                zpl += '^FO30,148^A0N,18,18^FD' + entryDate + '^FS\n';
                zpl += '^PQ1\n';
                zpl += '^XZ\n';
            }
            return zpl;
        }

        const customerName = this._sanitizeZPL(data.customer_name).substring(0, 30);
        const protocol = this._sanitizeZPL(data.protocol_number);
        const materials = this._sanitizeZPL(data.materials);
        const entryDate = this._sanitizeZPL(data.entry_date);
        const sampleType = this._sanitizeZPL(data.sample_type);
        const barcodeData = (materials && materials !== 'N/A')
            ? protocol + '^' + materials.split('/')[0].trim()
            : protocol;

        return [
            '^XA',
            '^CI28',
            '^PW400',
            '^LL200',
            '^FO40,10^BY2,2,65^BCN,65,Y,N,N^FD' + barcodeData + '^FS',
            '^FO30,100^A0N,20,20^FD' + customerName + '^FS',
            '^FO30,125^A0N,18,18^FD' + sampleType + ' | MAT: ' + materials + '^FS',
            '^FO30,148^A0N,18,18^FD' + entryDate + '^FS',
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

    /**
     * @param {string} labelDataUrl
     * @param {number} copies
     * @param {object[]|null} selectedRows Si null, usa todas las filas del JSON.
     */
    async printSampleLabel(labelDataUrl, copies = 1, selectedRows = null) {
        const response = await fetch(labelDataUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) throw new Error('No se pudieron obtener los datos de la muestra');

        const data = await response.json();
        let labels = this.normalizeLabelRows(data);

        if (Array.isArray(selectedRows) && selectedRows.length > 0) {
            const keys = new Set(selectedRows.map(r => String(r.material_key)));
            labels = labels.filter(l => keys.has(String(l.material_key)));
        }

        if (labels.length === 0) {
            throw new Error('Seleccione al menos un material para imprimir.');
        }

        const zplPayload = { labels };
        let zpl = this.generateZPL(zplPayload);

        if (copies > 1) {
            zpl = zpl.replaceAll('^PQ1', '^PQ' + copies);
        }

        return await this.printLabel(zpl);
    },
};

/**
 * Alpine.js component for the label printing modal
 *
 * @param {string} browserPrintBaseUrl URL absoluta de la vista HTML multipágina (sin query materials)
 */
function zebraPrintModal(browserPrintBaseUrl = '') {
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
        browserPrintBaseUrl: browserPrintBaseUrl || '',
        labelRows: [],
        selectedKeys: [],

        async init() {
            const saved = localStorage.getItem('zebra_selected_printer');
            if (saved) this.selectedPrinter = saved;
        },

        async openModal(payload) {
            const labelDataUrl = typeof payload === 'string' ? payload : (payload && payload.url);
            if (!labelDataUrl) return;

            this.labelDataUrl = labelDataUrl;
            this.open = true;
            this.error = null;
            this.success = false;
            this.loading = true;
            this.labelRows = [];
            this.selectedKeys = [];

            try {
                const res = await fetch(labelDataUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) throw new Error('No se pudieron obtener los datos de la etiqueta');
                const data = await res.json();
                this.labelRows = ZebraLabelPrint.normalizeLabelRows(data);
                this.selectedKeys = this.labelRows.map(r => String(r.material_key));
            } catch (e) {
                this.error = e.message || 'Error al cargar datos de etiquetas';
                this.loading = false;
                return;
            }

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
                if (!this.error) {
                    this.error = e.message;
                }
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

        selectAllMaterials() {
            this.selectedKeys = this.labelRows.map(r => String(r.material_key));
        },

        clearAllMaterials() {
            this.selectedKeys = [];
        },

        getSelectedRows() {
            const set = new Set(this.selectedKeys.map(k => String(k)));
            return this.labelRows.filter(r => set.has(String(r.material_key)));
        },

        browserPrintHref() {
            if (!this.browserPrintBaseUrl) return '#';
            const codes = this.getSelectedRows().map(r => r.material).filter(m => m && m !== '?');
            if (codes.length === 0) return this.browserPrintBaseUrl;
            const sep = this.browserPrintBaseUrl.includes('?') ? '&' : '?';
            return this.browserPrintBaseUrl + sep + 'materials=' + encodeURIComponent(codes.join(','));
        },

        firstPreviewRow() {
            const rows = this.getSelectedRows();
            return rows.length ? rows[0] : (this.labelRows[0] || null);
        },

        previewField(field, fallback = '') {
            const r = this.firstPreviewRow();
            if (!r) return fallback;
            const v = r[field];
            return v != null && v !== '' ? v : fallback;
        },

        async print() {
            if (!this.selectedPrinter) {
                this.error = 'Seleccione una impresora';
                return;
            }
            if (this.selectedKeys.length === 0) {
                this.error = 'Seleccione al menos un material';
                return;
            }

            this.printing = true;
            this.error = null;
            this.success = false;

            try {
                ZebraLabelPrint.selectPrinter(this.selectedPrinter);
                await ZebraLabelPrint.printSampleLabel(
                    this.labelDataUrl,
                    this.copies,
                    this.getSelectedRows()
                );
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
