<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FmbapProject;

class FmbapProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            [
                'project_code' => 'BB-FMP-AS-001',
                'title' => 'Anti-Erosion & Bank Protection Works at Majuli Island (Phase IV)',
                'state' => 'Assam',
                'district' => 'Majuli',
                'river_basin' => 'Brahmaputra',
                'component' => 'FMP',
                'sanctioned_cost_cr' => 185.50,
                'central_share_cr' => 166.95,
                'state_share_cr' => 18.55,
                'funds_released_cr' => 120.00,
                'funds_utilized_cr' => 112.40,
                'physical_progress_pct' => 68,
                'status' => 'Work in Progress',
                'inspection_notes' => 'Geo-bag aprons intact; revetment work ongoing on the upper reach.',
            ],
            [
                'project_code' => 'BB-RMBA-AR-002',
                'title' => 'Flood Mitigation & Embankment Strengthening along Siang River Border Zone',
                'state' => 'Arunachal Pradesh',
                'district' => 'East Siang',
                'river_basin' => 'Siang / Brahmaputra',
                'component' => 'RMBA',
                'sanctioned_cost_cr' => 94.20,
                'central_share_cr' => 84.78,
                'state_share_cr' => 9.42,
                'funds_released_cr' => 45.00,
                'funds_utilized_cr' => 41.10,
                'physical_progress_pct' => 42,
                'status' => 'Work in Progress',
                'inspection_notes' => 'Site mobilization completed; boulder pitching under monitoring.',
            ],
            [
                'project_code' => 'BB-FMP-AS-003',
                'title' => 'Protection of Dibrugarh Town from Erosion of River Brahmaputra',
                'state' => 'Assam',
                'district' => 'Dibrugarh',
                'river_basin' => 'Brahmaputra',
                'component' => 'FMP',
                'sanctioned_cost_cr' => 210.00,
                'central_share_cr' => 189.00,
                'state_share_cr' => 21.00,
                'funds_released_cr' => 210.00,
                'funds_utilized_cr' => 208.50,
                'physical_progress_pct' => 95,
                'status' => 'Completed',
                'inspection_notes' => 'Final technical inspection completed; quality parameters verified.',
            ],
            [
                'project_code' => 'BB-RMBA-MN-004',
                'title' => 'Anti-Erosion Measures for Border Tributaries in Imphal Basin',
                'state' => 'Manipur',
                'district' => 'Imphal East',
                'river_basin' => 'Barak / Imphal',
                'component' => 'RMBA',
                'sanctioned_cost_cr' => 62.80,
                'central_share_cr' => 56.52,
                'state_share_cr' => 6.28,
                'funds_released_cr' => 15.00,
                'funds_utilized_cr' => 10.20,
                'physical_progress_pct' => 25,
                'status' => 'Monsoon Delayed',
                'inspection_notes' => 'Heavy rainfall caused temporary site delays; resume work post-monsoon.',
            ],
            [
                'project_code' => 'BB-FMP-TR-005',
                'title' => 'Flood Embankment & River Protection Scheme along Muhuri River Area',
                'state' => 'Tripura',
                'district' => 'South Tripura',
                'river_basin' => 'Muhuri',
                'component' => 'FMP',
                'sanctioned_cost_cr' => 48.00,
                'central_share_cr' => 43.20,
                'state_share_cr' => 4.80,
                'funds_released_cr' => 0.00,
                'funds_utilized_cr' => 0.00,
                'physical_progress_pct' => 0,
                'status' => 'In Technical Review',
                'inspection_notes' => 'DPR submitted by state; awaiting hydrological committee feedback.',
            ],
        ];

        foreach ($projects as $project) {
            FmbapProject::create($project);
        }
    }
}