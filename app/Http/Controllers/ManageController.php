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
        $job = Job::whereNotNull('parent_id')->first();
        $employees = Employee::all();
        return view('manage.index',compact('job','employees'));
    }
}
