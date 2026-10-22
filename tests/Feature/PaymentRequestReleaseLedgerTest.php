<?php

namespace Tests\Feature;

use App\Models\PaymentRequest;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRequestReleaseLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_edit_fund_release_loads_mojs_sanction_ledger_and_balance()
    {
        $stateUser = User::factory()->create([
            'role'        => 'state_official',
            'state'       => 'Assam',
            'is_approved' => true,
        ]);

        $scheme = Scheme::create([
            'scheme_code'          => 'AS-200',
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
            'physical_progress_pct'=> 20.00,
            'is_active'            => true,
        ]);

        // Prior Approved Claim (Instalment 1)
        PaymentRequest::create([
            'scheme_id'              => $scheme->id,
            'user_id'                => $stateUser->id,
            'state'                  => 'Assam',
            'status'                 => 'APPROVED',
            'requested_amount_cr'    => 0.16,
            'approved_amount_cr'     => 0.12,
            'deduction_amount_cr'    => 0.04,
            'curtailment_reason'     => 'Field Inspection / Physical Progress Shortfall',
            'sanction_order_no'      => 'MoJS/FMBAP/ASS/AS-200/2026-1',
            'sanction_order_date'    => '2026-10-06',
            'instalment_number'      => 1,
            'mojs_decision'          => 'APPROVED',
        ]);

        // Test GET /fund-release/create?scheme_id=...
        $response = $this->actingAs($stateUser)->get(route('fund-release.create', ['scheme_id' => $scheme->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('FundRelease/Create')
                ->where('preselectedSchemeId', $scheme->id)
                ->where('availableSchemes.0.id', $scheme->id)
                ->where('availableSchemes.0.approved_claims_release_cr', 0.12)
                ->where('availableSchemes.0.curtailed_amount_cr', 0.04)
                ->where('availableSchemes.0.balance_central_share_cr', 5.31)
                ->where('availableSchemes.0.next_suggested_instalment', 2)
                ->where('availableSchemes.0.past_approved_claims.0.sanction_order_no', 'MoJS/FMBAP/ASS/AS-200/2026-1')
        );

        // Test GET /api/schemes/search
        $searchResponse = $this->actingAs($stateUser)->getJson(route('schemes.search', ['q' => 'AS-200']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertJsonFragment([
            'scheme_code'              => 'AS-200',
            'approved_claims_release_cr'=> 0.12,
            'balance_central_share_cr'  => 5.31,
            'next_suggested_instalment' => 2,
            'has_active_claim'          => false,
        ]);
    }

    public function test_single_active_claim_enforced_per_scheme()
    {
        $stateUser = User::factory()->create([
            'role'        => 'state_official',
            'state'       => 'Assam',
            'is_approved' => true,
        ]);

        $scheme = Scheme::create([
            'scheme_code'          => 'AS-201',
            'scheme_name'          => 'Dikhow River Bank Protection',
            'river_basin'          => 'Brahmaputra',
            'district'             => 'Sivasagar',
            'division'             => 'Sivasagar WRD',
            'state'                => 'Assam',
            'plan_period'          => 'XI Plan',
            'sanctioned_amount_cr' => 10.00,
            'central_share_pct'    => 90,
            'state_share_pct'      => 10,
            'physical_status'      => 'Ongoing',
            'physical_progress_pct'=> 35.00,
            'is_active'            => true,
        ]);

        // 1. Create an active claim under review (SUBMITTED_TO_BB)
        $activeClaim = PaymentRequest::create([
            'scheme_id'           => $scheme->id,
            'user_id'             => $stateUser->id,
            'state'               => 'Assam',
            'status'              => PaymentRequest::STATUS_SUBMITTED_TO_BB,
            'requested_amount_cr' => 1.50,
            'instalment_number'   => 1,
            'submitted_at'        => now(),
        ]);

        // 2. GET /fund-release/create?scheme_id=... should redirect to show existing claim with warning
        $response = $this->actingAs($stateUser)->get(route('fund-release.create', ['scheme_id' => $scheme->id]));
        $response->assertRedirect(route('fund-release.show', $activeClaim->id));
        $response->assertSessionHas('warning');

        // 3. POST /fund-release store on Step 1 should be rejected with 422
        $storeResponse = $this->actingAs($stateUser)->postJson(route('fund-release.store'), [
            'step'      => 1,
            'scheme_id' => $scheme->id,
        ]);
        $storeResponse->assertStatus(422);
        $storeResponse->assertJsonFragment([
            'active_claim_id' => $activeClaim->id,
        ]);

        // 4. Scheme enrichFinancialMetrics and search API should flag has_active_claim as true
        $searchResponse = $this->actingAs($stateUser)->getJson(route('schemes.search', ['q' => 'AS-201']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertJsonFragment([
            'scheme_code'      => 'AS-201',
            'has_active_claim' => true,
        ]);

        // 5. If active claim needs correction, GET /fund-release/create should redirect to edit
        $activeClaim->update(['status' => PaymentRequest::STATUS_NEEDS_CORRECTION]);
        $editRedirectResponse = $this->actingAs($stateUser)->get(route('fund-release.create', ['scheme_id' => $scheme->id]));
        $editRedirectResponse->assertRedirect(route('fund-release.edit', $activeClaim->id));

        // 6. Once fulfilled (APPROVED), initiating next instalment is permitted
        $activeClaim->update([
            'status'             => PaymentRequest::STATUS_APPROVED,
            'approved_amount_cr' => 1.50,
        ]);

        $fulfilledResponse = $this->actingAs($stateUser)->get(route('fund-release.create', ['scheme_id' => $scheme->id]));
        $fulfilledResponse->assertStatus(200);
        $fulfilledResponse->assertInertia(fn ($page) =>
            $page->component('FundRelease/Create')
                ->where('preselectedSchemeId', $scheme->id)
                ->where('availableSchemes.0.has_active_claim', false)
                ->where('availableSchemes.0.next_suggested_instalment', 2)
        );
    }
}
