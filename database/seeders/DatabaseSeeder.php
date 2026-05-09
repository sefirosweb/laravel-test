<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Sefirosweb\LaravelAccessList\Seeders\AclDemoSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Calls the package's AclDemoSeeder so
     * a fresh `php artisan migrate:fresh --seed` populates 100 users /
     * 15 groups / 50 accesses with their relations wired up.
     */
    public function run(): void
    {
        $this->call(AclDemoSeeder::class);
    }
}
