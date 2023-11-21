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
    
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

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


    //EMPLOYEES
    Route::get('employee/new',[App\Http\Controllers\EmployeeController::class, 'new'])->name('employee.new');

    Route::post('employee/store',[App\Http\Controllers\EmployeeController::class, 'store'])->name('employee.store');

    Route::get('employee/edit',[App\Http\Controllers\EmployeeController::class, 'edit'])->name('employee.edit');
    Route::post('employee/save',[App\Http\Controllers\EmployeeController::class, 'save'])->name('employee.save');

    Route::get('employee/show', [EmployeeController::class, 'show'])->name('employee.show');

    //JOBS
    Route::get('job/new',[App\Http\Controllers\JobController::class, 'new'])->name('job.new');

    Route::post('job/store',[App\Http\Controllers\JobController::class, 'store'])->name('job.store');

    Route::get('job/edit',[App\Http\Controllers\JobController::class, 'edit'])->name('job.edit');
    Route::post('job/save',[App\Http\Controllers\JobController::class, 'save'])->name('job.save');

    Route::get('job/delete',[App\Http\Controllers\JobController::class, 'delete'])->name('job.delete');
    Route::get('job/detach/{job}/{employee}',[App\Http\Controllers\JobController::class, 'detach'])->name('job.detach');

    Route::get('category/new',[App\Http\Controllers\JobController::class, 'newCategory'])->name('category.new');

    Route::post('category/store',[App\Http\Controllers\JobController::class, 'storeCategory'])->name('category.store');

    Route::get('category/edit',[App\Http\Controllers\JobController::class, 'editCategory'])->name('category.edit');
    Route::post('category/save',[App\Http\Controllers\JobController::class, 'saveCategory'])->name('category.save');

    Route::get('category/delete',[App\Http\Controllers\JobController::class, 'deleteCategory'])->name('category.delete');

});
