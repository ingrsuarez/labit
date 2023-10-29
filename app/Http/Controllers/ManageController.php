<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Job;

class ManageController extends Controller
{
    //
    public function index()
    {
        $jobs = Job::all();
        $employees = Employee::all();
        return view('manage.index',compact('jobs','employees'));
    }
}
