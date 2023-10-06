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
});
