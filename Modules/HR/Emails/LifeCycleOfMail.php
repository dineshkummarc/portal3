<?php

namespace Modules\HR\Emails;

use App\Mail\NotifyExpiredLifeCycleEmail;
use Illuminate\Console\Command;
use Modules\HR\Entities\Application;
use Illuminate\Support\Facades\Mail;

class LifeCycleOfMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'application:lifecycle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Streamline job application life cycle';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dates = Application::whereIn('status', ['new', 'in_progress'])->pluck('created_at');
        $expiredApplicationNumber = 0;
        foreach ($dates as $date) {
            $difference_days = $date->diffInDays(now());
            if ($difference_days > config('hr.time-period.outdated')) {
                $expiredApplicationNumber += 1;
            }
        }

        return (Mail::to(config('hr.applications-life-cycle.email'))->queue(new NotifyExpiredLifeCycleEmail($expiredApplicationNumber))); 
    }
}
