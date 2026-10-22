<?php

namespace Tests\Feature;

use App\Models\PaymentRequest;
use App\Models\Scheme;
use App\Models\User;
use App\Models\FmbapProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AmountInRupeesAndWordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_request_accepts_whole_rupee_amount_and_converts_to_crore(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'role' => 'state_official',
            'state' => 'Assam',
            'is_approved' => true,
        ]);

        $scheme = Scheme::create([
            'scheme_code' => 'TEST-INR-01',
            'scheme_name' => 'Test River Embankment INR',
            'state' => 'Assam',
            'division' => 'Guwahati',
            'estimated_cost_lakh' => 608.00,
            'sanctioned_amount_cr' => 6.08,
            'central_share_pct' => 90,
            'state_share_pct' => 10,
            'physical_status' => 'Ongoing',
            'is_active' => true,
        ]);

        // State initiates claim with requested_amount_inr = 16,00,000 (16 Lakhs / 0.16 Cr)
        $response = $this->actingAs($user)->postJson(route('fund-release.store'), [
            'step' => 2,
            'scheme_id' => $scheme->id,
            'requested_amount_inr' => 1600000,
            'instalment_number' => 1,
            'funding_pattern' => '90/10',
            'estimated_cost_cr' => 6.08,
            'bank_details' => 'SBI Guwahati',
            'state_remarks' => 'Claim for mobilisation advance',
        ]);

        $response->assertStatus(200);

        $claim = PaymentRequest::where('scheme_id', $scheme->id)->first();
        $this->assertNotNull($claim);
        $this->assertEquals(0.16, (float) $claim->requested_amount_cr);
    }

    public function test_mojs_sanction_accepts_approved_amount_inr_and_converts_to_crore(): void
    {
        Storage::fake('public');
        $mojsUser = User::factory()->create([
            'role' => 'mojs_official',
            'is_approved' => true,
        ]);

        $scheme = Scheme::create([
            'scheme_code' => 'TEST-INR-02',
            'scheme_name' => 'Test Anti Erosion Project',
            'state' => 'Assam',
            'sanctioned_amount_cr' => 10.00,
            'central_share_pct' => 90,
            'state_share_pct' => 10,
            'is_active' => true,
        ]);

        $claim = PaymentRequest::create([
            'scheme_id' => $scheme->id,
            'user_id' => $mojsUser->id,
            'state' => 'Assam',
            'status' => PaymentRequest::STATUS_FORWARDED_TO_MOJS,
            'requested_amount_cr' => 0.16,
            'instalment_number' => 1,
        ]);

        // MoJS sanctions ₹10,00,000 (0.10 Cr)
        $response = $this->actingAs($mojsUser)->post(route('fund-release.mojsDecision', $claim->id), [
            'decision' => 'APPROVED',
            'approved_amount_inr' => 1000000,
            'remarks' => 'Sanctioned ₹10,00,000 as per utilization certificate review.',
        ]);

        $response->assertSessionHasNoErrors();

        $claim->refresh();
        $this->assertEquals(0.10, (float) $claim->approved_amount_cr);
        $this->assertEquals(0.06, (float) $claim->deduction_amount_cr);
        $this->assertEquals(PaymentRequest::STATUS_APPROVED, $claim->status);
    }

    public function test_fmbap_project_quick_release_accepts_requested_amount_inr(): void
    {
        Storage::fake('public');
        $stateUser = User::factory()->create([
            'role' => 'state_official',
            'state' => 'Assam',
            'is_approved' => true,
        ]);

        $scheme = Scheme::create([
            'scheme_code' => 'TEST-INR-03',
            'scheme_name' => 'Test Quick Release Scheme',
            'state' => 'Assam',
            'sanctioned_amount_cr' => 5.00,
            'central_share_pct' => 90,
            'state_share_pct' => 10,
            'is_active' => true,
        ]);

        $project = FmbapProject::create([
            'scheme_code' => 'TEST-INR-03',
            'scheme_name' => 'Test Quick Release Project',
            'state' => 'Assam',
            'user_id' => $stateUser->id,
            'status' => 'SUBMITTED_BY_STATE',
        ]);

        $pdf = UploadedFile::fake()->create('uc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($stateUser)->post(route('fmbap.projects.requestRelease', $project->id), [
            'instalment_number' => 1,
            'requested_amount_inr' => 2500000, // ₹25,00,000 -> 0.25 Cr
            'bank_details' => 'HDFC Bank, Guwahati',
            'utilization_certificate' => $pdf,
        ]);

        $response->assertSessionHasNoErrors();

        $claim = PaymentRequest::where('scheme_id', $scheme->id)->first();
        $this->assertNotNull($claim);
        $this->assertEquals(0.25, (float) $claim->requested_amount_cr);
    }
}
