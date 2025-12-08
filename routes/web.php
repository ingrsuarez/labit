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

// Ruta para usuarios sin acceso (debe estar fuera del middleware check.access)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/access-pending', function () {
        $user = auth()->user();
        
        // Roles que solo dan acceso al portal
        $portalOnlyRoles = ['empleado', 'employee'];
        $userRoles = $user->roles->pluck('name')->map(fn($r) => strtolower($r))->toArray();
        
        // Si tiene roles administrativos (no solo empleado), ir al dashboard
        $hasAdminRoles = !empty(array_diff($userRoles, $portalOnlyRoles));
        if ($hasAdminRoles || $user->permissions->count() > 0) {
            return redirect()->route('dashboard');
        }
        
        // Si tiene empleado asociado, ir al portal
        if ($user->employee) {
            return redirect()->route('portal.dashboard');
        }
        
        // Mostrar página de acceso pendiente
        return view('auth.access-pending');
    })->name('access.pending');
});

// Rutas protegidas que requieren acceso al sistema
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'check.access',
])->group(function () {
    
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // PATIENTS ROUTES
    Route::get('/patient/new',[App\Http\Controllers\PatientController::class, 'index'])->name('patient.index');
    Route::get('/patient/show',[App\Http\Controllers\PatientController::class, 'show'])->name('patient.show');
    Route::get('/patient/edit',[App\Http\Controllers\PatientController::class, 'edit'])->name('patient.edit');
    Route::post('/patient/edit',[App\Http\Controllers\PatientController::class, 'save_changes'])->name('patient.save');

    Route::post('/patient/store',[App\Http\Controllers\PatientController::class, 'store'])->name('patient.store');

    // TEST ROUTES (Determinaciones)
    Route::get('/tests',[App\Http\Controllers\TestController::class, 'index'])->name('tests.index');
    Route::get('/tests/create',[App\Http\Controllers\TestController::class, 'create'])->name('tests.create');
    Route::post('/tests',[App\Http\Controllers\TestController::class, 'store'])->name('test.store');
    Route::get('/tests/{test}/edit',[App\Http\Controllers\TestController::class, 'edit'])->name('tests.edit')->where('test', '[0-9]+');
    Route::put('/tests/{test}',[App\Http\Controllers\TestController::class, 'update'])->name('tests.update')->where('test', '[0-9]+');
    Route::delete('/tests/{test}',[App\Http\Controllers\TestController::class, 'destroy'])->name('tests.destroy')->where('test', '[0-9]+');

    // Test Reference Values (Valores de Referencia)
    Route::get('/tests/{test}/reference-values', [App\Http\Controllers\TestReferenceValueController::class, 'index'])->name('tests.reference-values.index');
    Route::post('/tests/{test}/reference-values', [App\Http\Controllers\TestReferenceValueController::class, 'store'])->name('tests.reference-values.store');
    Route::put('/tests/{test}/reference-values/{referenceValue}', [App\Http\Controllers\TestReferenceValueController::class, 'update'])->name('tests.reference-values.update');
    Route::delete('/tests/{test}/reference-values/{referenceValue}', [App\Http\Controllers\TestReferenceValueController::class, 'destroy'])->name('tests.reference-values.destroy');

    // Reference Categories (Categorías de Valores de Referencia)
    Route::get('/reference-categories', [App\Http\Controllers\ReferenceCategoryController::class, 'index'])->name('reference-categories.index');
    Route::post('/reference-categories', [App\Http\Controllers\ReferenceCategoryController::class, 'store'])->name('reference-categories.store');
    Route::put('/reference-categories/{category}', [App\Http\Controllers\ReferenceCategoryController::class, 'update'])->name('reference-categories.update');
    Route::delete('/reference-categories/{category}', [App\Http\Controllers\ReferenceCategoryController::class, 'destroy'])->name('reference-categories.destroy');

    // ADMISSION
    Route::get('/admission/new',[App\Http\Controllers\AdmissionController::class, 'index'])->name('admission.index');

    Route::post('admission/store',[App\Http\Controllers\AdmissionController::class, 'store'])->name('admission.store');


    // INSURANCE
    Route::get('/insurance/new',[App\Http\Controllers\InsuranceController::class, 'index'])->name('insurance.index');

    Route::post('insurance/store',[App\Http\Controllers\InsuranceController::class, 'store'])->name('insurance.store');

    // GROUP
    Route::get('/group/new/{current_patient?}',[App\Http\Controllers\GroupController::class, 'index'])->name('group.index');

    Route::post('group/store',[App\Http\Controllers\GroupController::class, 'store'])->name('group.store');

    // SAMPLES (Muestras de Agua y Alimentos)
    Route::get('sample', [App\Http\Controllers\SampleController::class, 'index'])->name('sample.index');
    Route::get('sample/create', [App\Http\Controllers\SampleController::class, 'create'])->name('sample.create');
    Route::post('sample', [App\Http\Controllers\SampleController::class, 'store'])->name('sample.store');
    Route::get('sample/{sample}', [App\Http\Controllers\SampleController::class, 'show'])->name('sample.show');
    Route::get('sample/{sample}/edit', [App\Http\Controllers\SampleController::class, 'edit'])->name('sample.edit');
    Route::put('sample/{sample}', [App\Http\Controllers\SampleController::class, 'update'])->name('sample.update');
    Route::post('sample/{sample}/determination', [App\Http\Controllers\SampleController::class, 'addDetermination'])->name('sample.addDetermination');
    Route::delete('sample/{sample}/determination/{determination}', [App\Http\Controllers\SampleController::class, 'removeDetermination'])->name('sample.removeDetermination');
    Route::put('sample/determination/{determination}', [App\Http\Controllers\SampleController::class, 'updateDetermination'])->name('sample.updateDetermination');
    
    // Carga rápida de resultados
    Route::get('sample/{sample}/load-results', [App\Http\Controllers\SampleController::class, 'loadResults'])->name('sample.loadResults');
    Route::post('sample/{sample}/save-results', [App\Http\Controllers\SampleController::class, 'saveResults'])->name('sample.saveResults');
    
    // Validación de protocolos
    Route::get('sample/{sample}/validate', [App\Http\Controllers\SampleController::class, 'showValidation'])
        ->middleware('can:samples.validate')
        ->name('sample.validate.show');
    Route::post('sample/{sample}/validate', [App\Http\Controllers\SampleController::class, 'processValidation'])
        ->middleware('can:samples.validate')
        ->name('sample.validate');
    Route::post('sample/{sample}/validate/revert', [App\Http\Controllers\SampleController::class, 'revertValidation'])
        ->middleware('can:samples.validate')
        ->name('sample.validate.revert');
    
    // Validación de determinaciones individuales
    Route::post('sample/determination/{determination}/toggle-validation', [App\Http\Controllers\SampleController::class, 'toggleDeterminationValidation'])
        ->middleware('can:samples.validate')
        ->name('sample.determination.toggleValidation');
    Route::post('sample/{sample}/validate-determinations', [App\Http\Controllers\SampleController::class, 'validateDeterminations'])
        ->middleware('can:samples.validate')
        ->name('sample.validateDeterminations');
    
    // PDF y envío
    Route::get('sample/{sample}/pdf', [App\Http\Controllers\SampleController::class, 'downloadPdf'])->name('sample.pdf.download');
    Route::get('sample/{sample}/pdf/view', [App\Http\Controllers\SampleController::class, 'viewPdf'])->name('sample.pdf.view');
    Route::post('sample/{sample}/send-email', [App\Http\Controllers\SampleController::class, 'sendEmail'])->name('sample.sendEmail');

    // CUSTOMERS (Clientes)
    Route::get('customer', [App\Http\Controllers\CustomerController::class, 'index'])->name('customer.index');
    Route::get('customer/create', [App\Http\Controllers\CustomerController::class, 'create'])->name('customer.create');
    Route::post('customer', [App\Http\Controllers\CustomerController::class, 'store'])->name('customer.store');
    Route::get('customer/{customer}/edit', [App\Http\Controllers\CustomerController::class, 'edit'])->name('customer.edit');
    Route::put('customer/{customer}', [App\Http\Controllers\CustomerController::class, 'update'])->name('customer.update');

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
    
    Route::get('employee/profile/{employee}', [App\Http\Controllers\EmployeeController::class, 'profile'])->name('employee.profile');

    //JOBS
    Route::get('job/new',[App\Http\Controllers\JobController::class, 'new'])->name('job.new');

    Route::get('job/list',[App\Http\Controllers\JobController::class, 'list'])->name('job.list');

    Route::post('job/store',[App\Http\Controllers\JobController::class, 'store'])->name('job.store');

    Route::get('job/edit/{job}',[App\Http\Controllers\JobController::class, 'edit'])->name('job.edit');
    Route::post('job/save',[App\Http\Controllers\JobController::class, 'save'])->name('job.save');

    Route::get('job/delete',[App\Http\Controllers\JobController::class, 'delete'])->name('job.delete');
    Route::get('job/detach/{job}/{employee}',[App\Http\Controllers\JobController::class, 'detach'])->name('job.detach');

    Route::get('category/index',[App\Http\Controllers\JobController::class, 'indexCategory'])->name('category.index');

    Route::get('category/new',[App\Http\Controllers\JobController::class, 'newCategory'])->name('category.new');

    Route::post('category/store',[App\Http\Controllers\JobController::class, 'storeCategory'])->name('category.store');

    Route::get('category/edit',[App\Http\Controllers\JobController::class, 'editCategory'])->name('category.edit');
    Route::post('category/save',[App\Http\Controllers\JobController::class, 'saveCategory'])->name('category.save');

    Route::get('category/delete',[App\Http\Controllers\JobController::class, 'deleteCategory'])->name('category.delete');

    //SALARY ITEMS (Conceptos de Sueldo)
    Route::get('salary/index', [App\Http\Controllers\SalaryItemController::class, 'index'])->name('salary.index');
    Route::get('salary/create', [App\Http\Controllers\SalaryItemController::class, 'create'])->name('salary.create');
    Route::post('salary/store', [App\Http\Controllers\SalaryItemController::class, 'store'])->name('salary.store');
    Route::get('salary/edit/{salaryItem}', [App\Http\Controllers\SalaryItemController::class, 'edit'])->name('salary.edit');
    Route::post('salary/update/{salaryItem}', [App\Http\Controllers\SalaryItemController::class, 'update'])->name('salary.update');
    Route::get('salary/toggle/{salaryItem}', [App\Http\Controllers\SalaryItemController::class, 'toggle'])->name('salary.toggle');
    Route::delete('salary/destroy/{salaryItem}', [App\Http\Controllers\SalaryItemController::class, 'destroy'])->name('salary.destroy');
    
    // Asignaciones de conceptos a empleados
    Route::get('salary/assignments/{salaryItem}', [App\Http\Controllers\SalaryItemController::class, 'assignments'])->name('salary.assignments');
    Route::post('salary/assignments/{salaryItem}', [App\Http\Controllers\SalaryItemController::class, 'saveAssignments'])->name('salary.assignments.save');
    Route::get('salary/toggle-assignment/{salaryItem}/{employee}', [App\Http\Controllers\SalaryItemController::class, 'toggleAssignment'])->name('salary.toggle-assignment');

    // PAYROLL (Liquidación de Sueldos)
    Route::get('payroll/index', [App\Http\Controllers\PayrollController::class, 'index'])->name('payroll.index');
    Route::get('payroll/bulk', [App\Http\Controllers\PayrollController::class, 'bulk'])->name('payroll.bulk');
    Route::get('payroll/closed', [App\Http\Controllers\PayrollController::class, 'closed'])->name('payroll.closed');
    Route::post('payroll/store', [App\Http\Controllers\PayrollController::class, 'store'])->name('payroll.store');
    Route::post('payroll/store-bulk', [App\Http\Controllers\PayrollController::class, 'storeBulk'])->name('payroll.storeBulk');
    Route::get('payroll/{payroll}', [App\Http\Controllers\PayrollController::class, 'show'])->name('payroll.show');
    Route::post('payroll/{payroll}/liquidar', [App\Http\Controllers\PayrollController::class, 'liquidar'])->name('payroll.liquidar');
    Route::post('payroll/{payroll}/pagar', [App\Http\Controllers\PayrollController::class, 'pagar'])->name('payroll.pagar');
    Route::post('payroll/{payroll}/reabrir', [App\Http\Controllers\PayrollController::class, 'reabrir'])->name('payroll.reabrir');
    Route::delete('payroll/{payroll}', [App\Http\Controllers\PayrollController::class, 'destroy'])->name('payroll.destroy');
    Route::post('payroll/liquidar-bulk', [App\Http\Controllers\PayrollController::class, 'liquidarBulk'])->name('payroll.liquidarBulk');
    Route::post('payroll/pagar-bulk', [App\Http\Controllers\PayrollController::class, 'pagarBulk'])->name('payroll.pagarBulk');
    Route::get('payroll/{payroll}/pdf', [App\Http\Controllers\PayrollController::class, 'downloadPdf'])->name('payroll.pdf');

    //VACATIONS (Gestión de Vacaciones)
    Route::get('vacation/index', [App\Http\Controllers\VacationController::class, 'index'])->name('vacation.index');
    Route::post('vacation/store', [App\Http\Controllers\VacationController::class, 'store'])->name('vacation.store');
    Route::get('vacation/approval', [App\Http\Controllers\VacationController::class, 'approvalPanel'])->name('vacation.approval');
    Route::post('vacation/approve/{leave}', [App\Http\Controllers\VacationController::class, 'approve'])->name('vacation.approve');
    Route::post('vacation/reject/{leave}', [App\Http\Controllers\VacationController::class, 'reject'])->name('vacation.reject');
    Route::get('vacation/pdf/{leave}', [App\Http\Controllers\VacationController::class, 'generatePdf'])->name('vacation.pdf');
    Route::get('vacation/calendar', [App\Http\Controllers\VacationController::class, 'calendar'])->name('vacation.calendar');
    Route::get('vacation/calculate-days', [App\Http\Controllers\VacationController::class, 'calculateWorkingDays'])->name('vacation.calculate-days');
    Route::get('vacation/holidays', [App\Http\Controllers\VacationController::class, 'holidays'])->name('vacation.holidays');

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

    Route::get('leave/export/excel', [App\Http\Controllers\LeaveController::class, 'exportExcel'])
        ->name('leave.export.excel');
    
    Route::get('leave/export/pdf', [App\Http\Controllers\LeaveController::class, 'exportPdf'])
        ->name('leave.export.pdf');
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
        

    // TODO: Agregar middleware de permisos cuando estén configurados
    Route::get('users/attach/role',[App\Http\Controllers\UserController::class, 'attachRole'])
        // ->middleware('can:role.new')
        ->name('role.attach');
    Route::get('users/detach/role',[App\Http\Controllers\UserController::class, 'detachRole'])
        // ->middleware('can:role.new')
        ->name('role.detach');
    Route::post('users/sync-roles',[App\Http\Controllers\UserController::class, 'syncRoles'])
        // ->middleware('can:role.new')
        ->name('user.syncRoles');

    //ROLES
    // TODO: Reactivar middleware cuando estén configurados los permisos iniciales

    Route::get('role/new',[App\Http\Controllers\RoleController::class, 'new'])
        // ->middleware('can:role.new')
        ->name('role.new');

    Route::post('role/store',[App\Http\Controllers\RoleController::class, 'store'])
        // ->middleware('can:role.store')
        ->name('role.store');

    Route::post('role/update',[App\Http\Controllers\RoleController::class, 'update'])
        // ->middleware('can:role.store')
        ->name('role.update');

    Route::get('role/edit/{role}',[App\Http\Controllers\RoleController::class, 'edit'])->name('role.edit');
    
    Route::delete('role/destroy/{role}',[App\Http\Controllers\RoleController::class, 'destroy'])
        // ->middleware('can:role.delete')
        ->name('role.destroy');

    Route::get('role/permission/attach/{role}/{permission}',[App\Http\Controllers\RoleController::class, 'attachPermission'])
        // ->middleware('can:role.attachPermission')
        ->name('role.attachPermission');
    
    Route::get('role/permission/detach/{role}/{permission}',[App\Http\Controllers\RoleController::class, 'detachPermission'])
        // ->middleware('can:role.detachPermission')
        ->name('role.detachPermission');

    //PERMISSION
    // TODO: Reactivar middleware cuando estén configurados los permisos iniciales

    Route::get('permission/new',[App\Http\Controllers\PermissionController::class, 'new'])
        // ->middleware('can:permission.new')
        ->name('permission.new');

    Route::post('permission/store',[App\Http\Controllers\PermissionController::class, 'store'])
        // ->middleware('can:permission.store')
        ->name('permission.store');

    Route::get('permission/edit/{permission}',[App\Http\Controllers\PermissionController::class, 'edit'])
        // ->middleware('can:permission.edit')
        ->name('permission.edit');

    Route::put('permission/update/{permission}',[App\Http\Controllers\PermissionController::class, 'update'])
        // ->middleware('can:permission.update')
        ->name('permission.update');

    Route::delete('permission/destroy/{permission}',[App\Http\Controllers\PermissionController::class, 'destroy'])
        // ->middleware('can:permission.destroy')
        ->name('permission.destroy');

    Route::post('permission/attach-role/{permission}/{role}',[App\Http\Controllers\PermissionController::class, 'attachRole'])
        // ->middleware('can:permission.attachRole')
        ->name('permission.attachRole');

    Route::delete('permission/detach-role/{permission}/{role}',[App\Http\Controllers\PermissionController::class, 'detachRole'])
        // ->middleware('can:permission.detachRole')
        ->name('permission.detachRole');

    Route::post('permission/generate-module',[App\Http\Controllers\PermissionController::class, 'generateForModule'])
        // ->middleware('can:permission.generate')
        ->name('permission.generateModule');
});

// EMPLOYEE PORTAL (Vista para usuarios con empleado asociado)
// IMPORTANTE: Debe estar FUERA del middleware check.access para evitar loops
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'has.employee',
])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [App\Http\Controllers\EmployeePortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/team', [App\Http\Controllers\EmployeePortalController::class, 'team'])->name('team');
    Route::get('/directory', [App\Http\Controllers\EmployeePortalController::class, 'directory'])->name('directory');
    
    // Solicitudes
    Route::get('/requests', [App\Http\Controllers\EmployeePortalController::class, 'requests'])->name('requests');
    Route::post('/requests/vacation', [App\Http\Controllers\EmployeePortalController::class, 'storeVacationRequest'])->name('requests.vacation');
    Route::post('/requests/leave', [App\Http\Controllers\EmployeePortalController::class, 'storeLeaveRequest'])->name('requests.leave');
    Route::delete('/requests/{leave}/cancel', [App\Http\Controllers\EmployeePortalController::class, 'cancelRequest'])->name('requests.cancel');
});
