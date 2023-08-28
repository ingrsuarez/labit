<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;

class TestController extends Controller
{
    public function index()
    {
        $parents = Test::where('parent',NULL)->get();
        return view('test.index',compact('parents'));
    }

    public function store(Request $request)
    {
        
        $test = new Test;
        $test->name = strtolower($request->name);
        $test->unit = $request->unit;
        $test->low = '';
        $test->high = '';
        $test->instructions = $request->instructions;
        $test->parent = $request->parent;
        $test->decimals = $request->decimals;
        $test->negative = '';
        $test->positive = '';
        $test->questions = $request->questions;
        $test->code = $request->code;
        $test->method = $request->method;
        $test->nbu = $request->nbu;
        $test->price = 1;
        $test->cost = 1;
        $test->work_sheet = '';
        $test->material = $request->material;
        $test->formula = '';
        $test->box = 1;
        
        try {
            $test->save();
            return redirect()->back();
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }

    
        return $test;
    }
}
