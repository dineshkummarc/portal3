<?php

namespace Modules\User\Services;

use Modules\User\Contracts\ProfileServiceContract;
use Modules\HR\Entities\HrJobDomain;
use Modules\Operations\Entities\OfficeLocation;

class ProfileService implements ProfileServiceContract
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // dd(OfficeLocation::all());
        $user = auth()->user();
        $section = request()->input('section', 'basic-details');
        $domains = HrJobDomain::select('id', 'domain')->get()->toArray();
        $officelocation = OfficeLocation::all();

        return ['user' => $user, 'section' => $section, 'domains' =>$domains,'officelocations' => $officelocation];
    }
}
