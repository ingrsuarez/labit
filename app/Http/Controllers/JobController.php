<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;

class JobController extends Controller
{
    public function new()
    {
        $jobs = Job::all();
        return view('job.new',compact('jobs'));
    }

    public function store(Request $request)
    {
        
        $job = new Job;

        $job->name = strtolower($request->name);
        $job->order = $request->order;
        $job->email =$request->email;
        $job->parent_id = $request->parent;
        $job->department = strtolower($request->department);
        $job->responsibilities = $request->responsibilities;

        try {
            $job->save();
            return redirect()->back();
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }
    }

    public function edit(Request $request)
    {
        $job = Job::find($request->job);



        return view('job.edit',compact('job'));
    }

    public function save(Request $request)
    {
        
        $job = Job::find($request->id);

        $job->name = strtolower($request->name);
        $job->lastName = strtolower($request->last_name);
        $job->jobId = $request->jobId;
        $job->email =$request->email;
        $job->bank_account = $request->bank_account;
        $job->position = $request->position;
        $job->sex = $request->sex;
        $job->phone = $request->phone;
        $job->weekly_hours = $request->weekly_hours;
        $job->birth = $request->birth;
        $job->address = $request->address;

        try {
            $job->save();
            return redirect()->action([JobController::class, 'new']);
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }

    }
}
