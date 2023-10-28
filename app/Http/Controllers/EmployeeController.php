<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Job;


class EmployeeController extends Controller
{

    public function new()
    {
        $employees = Employee::all();
        $jobs = Job::all();
        return view('employee.new',compact('employees','jobs'));
    }

    public function store(Request $request)
    {
        $employee = new Employee;

        $employee->name = strtolower($request->name);
        $employee->lastName = strtolower($request->last_name);
        $employee->employeeId = $request->employeeId;
        $employee->email =$request->email;
        $employee->bank_account = $request->bank_account;
        $employee->jobs()->syncWithoutDetaching($request->position, ['user_id' => '1']);
        $employee->sex = $request->sex;
        $employee->phone = $request->phone;
        $employee->weekly_hours = $request->weekly_hours;
        $employee->birth = $request->birth;
        $employee->address = $request->address;

        try {
            $employee->save();
            return redirect()->back();
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }
    }

    public function edit(Request $request)
    {
        $employee = Employee::find($request->employee);
        $jobs = Job::all();


        return view('employee.edit',compact('employee','jobs'));
    }

    public function save(Request $request)
    {
        
        $employee = Employee::find($request->id);

        $employee->name = strtolower($request->name);
        $employee->lastName = strtolower($request->last_name);
        $employee->employeeId = $request->employeeId;
        $employee->email =$request->email;
        $employee->bank_account = $request->bank_account;
        $employee->jobs()->syncWithoutDetaching($request->position, ['user_id' => '1']);
        $employee->sex = $request->sex;
        $employee->phone = $request->phone;
        $employee->weekly_hours = $request->weekly_hours;
        $employee->birth = $request->birth;
        $employee->address = $request->address;

        try {
            $employee->save();
            return redirect()->action([EmployeeController::class, 'new']);
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }

    }
}
