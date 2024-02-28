<?php

namespace Modules\Project\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Client\Entities\Client;
use Modules\EffortTracking\Services\EffortTrackingService;
use Modules\HR\Entities\HrJobDomain;
use Modules\HR\Entities\Job;
use Modules\Project\Contracts\ProjectServiceContract;
use Modules\Project\Entities\Project;
use Modules\Project\Entities\ProjectContract;
use Modules\Project\Http\Requests\ProjectRequest;
use Modules\Project\Rules\ProjectNameExist;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    protected $service;

    public function __construct(ProjectServiceContract $service)
    {
        $this->authorizeResource(Project::class);
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = $this->service->index(request()->all());

        return view('project::index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = $this->service->getClients($status = 'all');

        return view('project::create')->with('clients', $clients);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ProjectRequest $request
     */
    public function store(ProjectRequest $request)
    {
        $validated = $request->validated();
        $this->service->store($validated);

        return redirect(route('project.index'))->with('success', 'Project has been created successfully!');
    }

    /**
     * Show the specified resource.
     *
     * @param Project $project
     */
    public function show(Project $project)
    {
        $contract = ProjectContract::where('project_id', $project->id)->first();
        $contractFilePath = $contract ? storage_path('app/' . $contract->contract_file_path) : null;
        $currentDate = today(config('constants.timezone.indian'));

        if (now(config('constants.timezone.indian'))->format('H:i:s') < config('efforttracking.update_date_count_after_time')) {
            $currentDate = $currentDate->subDay();
        }
        $daysTillToday = count($project->getWorkingDaysList($project->client->month_start_date, $currentDate));

        $effortTracking = new EffortTrackingService;
        $isApprovedWorkPipelineExist = $effortTracking->getIsApprovedWorkPipelineExist($project->effort_sheet_url);

        return view('project::show', [
            'project' => $project,
            'contract' => $contract,
            'contractFilePath' => $contractFilePath,
            'daysTillToday' => $daysTillToday,
            'isApprovedWorkPipelineExist' => $isApprovedWorkPipelineExist,
        ]);
    }

    public function destroy(ProjectRequest $request, Project $project)
    {
        $project->update(
            [
                'reason_for_deletion' => $request['comment'],
            ]
        );
        $project->delete();

        return redirect()->back()->with('status', 'Project deleted successfully!');
    }

    public static function showPdf(ProjectContract $contract)
    {
        $filePath = storage_path('app/' . $contract->contract_file_path);
        $content = file_get_contents($filePath);
        $contractFileName = pathinfo($contract->contract_file_path)['filename'];

        return response($content)->withHeaders([
            'content-type' => mime_content_type($filePath),
            'contractFileName' => $contractFileName,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Project $project
     */
    public function edit(Project $project)
    {
        $designations = $this->service->getDesignations();
        $designationKeys = array_keys($designations);

        return view('project::edit', [
            'project' => $project,
            'clients' => Client::orderBy('name')->get(),
            'teamMembers' => $this->service->getTeamMembers(),
            'projectTeamMembers' => $this->service->getProjectTeamMembers($project),
            'projectRepositories' => $this->service->getProjectRepositories($project),
            'designations' => $designations,
            'workingDaysInMonth' => $this->service->getWorkingDays($project),
            'designationKeys' => $designationKeys,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ProjectRequest $request
     */
    public function update(ProjectRequest $request, Project $project)
    {
        $request->merge([
            'name' => trim(preg_replace('/\s\s+/', ' ', str_replace("\n", ' ', $request->name))),
        ]);
        if ($request->name != $project->name) {
            $request->validate(['name' => new ProjectNameExist()]);
        }

        return $this->service->updateProjectData($request->all(), $project);
    }

    public function projectFTEExport(Request $request)
    {
        $filters = $request->all();

        return $this->service->projectFTEExport($filters);
    }

    public function projectResource()
    {
        $resourceData = $this->service->getProjectsWithTeamMemberRequirementData(request()->all());
        $domainName = HrJobDomain::all();
        $jobName = Job::all();

        return view('project::resource-requirement', [
            'resourceData' => $resourceData,
            'domainName' => $domainName,
            'jobName' => $jobName,
        ]);
    }
}
