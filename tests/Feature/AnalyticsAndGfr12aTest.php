<?php

namespace Tests\Feature;

use App\Models\FmbapProject;
use App\Models\PaymentRequest;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAndGfr12aTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Scheme $scheme;
    protected PaymentRequest $paymentRequest;
    protected FmbapProject $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::create([
            'name' => 'Admin User',
            'email' => 'testadmin@gov.in',
            'password' => bcrypt('password@123'),
            'role' => 'super_admin',
            'is_approved' => true,
        ]);

        $this->scheme = Scheme::first() ?? Scheme::create([
            'scheme_code' => 'TEST-01',
            'scheme_name' => 'Test Anti-Erosion Scheme',
            'state' => 'Assam',
            'river_basin' => 'Brahmaputra',
            'sanctioned_amount_cr' => 12.50,
            'central_share_pct' => 90,
            'state_share_pct' => 10,
            'physical_status' => 'Ongoing',
            'physical_progress_pct' => 45.0,
            'is_active' => true,
        ]);

        $this->paymentRequest = PaymentRequest::first() ?? PaymentRequest::create([
            'scheme_id' => $this->scheme->id,
            'user_id' => $this->user->id,
            'state' => 'Assam',
            'status' => PaymentRequest::STATUS_SUBMITTED_TO_BB,
            'requested_amount_cr' => 3.50,
            'instalment_number' => 1,
            'physical_progress_pct' => 45.0,
            'financial_progress_pct' => 40.0,
            'submitted_at' => now(),
        ]);

        $this->project = FmbapProject::first() ?? FmbapProject::create([
            'scheme_code' => $this->scheme->scheme_code,
            'scheme_name' => $this->scheme->scheme_name,
            'user_id' => $this->user->id,
            'state' => 'Assam',
            'status' => 'APPROVED',
            'estimated_cost_cr' => 12.50,
            'central_share_cr' => 11.25,
            'state_share_cr' => 1.25,
        ]);
    }

    public function test_analytics_cockpit_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/analytics');
        $response->assertStatus(200);
    }

    public function test_analytics_cockpit_with_filters(): void
    {
        $response = $this->actingAs($this->user)->get('/analytics?state=Assam&basin=Brahmaputra');
        $response->assertStatus(200);
    }

    public function test_analytics_excel_export(): void
    {
        $response = $this->actingAs($this->user)->get('/analytics/export-excel');
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), '.xlsx'));
    }

    public function test_analytics_excel_export_with_filters(): void
    {
        $response = $this->actingAs($this->user)->get('/analytics/export-excel?state=Assam&basin=Brahmaputra');
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), '.xlsx'));
    }

    public function test_analytics_pdf_export(): void
    {
        $response = $this->actingAs($this->user)->get('/analytics/export-pdf');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_analytics_pdf_export_with_filters(): void
    {
        $response = $this->actingAs($this->user)->get('/analytics/export-pdf?state=Assam&basin=Brahmaputra');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_gfr12a_prefill_data_endpoint(): void
    {
        $response = $this->actingAs($this->user)->get('/fund-release/gfr12a/data?scheme_id=' . $this->scheme->id);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'scheme_id',
            'scheme_code',
            'financial_year',
            'central_amount_cr',
            'state_amount_cr',
            'total_amount_cr',
            'utilized_amount_cr',
            'unspent_balance_cr',
        ]);
    }

    public function test_gfr12a_pdf_generation_download(): void
    {
        $payload = [
            'scheme_id'              => $this->scheme->id,
            'payment_request_id'     => $this->paymentRequest->id,
            'financial_year'         => '2026-2027',
            'instalment_number'      => 1,
            'sanction_letter_no'     => 'MoJS/FMBAP/AS/2026/01',
            'sanction_date'          => '15/05/2026',
            'central_amount_cr'      => 3.50,
            'state_amount_cr'        => 0.38,
            'total_amount_cr'        => 3.88,
            'utilized_amount_cr'     => 2.10,
            'unspent_balance_cr'     => 1.78,
            'interest_accrued_cr'    => 0.00,
            'physical_progress_pct'  => 45.0,
            'financial_progress_pct' => 40.0,
            'officer_name'           => 'Er. Test Engineer',
            'officer_designation'    => 'Executive Engineer, WRD',
            'action'                 => 'download',
        ];

        $response = $this->actingAs($this->user)->post('/fund-release/gfr12a/generate', $payload);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_gfr12a_attach_to_claim(): void
    {
        $payload = [
            'scheme_id'              => $this->scheme->id,
            'payment_request_id'     => $this->paymentRequest->id,
            'financial_year'         => '2026-2027',
            'instalment_number'      => 1,
            'sanction_letter_no'     => 'MoJS/FMBAP/AS/2026/01',
            'sanction_date'          => '15/05/2026',
            'central_amount_cr'      => 3.50,
            'state_amount_cr'        => 0.38,
            'total_amount_cr'        => 3.88,
            'utilized_amount_cr'     => 2.10,
            'unspent_balance_cr'     => 1.78,
            'interest_accrued_cr'    => 0.00,
            'physical_progress_pct'  => 45.0,
            'financial_progress_pct' => 40.0,
            'officer_name'           => 'Er. Test Engineer',
            'officer_designation'    => 'Executive Engineer, WRD',
            'action'                 => 'attach',
        ];

        $response = $this->actingAs($this->user)->post('/fund-release/gfr12a/generate', $payload);
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Statutory Form GFR-12A generated and attached to claim successfully.',
        ]);

        $this->paymentRequest->refresh();
        $this->assertNotNull($this->paymentRequest->utilization_certificate_path);
    }

    public function test_payment_request_dossier_pdf_download(): void
    {
        $response = $this->actingAs($this->user)->get('/fund-release/' . $this->paymentRequest->id . '/dossier');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_fmbap_project_pdf_download(): void
    {
        $response = $this->actingAs($this->user)->get('/fmbap/projects/' . $this->project->id . '/pdf');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
