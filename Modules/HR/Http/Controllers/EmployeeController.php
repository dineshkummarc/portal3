<?php

namespace Modules\HR\Http\Controllers;

use App\Services\EmployeeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\HR\Entities\Assessment;
use Modules\HR\Entities\Employee;
use Modules\HR\Entities\HrJobDesignation;
use Modules\HR\Entities\HrJobDomain;
use Modules\HR\Entities\IndividualAssessment;
use Modules\HR\Entities\Job;
use Modules\HR\Exports\EmployeePayrollExport;
use Modules\Project\Entities\ProjectTeamMember;
use Modules\Invoice\Services\CurrencyService;

class EmployeeController extends Controller
{
    use AuthorizesRequests;

    protected $service;

    public function __construct(EmployeeService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('list', Employee::class);
        $filters = $request->all() ?: $this->service->defaultFilters();

        return view('hr.employees.index', $this->service->index($filters));
    }

    public function listPayroll(Request $request)
    {
        $this->authorize('listPayroll', Employee::class);
        $filters = $request->all() ?: $this->service->defaultFilters();
        $data = $this->service->getEmployeeListWithLatestPayroll($filters);

        return view('hr.payroll.index', $data);
    }

    public function show(Employee $employee)
    {
        $user = $employee->user()->withTrashed()->first();

        return view('hr.employees.show', compact('employee', 'user'));
    }

    public function reports()
    {
        $this->authorize('reports');

        return view('hr.employees.reports');
    }
    public function basicDetails(Employee $employee)
    {
        $domains = HrJobDomain::select('id', 'domain')->get()->toArray();
        $designations = HrJobDesignation::select('id', 'designation')->get()->toArray();
        $domainIndex = '';

        return view('hr.employees.basic-details', ['domainIndex' => $domainIndex, 'employee' => $employee, 'domains' => $domains, 'designations' => $designations]);
    }

    public function showFTEdata(Request $request)
    {
        $domainId = $request->domain_id;
        $employees = Employee::where('domain_id', $domainId)->get();
        $domainName = HrJobDomain::all();
        $jobName = Job::all();

        return view('hr.employees.fte-handler')->with([
            'domainName' => $domainName,
            'employees' => $employees,
            'jobName' => $jobName,
        ]);
    }

    public function employeeWorkHistory(Employee $employee)
    {
        $employeesDetails = ProjectTeamMember::where('team_member_id', $employee->user_id)->get()->unique('project_id');

        return view('hr.employees.employee-work-history', compact('employeesDetails'));
    }

    public function reviewDetails(Employee $employee)
    {
        $assessments = Assessment::where('reviewee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $employees = Employee::whereHas('user', function ($query) {
            $query->whereNull('deleted_at');
        })->get();

        return view('hr.employees.review-details', ['employee' => $employee, 'employees' => $employees, 'assessments' => $assessments]);
    }

    public function createIndividualAssessment(Request $request)
    {
        $reviewStatuses = [
            'self' => $request->self,
            'mentor' => $request->mentor,
            'hr' => $request->hr,
            'manager' => $request->manager,
        ];
        $assessmentId = $request->assessmentId;
        $reviewStatus = $reviewStatuses[$request->review_type] ?? '';

        $individualAssessment = IndividualAssessment::firstOrNew([
            'assessment_id' => $assessmentId,
            'type' => $request->review_type,
        ]);

        $individualAssessment->fill([
            'reviewer_id' => $request->reviewer_id,
            'status' => $reviewStatus,
        ])->save();

        return redirect()->back()->with('success', $individualAssessment->wasRecentlyCreated ? 'Review saved successfully.' : 'Review status updated successfully.');
    }

    public function updateEmployeeReviewers(Request $request, Employee $employee)
    {
        // Update the employee reviewers data
        $employee->update([
            'hr_id' => $request->hr_id,
            'mentor_id' => $request->mentor_id,
            'manager_id' => $request->manager_id,
        ]);

        return redirect()->back();
    }

    public function downloadPayRoll()
    {
        $employees = $this->service->getEmployeeListForExport();
        $today = date('Y-m-d');
        $filename = 'PayRoll Report-' . $today . '.xlsx';

        return Excel::download(new EmployeePayrollExport($employees['employees']), $filename);
    }

    public function hrDetails(Employee $employee)
    {
        return view('hr.employees.hr-details', ['employee' => $employee]);
    }

    public function employeeEarningValue(Employee $employee)
    {
        $data = $this->service->fetchEmployeeEarnings($employee->id);
    
        foreach ($data['employees'] as &$employeeData) {
            $currency = $employeeData['currency'];
            $rateAfterConversion = $this->getTotalServiceRates($currency);
            $employeeData['rate_after_conversion'] = $rateAfterConversion;
            $employeeData['total_amount_after_conversion'] = $rateAfterConversion * $employeeData['actual_effort'] * $employeeData['service_rates'];
        }
            //   echo json_encode($data, JSON_PRETTY_PRINT);
        return view('finance.employees.index', $data);
    }
    

    public function getTotalServiceRates($currency) {
        $conversionRates = new CurrencyService();
        $conversionRate = $conversionRates->getAllCurrentRatesInINR();
        $initial = config('invoice.currency_initials');
        $service_rates_value = 0;

        switch (strtoupper($currency)) {
            case $initial['usd']:
                $service_rates_value = $conversionRate['USDINR'];
                break;

            case $initial['eur']:
                $service_rates_value = round(($conversionRate['USDINR']) / ($conversionRate['USDEUR']), 2);
                break;

            case $initial['swi']:
                $service_rates_value = round(($conversionRate['USDINR']) / ($conversionRate['USDCHF']), 2);
                break;
        }

       return $service_rates_value;
    }

    public function financialdetails(Employee $employee)
    {
        return view('hr.employees.financial-details', ['employee' => $employee]);
    }
}
