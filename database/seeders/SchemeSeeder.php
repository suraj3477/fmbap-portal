<?php

namespace Database\Seeders;

use App\Models\FmbapProject;
use App\Models\Scheme;
use Illuminate\Database\Seeder;

class SchemeSeeder extends Seeder
{
    /**
     * Seed schemes by extracting unique projects from FmbapProject table.
     */
    public function run(): void
    {
        $existingProjects = FmbapProject::select(
            'scheme_code',
            'scheme_name',
            'state',
            'estimated_cost_cr',
            'central_share_cr',
            'state_share_cr'
        )->distinct()->get();

        foreach ($existingProjects as $proj) {
            if (!$proj->scheme_code) continue;

            // Calculate percentage safely
            $totalCost = (float) $proj->estimated_cost_cr;
            $centralShare = (float) $proj->central_share_cr;
            
            $centralPct = 90;
            $statePct = 10;

            if ($totalCost > 0) {
                $centralPct = round(($centralShare / $totalCost) * 100);
                $statePct = 100 - $centralPct;
            }

            Scheme::updateOrCreate(
                ['scheme_code' => $proj->scheme_code],
                [
                    'scheme_name'          => $proj->scheme_name ?? 'Untitled Scheme',
                    'river_basin'          => 'Brahmaputra',
                    'district'             => null,
                    'state'                => $proj->state ?? 'Assam',
                    'sanctioned_amount_cr' => $totalCost,
                    'central_share_pct'    => $centralPct,
                    'state_share_pct'      => $statePct,
                    'project_type'         => 'Flood Management',
                    'is_active'            => true,
                ]
            );
        }

        $this->command->info('Scheme catalogue seeded from legacy FmbapProject data.');
    }
}
