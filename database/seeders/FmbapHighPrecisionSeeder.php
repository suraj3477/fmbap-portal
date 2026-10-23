<?php

namespace Database\Seeders;

use App\Models\Scheme;
use App\Models\PaymentRequest;
use App\Models\ProgressReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class FmbapHighPrecisionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Correct existing Assam Barak basin schemes
        Scheme::whereIn('scheme_code', ['AS-5', 'AS-6', 'AS-16', 'AS-17', 'AS-18', 'AS-19', 'AS-20'])
            ->update(['river_basin' => 'Barak']);

        // 2. High-Precision North-Eastern States Schemes Data
        $regionalSchemes = [
            // ARUNACHAL PRADESH
            [
                'scheme_code'          => 'AR-01',
                'scheme_name'          => 'Anti-erosion measures to protect Pasighat town along right bank of River Siang',
                'state'                => 'Arunachal Pradesh',
                'district'             => 'East Siang',
                'division'             => 'Pasighat WRD',
                'river_basin'          => 'Brahmaputra (Siang)',
                'plan_period'          => '15th Finance Commission',
                'sanctioned_amount_cr' => 35.50,
                'central_share_pct'    => 90,
                'state_share_pct'      => 10,
                'project_type'         => 'Anti-Erosion & Protection',
                'physical_status'      => 'Ongoing',
                'physical_progress_pct'=> 85.00,
                'is_active'            => true,
                'released_cr'          => 28.75,
                'status'               => PaymentRequest::STATUS_APPROVED,
            ],
            [
                'scheme_code'          => 'AR-02',
                'scheme_name'          => 'Flood management and drainage development of Namsai township along River Noa-Dehing',
                'state'                => 'Arunachal Pradesh',
                'district'             => 'Namsai',
                'division'             => 'Namsai WRD',
                'river_basin'          => 'Brahmaputra (Lohit)',
                'plan_period'          => '15th Finance Commission',
                'sanctioned_amount_cr' => 28.40,
                'central_share_pct'    => 90,
                'state_share_pct'      => 10,
                'project_type'         => 'Drainage & Embankment',
                'physical_status'      => 'Ongoing',
                'physical_progress_pct'=> 72.00,
                'is_active'            => true,
                'released_cr'          => 20.45,
                'status'               => PaymentRequest::STATUS_APPROVED,
            ],

            // MEGHALAYA
            [
                'scheme_code'          => 'ML-01',
                'scheme_name'          => 'Anti-erosion and bank protection works along Ganol river at Tura and surrounding habitations',
                'state'                => 'Meghalaya',
                'district'             => 'West Garo Hills',
                'division'             => 'Tura WRD',
                'river_basin'          => 'Brahmaputra (Ganol)',
                'plan_period'          => '15th Finance Commission',
                'sanctioned_amount_cr' => 18.25,
                'central_share_pct'    => 90,
                'state_share_pct'      => 10,
                'project_type'         => 'Anti-Erosion',
                'physical_status'      => 'Completed',
                'physical_progress_pct'=> 100.00,
                'is_active'            => true,
                'released_cr'          => 16.42,
                'status'               => PaymentRequest::STATUS_APPROVED,
            ],
            [
                'scheme_code'          => 'ML-02',
                'scheme_name'          => 'Drainage improvement and flood mitigation along Simsang river at Williamnagar town reach',
                'state'                => 'Meghalaya',
                'district'             => 'East Garo Hills',
                'division'             => 'Williamnagar WRD',
                'river_basin'          => 'Brahmaputra (Simsang)',
                'plan_period'          => '15th Finance Commission',
                'sanctioned_amount_cr' => 14.80,
                'central_share_pct'    => 90,
                'state_share_pct'      => 10,
                'project_type'         => 'Drainage Congestion',
                'physical_status'      => 'Ongoing',
                'physical_progress_pct'=> 60.00,
                'is_active'            => true,
                'released_cr'          => 8.50,
                'status'               => PaymentRequest::STATUS_APPROVED,
            ],

            // MANIPUR
            [
                'scheme_code'          => 'MN-01',
                'scheme_name'          => 'Comprehensive flood control and protection of Imphal river valley from Singjamei to Lilong',
                'state'                => 'Manipur',
                'district'             => 'Imphal West',
                'division'             => 'Imphal WRD',
                'river_basin'          => 'Barak (Manipur River)',
                'plan_period'          => '15th Finance Commission',
                'sanctioned_amount_cr' => 42.60,
                'central_share_pct'    => 90,
                'state_share_pct'      => 10,
                'project_type'         => 'Embankments & Dykes',
                'physical_status'      => 'Ongoing',
                'physical_progress_pct'=> 78.50,
                'is_active'            => true,
                'released_cr'          => 32.10,
                'status'               => PaymentRequest::STATUS_APPROVED,
            ],
            [
                'scheme_code'          => 'MN-02',
                'scheme_name'          => 'Protection of agricultural land from inundation of Iril river at Porompat reach',
                'state'                => 'Manipur',
                'district'             => 'Imphal East',
                'division'             => 'Porompat WRD',
                'river_basin'          => 'Barak (Iril)',
                'plan_period'          => '15th Finance Commission',
                'sanctioned_amount_cr' => 19.30,
                'central_share_pct'    => 90,
                'state_share_pct'      => 10,
                'project_type'         => 'Flood Management',
                'physical_status'      => 'Ongoing',
                'physical_progress_pct'=> 45.00,
                'is_active'            => true,
                'released_cr'          => 12.00,
                'status'               => PaymentRequest::STATUS_FORWARDED_TO_MOJS,
            ],

            // MIZORAM
            [
                'scheme_code'          => 'MZ-01',
                'scheme_name'          => 'River training and flood mitigation along Tlawng river protecting Sairang corridor',
                'state'                => 'Mizoram',
                'district'             => 'Aizawl',
                'division'             => 'Aizawl WRD',
                'river_basin'          => 'Barak (Tlawng)',
                'plan_period'          => '15th Finance Commission',
                'sanctioned_amount_cr' => 22.10,
                'central_share_pct'    => 90,
                'state_share_pct'      => 10,
                'project_type'         => 'River Training',
                'physical_status'      => 'Ongoing',
                'physical_progress_pct'=> 65.00,
                'is_active'            => true,
                'released_cr'          => 14.50,
                'status'               => PaymentRequest::STATUS_APPROVED,
            ],

            // NAGALAND
            [
                'scheme_code'          => 'NL-01',
                'scheme_name'          => 'Flood protection and anti-erosion measures along Dhansiri river at Dimapur town reach',
                'state'                => 'Nagaland',
                'district'             => 'Dimapur',
                'division'             => 'Dimapur WRD',
                'river_basin'          => 'Brahmaputra (Dhansiri)',
                'plan_period'          => '15th Finance Commission',
                'sanctioned_amount_cr' => 31.75,
                'central_share_pct'    => 90,
                'state_share_pct'      => 10,
                'project_type'         => 'Anti-Erosion & Protection',
                'physical_status'      => 'Ongoing',
                'physical_progress_pct'=> 82.00,
                'is_active'            => true,
                'released_cr'          => 25.80,
                'status'               => PaymentRequest::STATUS_APPROVED,
            ],

            // TRIPURA
            [
                'scheme_code'          => 'TR-01',
                'scheme_name'          => 'Protection of Agartala town and embankment strengthening along Howrah river reach',
                'state'                => 'Tripura',
                'district'             => 'West Tripura',
                'division'             => 'Agartala WRD',
                'river_basin'          => 'Barak (Howrah)',
                'plan_period'          => '15th Finance Commission',
                'sanctioned_amount_cr' => 26.90,
                'central_share_pct'    => 90,
                'state_share_pct'      => 10,
                'project_type'         => 'Embankments & Dykes',
                'physical_status'      => 'Completed',
                'physical_progress_pct'=> 95.00,
                'is_active'            => true,
                'released_cr'          => 24.21,
                'status'               => PaymentRequest::STATUS_APPROVED,
            ],

            // SIKKIM
            [
                'scheme_code'          => 'SK-01',
                'scheme_name'          => 'Bank protection and river training of Teesta river at Singtam and Rangpo urban habitations',
                'state'                => 'Sikkim',
                'district'             => 'East Sikkim',
                'division'             => 'Gangtok WRD',
                'river_basin'          => 'Teesta (Brahmaputra)',
                'plan_period'          => '15th Finance Commission',
                'sanctioned_amount_cr' => 38.20,
                'central_share_pct'    => 90,
                'state_share_pct'      => 10,
                'project_type'         => 'River Training & Protection',
                'physical_status'      => 'Ongoing',
                'physical_progress_pct'=> 88.00,
                'is_active'            => true,
                'released_cr'          => 31.00,
                'status'               => PaymentRequest::STATUS_APPROVED,
            ],
        ];

        $adminUser = User::where('role', 'super_admin')->first() ?: User::first();

        foreach ($regionalSchemes as $item) {
            $released = $item['released_cr'];
            $claimStatus = $item['status'];
            unset($item['released_cr'], $item['status']);

            $scheme = Scheme::updateOrCreate(
                ['scheme_code' => $item['scheme_code']],
                $item
            );

            // Add corresponding Payment Request so financial releases reflect with exact precision
            if ($released > 0) {
                PaymentRequest::updateOrCreate(
                    [
                        'scheme_id'         => $scheme->id,
                        'instalment_number' => 1,
                    ],
                    [
                        'user_id'                  => $adminUser?->id ?: 1,
                        'state'                    => $scheme->state,
                        'status'                   => $claimStatus,
                        'requested_amount_cr'      => $released,
                        'bb_recommended_amount_cr' => $released,
                        'approved_amount_cr'       => $claimStatus === PaymentRequest::STATUS_APPROVED ? $released : null,
                        'sanction_order_no'        => 'MoJS/FMBAP/' . $scheme->state . '/' . $scheme->scheme_code . '/2023-24',
                        'sanction_order_date'      => now()->subMonths(rand(2, 10)),
                        'submitted_at'             => now()->subMonths(rand(3, 11)),
                        'approved_at'              => $claimStatus === PaymentRequest::STATUS_APPROVED ? now()->subMonths(rand(1, 9)) : null,
                        'physical_progress_pct'    => $scheme->physical_progress_pct,
                        'financial_progress_pct'   => round(($released / ($scheme->sanctioned_amount_cr * 0.9)) * 100, 1),
                        'utilization_certificate_path' => 'documents/gfr12a_sample.pdf',
                    ]
                );
            }
        }
    }
}
