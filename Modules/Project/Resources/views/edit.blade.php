@extends('project::layouts.master')
@section('content')
    <div class="container" id="view_edit_project">
        <br>
        <a href="{{ URL::previous() }}"
            class="text-theme-body text-decoration-none mb-2 mb-xl-4 d-inline-flex align-items-center">
            <span class="mr-1 w-8 h-15 w-xl-12 h-xl-12">
                {!! file_get_contents(public_path('icons/prev-icon.svg')) !!}
            </span><span class="mr-3 w-26 h-15 w-xl-10 h-xl-10">Back</span>
        </a>
        <br>
        <h4 class="c-pointer d-inline-block" v-on:click="counter += 1">{{ $project->name }}</h4>
        <a target="_self" class="badge badge-primary p-1 ml-2 text-light pl-3 pr-3 " target="blank"
            href="{{ route('project.effort-tracking', $project) }}">{{ _('FTE') }}</a>
        <br>
        <div class="mt-2 ml-1">
            <ul class="nav nav-pills mb-2" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" data-toggle="pill" data-target="#projectDetails" type="button"
                        role="tab" aria-selected="true">Project details</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-toggle="pill" data-target="#projectTeamMembers" type="button"
                        role="tab" aria-selected="false">Project team members</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-toggle="pill" data-target="#projectRepository" type="button"
                        role="tab" aria-selected="false">Project repositories</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-toggle="pill" data-target="#projectFinancialDetails" type="button"
                        role="tab" aria-selected="false">Project Financial Details</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-toggle="pill" data-target="#projectTechstack" type="button"
                        role="tab" aria-selected="false">Project Techstack</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-toggle="pill" data-target="#projectResourceRequirement" type="button"
                        role="tab" aria-selected="false">Resource Requirement</a>
                </li>
            </ul>
            @include('status', ['errors' => $errors->all()])
            <div class="tab-content">
                <div class="tab-pane fade show active mb-5" id="projectDetails" role="tabpanel">
                    @include('project::subviews.edit-project-details')
                </div>

                <div class="tab-pane fade mb-5" id="projectTeamMembers" role="tabpanel">
                    @include('project::subviews.edit-project-team-members')
                </div>

                <div class="tab-pane fade mb-5" id="projectRepository" role="tabpanel">
                    @include('project::subviews.edit-project-repository')
                </div>

                <div class="tab-pane fade mb-5" id="projectFinancialDetails" role="tabpanel">
                    @include('project::subviews.edit-project-financial-details')
                </div>

                <div class="tab-pane fade mb-5" id="projectTechstack" role="tabpanel">
                    @include('project::subviews.edit-project-techstack-details')
                </div>

                <div class="tab-pane fade mb-5" id="projectResourceRequirement" role="tabpanel">
                    @include('project::subviews.edit-resource-requirement')
                </div>
            </div>
        </div>
    </div>
@endsection


@section('vue_scripts')
    <script>
        new Vue({
            el: '#view_edit_project',
            data() {
                return {
                    project: @json($project),
                    projectType: "{{ $project->type }}",
                    projectTeamMembers: @json($projectTeamMembers),
                    projectRepositories: @json($projectRepositories),
                    workingDaysInMonth: @json($workingDaysInMonth),
                    users: @json($teamMembers->sortBy('name')->values()),
                    designations: @json($designations),
                    invoiceTerms: @json($invoiceTerms)
                }
            },

            created() {
                this.projectTeamMembers.map((teamMember) => {
                    dailyEffort = teamMember['pivot']['daily_expected_effort'];
                    teamMember['pivot']['weekly_expected_effort'] = dailyEffort * 5; 
                    teamMember['pivot']['monthly_expected_effort'] = dailyEffort * this.workingDaysInMonth;
                })
            },
            
            computed: {
                totalDailyEffort() {
                    var total = 0
                    this.projectTeamMembers.map((teamMember) => {
                        total = total + teamMember['pivot']['daily_expected_effort'];
                    })

                    return total
                }
            },

            methods: {
                defaultProjectTeamMember() {
                    return {
                        id: new Date().getTime(),
                        pivot: {
                            daily_expected_effort: 0,
                            weekly_expected_effort: 0,
                            monthly_expected_effort: 0,
                        }
                    }
                },
                defaultProjectInvoiceTerm() {
                    return {
                        id: 'New',
                        invoice_date: new Date().getTime(),
                        amount: '',
                        status: 'yet-to-be-created',
                        client_acceptance_required: 0,
                        report_required: 0,
                        is_accepted: 0,
                        delivery_report: '',
                        date_change: false,
                        comment: {
                            id: new Date().getTime(),
                            body: '',
                            user: null
                        }
                    }
                },
                defaultProjectRepository() {
                    return {
                        id: new Date().getTime(),
                    }
                },

                updateProjectForm: async function(formId) {
                    $('.save-btn').attr('disabled', true);
                    let formData = new FormData(document.getElementById(formId));
                    $('.save-btn').removeClass('btn-primary').addClass('btn-dark');
                    await axios.post('{{ route('project.update', $project) }}', formData)
                        .then((response) => {
                            $('#edit-project-errors').addClass('d-none');
                            let url = $('#effort_sheet_url').val();
                            if (url) {
                                $('#view_effort_sheet_badge').removeClass('d-none');
                                $('#view_effort_sheet_badge').attr('href', url);
                            } else {
                                $('#view_effort_sheet_badge').addClass('d-none');
                            }
                            $('.save-btn').removeClass('btn-dark').addClass('btn-primary');
                            $('.save-btn').attr('disabled', false);
                            $('#project-details-update-message').addClass('d-block');
                            $('#project-details-update-message').removeClass('d-none');
                            this.$toast.success('Project details updated!');
                            location.reload(true);
                        })
                        .catch((error) => {
                            $('#project-details-update-message').removeClass('d-block');
                            $('#project-details-update-message').addClass('d-none');
                            let errors = error.response.data.errors;
                            $('#edit-project-error-list').empty();
                            for (error in errors) {
                                $('#edit-project-error-list').append("<li class='text-danger ml-2'>" +
                                    errors[error] + "</li>");
                            }
                            $('#edit-project-errors').removeClass('d-none');
                            $('.save-btn').attr('disabled', false);
                            if(errors){
                                var errormessage =  errors[error].join().replace('id','');
                                this.$toast.error(errormessage);                           
                            }
                        })
                },

                addNewProjectTeamMember() {
                    this.projectTeamMembers.push(this.defaultProjectTeamMember());
                },
                addNewProjectRepository() {
                    this.projectRepositories.push(this.defaultProjectRepository());
                },
                addNewProjectInvoiceTerm() {
                    this.invoiceTerms.push(this.defaultProjectInvoiceTerm());
                },
                removeProjectInvoiceTerm(index) {
                    this.invoiceTerms.splice(index, 1);
                },

                removeProjectTeamMember(index) {
                    this.projectTeamMembers.splice(index, 1);
                },
                removeProjectRepository(index) {
                    this.projectRepositories.splice(index, 1);
                },

                updateStartDateForTeamMember($event, index) {
                    newDate = $event.target.value;
                    this.projectTeamMembers[index]['pivot']['started_on'] = newDate;
                },

                updatedDailyExpectedEffort($event, index, numberOfDays) {
                    value = $event.target.value;
                    maximumExpectedEfforts = 12

                    if (numberOfDays == 5) {
                        maximumExpectedEfforts = 60
                    } else if (numberOfDays == this.workingDaysInMonth) {
                        maximumExpectedEfforts = 276
                    }

                    if (value > maximumExpectedEfforts) {
                        if(! confirm('are you sure you want to enter more than ' + maximumExpectedEfforts + ' hours in expected effort?')) {
                            $event.target.value = value.slice(0, -1)
                            return
                        }

                        if (numberOfDays == 5) {
                            this.projectTeamMembers[index]['pivot']['daily_expected_effort'] = value/5;
                            this.projectTeamMembers[index]['pivot']['weekly_expected_effort'] = value;
                            this.projectTeamMembers[index]['pivot']['monthly_expected_effort'] = (value/5) * this.workingDaysInMonth;
                        } else if (numberOfDays == this.workingDaysInMonth) {
                            this.projectTeamMembers[index]['pivot']['daily_expected_effort'] = value/numberOfDays;
                            this.projectTeamMembers[index]['pivot']['weekly_expected_effort'] = (value/numberOfDays) * 5;
                            this.projectTeamMembers[index]['pivot']['monthly_expected_effort'] = value;
                        } else {
                            this.projectTeamMembers[index]['pivot']['daily_expected_effort'] = value;
                            this.projectTeamMembers[index]['pivot']['weekly_expected_effort'] = value * 5;
                            this.projectTeamMembers[index]['pivot']['monthly_expected_effort'] = value * this.workingDaysInMonth;
                        }
                        this.projectTeamMembers[index]['pivot']['daily_expected_effort'] = value/numberOfDays;
                        this.$forceUpdate()
                    }
                },

                handleFileUpload(event) {
                    const fileInput = event.target;
                    this.toggleSections(fileInput.files.length > 0);
                },

                toggleSectionsByContracts() {
                    const hasContracts = this.project.project_contracts.length > 0;
                    this.toggleSections(hasContracts);
                },

                handleBillingCycle(event) {
                    const projectType = event.target.value;
                    this.toggleSectionsByProjectType(projectType);
                },

                toggleSections(condition) {
                    const linkDiv = document.getElementById('client-financial-detail-link');
                    const invoiceTermDiv = document.getElementById('invoice-terms-section');

                    if (condition) {
                        if (this.projectType === 'monthly-billing') {
                            linkDiv.classList.remove('d-none');
                            invoiceTermDiv.classList.add('d-none');
                        } else if (this.projectType === 'fixed-budget') {
                            linkDiv.classList.add('d-none');
                            invoiceTermDiv.classList.remove('d-none');
                        }
                    } else {
                        linkDiv.classList.add('d-none');
                        invoiceTermDiv.classList.add('d-none');
                    }
                },

                toggleSectionsByProjectType(projectType) {
                    const linkDiv = document.getElementById('client-financial-detail-link');
                    const invoiceTermDiv = document.getElementById('invoice-terms-section');

                    if (projectType === 'monthly-billing') {
                        linkDiv.classList.remove('d-none');
                        invoiceTermDiv.classList.add('d-none');
                    } else if (projectType === 'fixed-budget') {
                        linkDiv.classList.add('d-none');
                        invoiceTermDiv.classList.remove('d-none');
                    } else {
                        linkDiv.classList.add('d-none');
                        invoiceTermDiv.classList.add('d-none');
                    }
                },
                getFileName(filePath) {
                    return filePath.split('\\').pop().split('/').pop();
                },
                getDeliveryReportUrl(invoiceTermId) {
                    return `{{ route('delivery-report.show', ':id') }}`.replace(':id', invoiceTermId);
                },
                toggleDelayReason(index){
                    const invoiceTermComment= this.invoiceTerms[index].comment
                    console.log(this.invoiceTerms[index].invoice_date, index);
                    return invoiceTermComment && invoiceTermComment.body !== "";
                },
                formatDate(dateString) {
                    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
                    return new Date(dateString).toISOString().slice(0, 10);
                },
                isDateChange(index) {
                    this.$set(this.invoiceTerms[index], 'date_change', true);
                }
            },

            filters: {
                toDate: function(timestamp) {
                    if (timestamp == null) {
                        return timestamp;
                    }
                    return timestamp.substring(0,10);
                }
            },

            mounted() {
                document.getElementById('contract_file').addEventListener('change', this.handleFileUpload);
                document.getElementById('project_type').addEventListener('change', this.handleBillingCycle);
                this.toggleSectionsByContracts();
            }
        });
    </script>
@endsection