<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Ver a qué BD está conectado
$dbName = DB::selectOne('SELECT DATABASE() as db')->db;
$host = config('database.connections.mysql.host');
$port = config('database.connections.mysql.port');
echo "Conectado a: {$host}:{$port} / {$dbName}\n\n";

// Ver TODAS las tablas migrations aplicadas relacionadas con result
$migs = DB::table('migrations')->where('migration', 'like', '%result%')->orderBy('id')->get();
echo "Migrations con 'result':\n";
foreach ($migs as $m) echo "  [{$m->batch}] {$m->migration}\n";

echo "\nTotal result_batches: " . DB::table('result_batches')->count() . "\n";
echo "Total result_ingestions: " . DB::table('result_ingestions')->count() . "\n";

// Últimos 10 batches
echo "\nÚltimos 10 batches:\n";
$batches = DB::table('result_batches')->orderBy('id','desc')->limit(10)->get();
foreach ($batches as $b) {
    echo "  id={$b->id} | ext={$b->external_batch_id} | ing={$b->items_ingested} | dup={$b->items_duplicate} | {$b->created_at}\n";
}

// Todas las ingestions
echo "\nTodas las ingestions:\n";
$ings = DB::table('result_ingestions')->orderBy('id','desc')->limit(20)->get();
foreach ($ings as $i) {
    echo "  id={$i->id} | hl7={$i->hl7_control_id} | equip={$i->equipment_name} | proto={$i->protocol_number} | status={$i->status} | batch={$i->result_batch_id}\n";
}
