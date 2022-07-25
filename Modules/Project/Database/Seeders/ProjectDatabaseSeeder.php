<?php

namespace Modules\Project\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Modules\Client\Entities\Client;
use Modules\Project\Entities\Project;
use Spatie\Permission\Models\Permission;

class ProjectDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! app()->environment('production')) {
            $client = Client::factory()->create();
            Project::factory()
                ->count(3)
                ->for($client)
                ->create();
        }

        $this->call(ProjectPermissionsTableSeeder::class);
        $this->call(ProjectTeamMemberDatabaseSeeder::class);
        $this->call(ProjectTeamMembersEffortDatabaseSeeder::class);
    }
}
