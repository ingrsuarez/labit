<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_statement_id', 'date', 'value_date', 'concept', 'bank_code',
        'document_number', 'office', 'credit', 'debit', 'balance', 'detail',
        'category', 'reconciliation_status',
        'reconciled_type', 'reconciled_id', 'reconciled_at', 'reconciled_by', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'value_date' => 'date',
        'credit' => 'decimal:2',
        'debit' => 'decimal:2',
        'balance' => 'decimal:2',
        'reconciled_at' => 'datetime',
    ];

    public function statement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class, 'bank_statement_id');
    }

    public function reconciledRecord(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reconciled_type', 'reconciled_id');
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function getAmountAttribute(): float
    {
        return $this->credit > 0 ? (float) $this->credit : -1 * (float) $this->debit;
    }

    public function getIsDebitAttribute(): bool
    {
        return (float) $this->debit > 0;
    }

    public function getIsCreditAttribute(): bool
    {
        return (float) $this->credit > 0;
    }

    public static function categorize(string $concept, ?string $bankCode): ?string
    {
        $codeMap = [
            '136' => 'transferencia', '135' => 'transferencia',
            '129' => 'transferencia', '262' => 'transferencia',
            '265' => 'transferencia', '319' => 'transferencia',
            '986' => 'cobro', '983' => 'cobro',
            '005' => 'cheque', '082' => 'cheque',
            '381' => 'impuesto', '589' => 'impuesto',
            '573' => 'impuesto', '609' => 'impuesto',
            '526' => 'comision', '241' => 'comision',
            '575' => 'comision', '392' => 'comision',
            '236' => 'iva_comision',
            '522' => 'pago_tarjeta', '137' => 'pago_servicio',
            '081' => 'debin',
            '761' => 'debito_automatico', '879' => 'debito_automatico',
            '247' => 'debito_automatico', '493' => 'debito_automatico',
            '744' => 'pago_tarjeta_credito',
            '016' => 'movimiento_interno',
        ];

        if ($bankCode && isset($codeMap[trim($bankCode)])) {
            return $codeMap[trim($bankCode)];
        }

        $conceptPatterns = [
            '/TRANSF/i' => 'transferencia',
            '/CHEQUE|CH\/CLEAR|DEPOS\.CHQ/i' => 'cheque',
            '/AFIP|IMPUESTO|IB NEUQUEN|LEY NRO/i' => 'impuesto',
            '/COMISION|COM EMISION/i' => 'comision',
            '/IVA TASA/i' => 'iva_comision',
            '/PAGO CON VIS|PAGO SERVICI/i' => 'pago_tarjeta',
            '/DEBIN/i' => 'debin',
            '/OG-DEBITO|OG-DEB/i' => 'debito_automatico',
            '/ACRED SUELDO|DNET CREDITO/i' => 'cobro',
        ];

        foreach ($conceptPatterns as $pattern => $category) {
            if (preg_match($pattern, $concept)) {
                return $category;
            }
        }

        return null;
    }
}
