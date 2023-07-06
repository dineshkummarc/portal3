<?php

namespace Modules\CodeTrek\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Operations\Entities\OfficeLocation;
use Carbon\Carbon;

class CodeTrekApplicant extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function roundDetails()
    {
        return $this->hasMany(CodeTrekApplicantRoundDetail::class);
    }

    public function center()
    {
        return $this->belongsTo(OfficeLocation::class, 'center_id');
    }
    
    public function getDaysInCodetrekAttribute(CodeTrekApplicant $applicant)
    {
        $internDate = Carbon::parse($applicant->internship_start_date);

        if ($applicant->status == "completed") {
            return $internDate->diffInDays($applicant->start_date);
        }

        return now()->diffInDays($applicant->start_date);
    }
}
