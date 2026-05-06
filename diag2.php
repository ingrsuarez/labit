<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== INGESTIONS con hl7_control_id='1' ===\n";
$rows = DB::table('result_ingestions')
    ->where('hl7_control_id', '1')
    ->orderBy('id')
    ->get(['id','result_batch_id','api_client_id','external_message_id','hl7_control_id','protocol_number','equipment_name','status','rejection_reason','created_at']);
foreach ($rows as $r) {
    echo "id={$r->id} | batch={$r->result_batch_id} | equip={$r->equipment_name} | proto={$r->protocol_number} | status={$r->status} | created={$r->created_at}\n";
}

echo "\n=== TODOS los batches del api_client que contiene V2605050003 ===\n";
$batches = DB::table('result_batches')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id','external_batch_id','items_total','items_ingested','items_duplicate','items_rejected','created_at']);
foreach ($batches as $b) {
    echo "batch_id={$b->id} | ext={$b->external_batch_id} | total={$b->items_total} | ing={$b->items_ingested} | dup={$b->items_duplicate} | rej={$b->items_rejected} | {$b->created_at}\n";
}

echo "\n=== ESTADO ÍNDICES result_ingestions ===\n";
$indexes = DB::select("SHOW INDEX FROM result_ingestions");
foreach ($indexes as $idx) {
    echo "Key={$idx->Key_name} | Col={$idx->Column_name} | Unique=" . ($idx->Non_unique == 0 ? 'SÍ' : 'NO') . "\n";
}

echo "\n=== VERIFICAR QUÉ CÓDIGO ESTÁ CORRIENDO (hash del servicio) ===\n";
$file = __DIR__ . '/app/Services/Api/ApiResultIngestionService.php';
$content = file_get_contents($file);
if (strpos($content, 'equipment_name') !== false && strpos($content, 'dedupQuery') !== false) {
    echo "Código ACTUALIZADO (incluye equipment_name en dedup)\n";
} else {
    echo "Código VIEJO (sin equipment_name en dedup)\n";
}
