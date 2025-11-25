<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // PATIENTS ROUTES
    Route::get('/patient/new',[App\Http\Controllers\PatientController::class, 'index'])->name('patient.index');
    Route::get('/patient/show',[App\Http\Controllers\PatientController::class, 'show'])->name('patient.show');
    Route::get('/patient/edit',[App\Http\Controllers\PatientController::class, 'edit'])->name('patient.edit');
    Route::post('/patient/edit',[App\Http\Controllers\PatientController::class, 'save_changes'])->name('patient.save');

    Route::post('/patient/store',[App\Http\Controllers\PatientController::class, 'store'])->name('patient.store');

    // TEST ROUTES
    Route::get('/tests/new',[App\Http\Controllers\TestController::class, 'index'])->name('tests.index');

    Route::post('tests/store',[App\Http\Controllers\TestController::class, 'store'])->name('test.store');

    // ADMISSION
    Route::get('/admission/new',[App\Http\Controllers\AdmissionController::class, 'index'])->name('admission.index');

    Route::post('admission/store',[App\Http\Controllers\AdmissionController::class, 'store'])->name('admission.store');


    // INSURANCE
    Route::get('/insurance/new',[App\Http\Controllers\InsuranceController::class, 'index'])->name('insurance.index');

    Route::post('insurance/store',[App\Http\Controllers\InsuranceController::class, 'store'])->name('insurance.store');

    // GROUP
    Route::get('/group/new/{current_patient?}',[App\Http\Controllers\GroupController::class, 'index'])->name('group.index');

    Route::post('group/store',[App\Http\Controllers\GroupController::class, 'store'])->name('group.store');

    //MANAGMENT
    Route::get('manage/index',[App\Http\Controllers\ManageController::class, 'index'])->name('manage.index');

    Route::get('manage/organization',[App\Http\Controllers\ManageController::class, 'chart'])
        ->middleware('can:view.chart')
        ->name('manage.chart');

    //DOCUMENTS
    Route::get('documents/index',[App\Http\Controllers\DocumentController::class, 'index'])
        ->middleware('can:documentos.index')
        ->name('documents.index');
    
    Route::get('documents/create',[App\Http\Controllers\DocumentController::class, 'create'])
        ->middleware('can:documentos.index')
        ->name('documents.create');
    
    Route::post('documents/store',[App\Http\Controllers\DocumentController::class, 'store'])
        ->middleware('can:documentos.index')
        ->name('documents.store');

    Route::get('documents/edit/{document}',[App\Http\Controllers\DocumentController::class, 'edit'])
        ->middleware('can:documentos.index')
        ->name('documents.edit');

    Route::post('documents/update/{document}',[App\Http\Controllers\DocumentController::class, 'update'])
        ->middleware('can:documentos.index')
        ->name('documents.update');
    
    Route::delete('documents/destroy/{document}',[App\Http\Controllers\DocumentController::class, 'destroy'])
        ->middleware('can:documentos.index')
        ->name('documents.destroy');

    Route::delete('documents/file/destroy/{file}',[App\Http\Controllers\DocumentController::class, 'file_destroy'])
        ->middleware('can:documentos.index')
        ->name('documents.files.destroy');

    //EMPLOYEES
    Route::get('employee/new',[App\Http\Controllers\EmployeeController::class, 'new'])->name('employee.new');

    Route::post('employee/store',[App\Http\Controllers\EmployeeController::class, 'store'])->name('employee.store');

    Route::get('employee/edit/{employee}',[App\Http\Controllers\EmployeeController::class, 'edit'])->name('employee.edit');

    Route::post('employee/save',[App\Http\Controllers\EmployeeController::class, 'save'])->name('employee.update');

    Route::get('employee/show', [App\Http\Controllers\EmployeeController::class, 'show'])->name('employee.show');

    //JOBS
    Route::get('job/new',[App\Http\Controllers\JobController::class, 'new'])->name('job.new');

    Route::get('job/list',[App\Http\Controllers\JobController::class, 'list'])->name('job.list');

    Route::post('job/store',[App\Http\Controllers\JobController::class, 'store'])->name('job.store');

    Route::get('job/edit/{job}',[App\Http\Controllers\JobController::class, 'edit'])->name('job.edit');
    Route::post('job/save',[App\Http\Controllers\JobController::class, 'save'])->name('job.save');

    Route::get('job/delete',[App\Http\Controllers\JobController::class, 'delete'])->name('job.delete');
    Route::get('job/detach/{job}/{employee}',[App\Http\Controllers\JobController::class, 'detach'])->name('job.detach');

    Route::get('category/new',[App\Http\Controllers\JobController::class, 'newCategory'])->name('category.new');

    Route::post('category/store',[App\Http\Controllers\JobController::class, 'storeCategory'])->name('category.store');

    Route::get('category/edit',[App\Http\Controllers\JobController::class, 'editCategory'])->name('category.edit');
    Route::post('category/save',[App\Http\Controllers\JobController::class, 'saveCategory'])->name('category.save');

    Route::get('category/delete',[App\Http\Controllers\JobController::class, 'deleteCategory'])->name('category.delete');

    //LEAVES
    Route::get('leave/resume',[App\Http\Controllers\LeaveController::class, 'resume'])->name('leave.resume');
    Route::get('leave/new',[App\Http\Controllers\LeaveController::class, 'new'])->name('leave.new');
    Route::get('leave/index',[App\Http\Controllers\LeaveController::class, 'index'])->name('leave.index');

    Route::post('leave/store',[App\Http\Controllers\LeaveController::class, 'store'])->name('leave.store');

    Route::post('leave/update/{leave}',[App\Http\Controllers\LeaveController::class, 'update'])->name('leave.update');

    Route::get('leave/edit/{leave}',[App\Http\Controllers\LeaveController::class, 'edit'])->name('leave.edit');

    Route::get('leave/delete/{leave?}',[App\Http\Controllers\LeaveController::class, 'delete'])->name('leave.delete');

    Route::get('leave/resume-compact', [App\Http\Controllers\LeaveController::class, 'resumeCompact'])
     ->name('leave.resume.compact');
    // USERS

    Route::get('users/index',[App\Http\Controllers\UserController::class, 'index'])
        // ->middleware('can:role.new')
        ->name('user.index');

    Route::get('users/edit',[App\Http\Controllers\UserController::class, 'edit'])
        // ->middleware('can:role.new')
        ->name('user.edit');

    Route::post('users/save',[App\Http\Controllers\UserController::class, 'save'])
        // ->middleware('can:role.new')
        ->name('user.save');
        

    Route::get('users/attach/role',[App\Http\Controllers\UserController::class, 'attachRole'])
        ->middleware('can:role.new')
        ->name('role.attach');
    Route::get('users/detach/role',[App\Http\Controllers\UserController::class, 'detachRole'])
        ->middleware('can:role.new')
        ->name('role.detach');

    //ROLES

    Route::get('role/new',[App\Http\Controllers\RoleController::class, 'new'])
        ->middleware('can:role.new')
        ->name('role.new');

    Route::post('role/store',[App\Http\Controllers\RoleController::class, 'store'])
        ->middleware('can:role.store')
        ->name('role.store');

    Route::post('role/update',[App\Http\Controllers\RoleController::class, 'update'])
        ->middleware('can:role.store')
        ->name('role.update');

    Route::get('role/edit/{role}',[App\Http\Controllers\RoleController::class, 'edit'])->name('role.edit');
    
    Route::get('role/permission/attach/{role}/{permission}',[App\Http\Controllers\RoleController::class, 'attachPermission'])
        ->middleware('can:role.attachPermission')
        ->name('role.attachPermission');
    
    Route::get('role/permission/detach/{role}/{permission}',[App\Http\Controllers\RoleController::class, 'detachPermission'])
        ->middleware('can:role.detachPermission')
        ->name('role.detachPermission');
    //PERMISSION

    Route::get('permission/new',[App\Http\Controllers\PermissionController::class, 'new'])
        ->middleware('can:permission.new')
        ->name('permission.new');

    Route::post('permission/store',[App\Http\Controllers\PermissionController::class, 'store'])
        ->middleware('can:permission.store')
        ->name('permission.store');

    Route::get('permission/edit/{permission}',[App\Http\Controllers\PermissionController::class, 'edit'])
        ->middleware('can:permission.new')
        ->name('permission.edit');    
});
