<?php

namespace Tests\Feature;

use App\Models\PaymentRequest;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemeCatalogueReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheme_catalogue_displays_released_funds_and_curtailments()
    {
        $stateUser = User::factory()->create([
            'role'        => 'state_official',
            'state'       => 'Assam',
            'is_approved' => true,
        ]);

        $scheme = Scheme::create([
            'scheme_code'          => 'AS-100',
            'scheme_name'          => 'Kolong River Embankment Protection',
            'river_basin'          => 'Brahmaputra',
            'district'             => 'Nagaon',
            'division'             => 'Nagaon WRD',
            'state'                => 'Assam',
            'plan_period'          => 'XI Plan',
            'sanctioned_amount_cr' => 6.03,
            'central_share_pct'    => 90,
            'state_share_pct'      => 10,
            'physical_status'      => 'Ongoing',
            'physical_progress_pct'=> 15.00,
            'is_active'            => true,
        ]);

        PaymentRequest::create([
            'scheme_id'              => $scheme->id,
            'user_id'                => $stateUser->id,
            'state'                  => 'Assam',
            'status'                 => 'APPROVED',
            'requested_amount_cr'    => 0.16,
            'approved_amount_cr'     => 0.12,
            'deduction_amount_cr'    => 0.04,
            'curtailment_reason'     => 'Field Inspection / Physical Progress Shortfall',
            'sanction_order_no'      => 'MoJS/FMBAP/ASS/AS-100/2026-13',
            'sanction_order_date'    => '2026-10-06',
            'sanction_order_doc_path'=> '/storage/sanction_orders/test.pdf',
            'instalment_number'      => 1,
            'mojs_decision'          => 'APPROVED',
        ]);

        $response = $this->actingAs($stateUser)->get(route('schemes.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Schemes/Index')
                ->has('schemes', 1)
                ->where('schemes.0.scheme_code', 'AS-100')
                ->where('schemes.0.released_central_share_cr', 0.12)
                ->where('schemes.0.curtailed_amount_cr', 0.04)
                ->where('schemes.0.latest_curtailment_reason', 'Field Inspection / Physical Progress Shortfall')
                ->where('schemes.0.latest_sanction_order_no', 'MoJS/FMBAP/ASS/AS-100/2026-13')
                ->where('stats.total_curtailed', '0.04')
        );
    }
}
