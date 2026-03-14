<?php

namespace App\Http\Controllers;

use App\Services\AfipService;
use Exception;

class AfipTestController extends Controller
{
    public function index()
    {
        $results = [];

        try {
            $afip = new AfipService();
            $results['config'] = [
                'cuit' => config('afip.cuit'),
                'cert_exists' => file_exists(config('afip.cert_path')),
                'key_exists' => file_exists(config('afip.key_path')),
                'production' => config('afip.production'),
            ];

            $status = $afip->getServerStatus();
            $results['server_status'] = $status;

        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
        }

        return view('afip.test', compact('results'));
    }
}
