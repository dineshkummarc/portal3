<ul class="nav nav-pills">
    <li class="nav-item">
        @php
          $params = array_merge(['name' => 'employee'], ['status' => 'current']);
        @endphp
        <a class="nav-item nav-link {{ request()->input('name') === 'employee' ? 'active' : '' }}" href="{{ route('employees',$params) }}"><i class="fa fa-users"></i>&nbsp; Employees </a>
    </li>
    <li class="nav-item">
        @php
          $params = array_merge(['name' => 'intern'], ['status' => 'current']);
        @endphp
        <a class="nav-item nav-link {{ request()->input('name') === 'intern' ? 'active' : '' }}" href="{{ route('employees', $params) }}"><i class="fa fa-users"></i>&nbsp;Intern</a>
    </li>

    <li class="nav-item">
        @php 
          $params = array_merge(['name' => 'contractor'], ['status' => 'current']);
        @endphp
        <a class="nav-item nav-link {{ request()->input('name') === 'contractor' ? 'active' : '' }}" href="{{ route('employees', $params) }}"><i class="fa fa-users"></i>&nbsp;Contractor</a>
    </li>

    <li class="nav-item">
        @php
          $params = array_merge(['name' => 'support-staff'], ['status' => 'current']);
        @endphp
        <a class="nav-item nav-link {{ request()->input('name') === 'support-staff' ? 'active' : '' }}" href="{{ route('employees', $params) }}"><i class="fa fa-users"></i>&nbsp;Support Staff</a>
    </li>

    <li class="nav-item">
        <a class="nav-item nav-link {{ Request::is('hr/employee-reports*') ? 'active' : '' }}" href="{{ route('employees.reports') }}"><i class="fa fa-pie-chart"></i>&nbsp;Reports</a>
    </li>
</ul>
