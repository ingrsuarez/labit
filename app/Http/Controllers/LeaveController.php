<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Leave;
use App\Models\Job;
use App\Models\Employee;

class LeaveController extends Controller
{
    public function new()
    {
        $leaves = Leave::orderBy('start','desc')->get();
        $employees = Employee::all();

        return view('leave.new',compact('leaves','employees'));
    }

    public function resume()
    {
        // $resume = Leave::orderBy('start','desc')->get();
        $employees = Employee::all();
        // $jobs_query = Job::select('jobs.id','jobs.name','jobs.agreement','categories.name as category')
        //     ->leftJoin('categories','jobs.category_id','=','categories.id');
        // return $jobs;
        // ->joinSub($latestPosts, 'latest_posts', function (JoinClause $join) {
        //     $join->on('users.id', '=', 'latest_posts.user_id');
        // })

        $resumes = Leave::selectRaw('YEAR(leaves.start) as year, MONTH(leaves.start) as month, 
            CONCAT(employees.name," ",employees.lastName) as employee, employees.weekly_hours as hours,
            employees.position as category, leaves.id as leave_id, leaves.type, leaves.days, leaves.start')
            ->join('employees', 'leaves.employee_id', '=', 'employees.id')
            // ->whereRaw('month(leaves.start) = 3')
            // ->whereRaw('year(leaves.start) = 2024')
            // ->groupBy('leaves.id','leaves.type','leaves.start')
            ->orderBy('employees.lastName','DESC')
            ->orderBy('leaves.start','DESC')
            ->get();

        return view('leave.resume',compact('resumes','employees'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $leave = new Leave;

        $leave->description = strtolower($request->description);
        $leave->start = $request->start;
        $leave->end = $request->end;
        $leave->employee_id =$request->employee;
        $leave->type = $request->type;
        $leave->user_id = $user->id;
        $leave->doctor = $request->doctor;
        $leave->hour_50 = $request->hour_50;
        $leave->hour_100 = $request->hour_100;
        $leave->status = 'revision';

        try {
            $leave->save();
            return redirect()->back();
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }
    }

    public function edit(Leave $leave)
    {

        return view('leave.edit',compact('leave'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $leave = Leave::where('id',$request->leave_id)->first();

        $leave->description = strtolower($request->description);
        $leave->start = $request->start;
        $leave->end = $request->end;
        $leave->employee_id =$request->employee_id;
        $leave->type = $request->type;
        $leave->user_id = $user->id;
        $leave->doctor = $request->doctor;
        $leave->hour_50 = $request->hour_50;
        $leave->hour_100 = $request->hour_100;
        
        

        try {
            $leave->save();
            return redirect()->back();
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }
    }

    public function delete(Leave $leave)
    {
        $leave->delete();
        return redirect()->back();
    }
}
