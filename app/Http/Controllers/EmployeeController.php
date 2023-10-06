<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmployeeController extends Controller
{

    public function new()
    {

        return view('employee.new');
    }

    public function store(Request $request)
    {
        return $request;
    }
}
