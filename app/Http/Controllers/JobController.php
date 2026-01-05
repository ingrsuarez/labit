<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\Job;
use App\Models\Employee;
use App\Models\Category;


class JobController extends Controller
{
    public function list()
    {
        $jobs = Job::with(['category', 'employees', 'childs', 'parent'])->get();
        $categories = Category::all();

        return view('job.list', compact('jobs', 'categories'));
    }

    public function new()
    {
        $jobs = Job::with('category')->get();
        $categories = Category::all();

        return view('job.new',compact('jobs','categories'));
    }

    public function store(Request $request)
    {
        
        $data = $request->validate([
            'name'         => ['required','string','max:255'],
            'parent_id'    => ['nullable','integer','exists:jobs,id'],
            'department'   => ['nullable','string','max:255'],
            'agreement'    => ['nullable','string','max:255'],
            'category_id'  => ['required','integer','exists:categories,id'], // <-- clave
            'responsibilities' => ['nullable','string'],
            'email'        => ['nullable','email','max:255'],
        ]);

        $job = Job::create($data);

        return redirect()->route('job.new')
                ->with('success', 'Puesto creado correctamente');
    }

    public function edit(Request $request)
    {
        $job = Job::find($request->job);
        $jobs = Job::all();
        $categories = Category::all();

        return view('job.edit',compact('job','jobs','categories'));
    }

    public function save(Request $request)
    {
        // return $request;
        $job = Job::find($request->id);

        $job->name = strtolower($request->name);
        $job->category_id = $request->category;
        $job->agreement = $request->agreement;
        $job->email =$request->email;
        $job->parent_id = $request->parent;
        $job->department = strtolower($request->department);
        $job->responsibilities = $request->responsibilities;

        try {
            $job->save();
            return redirect()->action([JobController::class, 'new']);
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }

    }

    public function delete(Request $request)
    {

        $job = Job::find($request->job);
        if($job->childs->isNotEmpty())
        {
            echo('Tiene hijos');
        }
        else
        {
           $job->delete();
           return redirect()->back();
        }
        

        return view('job.new',compact('jobs'));
    }

    public function detach(Job $job, Employee $employee)
    {
        $employee->jobs()->detach($job->id);
        return redirect()->back();
    }

    public function indexCategory()
    {
        $categories = Category::withCount('jobs')->orderBy('name')->get();
        return view('category.index', compact('categories'));
    }

    public function newCategory()
    {
        $categories = Category::all();
        return view('category.new',compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        
        $categories = new Category;

        $categories->name = strtolower($request->name);
        $categories->agreement = strtolower($request->agreement);
        $categories->union_name = strtolower($request->union_name);
        $categories->wage = $request->wage;
        $categories->base_weekly_hours = $request->base_weekly_hours ?? 48;

        try {
            $categories->save();
            return redirect()->route('category.index');
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }
    }

    public function editCategory(Request $request)
    {
        $category = Category::find($request->category);
        $jobs = Job::all();
        $categories = Category::all();


        return view('category.edit',compact('category','jobs','categories'));

    }

    public function saveCategory(Request $request)
    {
        
        $category = Category::find($request->id);

        $category->name = strtolower($request->name);
        $category->agreement = $request->agreement;
        $category->wage = $request->wage;
        $category->base_weekly_hours = $request->base_weekly_hours ?? 48;
        $category->union_name = strtolower($request->union_name);
        $category->full_time = $request->full_time;

        try {
            $category->save();
            return redirect()->route('category.index');
        }
        catch(Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";

        }

    }

}
