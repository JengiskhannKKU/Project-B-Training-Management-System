<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Role;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $trainer;
    protected User $otherTrainer;
    protected User $admin;
    protected Program $program;
    protected TrainingSession $completedSession;
    protected TrainingSession $openSession;
    protected TrainingSession $closedSession;
    protected Enrollment $enrollment;

    /**
     * Setup test data before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            ['label' => 'Student']
        );

        $trainerRole = Role::firstOrCreate(
            ['name' => 'trainer'],
            ['label' => 'Trainer']
        );

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['label' => 'Admin']
        );

        // Create users
        $this->student = User::factory()->create([
            'role_id' => $studentRole->id,
            'status' => 'active',
        ]);

        $this->trainer = User::factory()->create([
            'role_id' => $trainerRole->id,
            'status' => 'active',
        ]);

        $this->otherTrainer = User::factory()->create([
            'role_id' => $trainerRole->id,
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        // Create a program
        $this->program = Program::factory()->create([
            'created_by' => $this->trainer->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        // Create completed session
        $this->completedSession = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
            'status' => 'completed',
            'approval_status' => 'approved',
        ]);

        // Create open session
        $this->openSession = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
            'status' => 'open',
            'approval_status' => 'approved',
        ]);

        // Create closed session
        $this->closedSession = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
            'status' => 'closed',
            'approval_status' => 'approved',
        ]);

        // Create enrollment with completed status
        $this->enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'session_id' => $this->completedSession->id,
            'status' => 'completed',
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);
    }

    /**
     * Test: KAN-342 - Trainer of a completed session can generate certificates successfully
     */
    public function test_trainer_can_generate_certificates_for_completed_session(): void
    {
        // Act: Trainer generates certificates for their completed session
        $response = $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ])
            ->assertJson([
                'message' => 'Certificates generated successfully.',
            ]);

        // Verify certificate was created in database
        $this->assertDatabaseHas('certificates', [
            'session_id' => $this->completedSession->id,
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
        ]);
    }

    /**
     * Test: KAN-343 - Trainer attempting to generate for open session gets error
     */
    public function test_trainer_cannot_generate_certificates_for_open_session(): void
    {
        // Act: Trainer tries to generate certificates for an open session
        $response = $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->openSession->id}/certificates/generate");

        // Assert
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJson([
                'errors' => [
                    'status' => ['Session must be completed before generating certificates.'],
                ],
            ]);

        // Verify no certificate was created
        $this->assertDatabaseMissing('certificates', [
            'session_id' => $this->openSession->id,
        ]);
    }

    /**
     * Test: KAN-343 - Trainer attempting to generate for closed session gets error
     */
    public function test_trainer_cannot_generate_certificates_for_closed_session(): void
    {
        // Act: Trainer tries to generate certificates for a closed session
        $response = $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->closedSession->id}/certificates/generate");

        // Assert
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJson([
                'errors' => [
                    'status' => ['Session must be completed before generating certificates.'],
                ],
            ]);

        // Verify no certificate was created
        $this->assertDatabaseMissing('certificates', [
            'session_id' => $this->closedSession->id,
        ]);
    }

    /**
     * Test: KAN-344 - Student attempting to generate certificates gets forbidden
     */
    public function test_student_cannot_generate_certificates(): void
    {
        // Act: Student tries to generate certificates
        $response = $this->actingAs($this->student)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        // Assert: Student is blocked by role middleware (403 Unauthorized)
        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);

        // Verify no certificate was created
        $this->assertDatabaseMissing('certificates', [
            'session_id' => $this->completedSession->id,
            'issued_by' => $this->student->id,
        ]);
    }

    /**
     * Test: KAN-344 - Other trainer (not session owner) attempting to generate gets forbidden
     */
    public function test_other_trainer_cannot_generate_certificates(): void
    {
        // Act: Different trainer tries to generate certificates for another trainer's session
        $response = $this->actingAs($this->otherTrainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        // Assert
        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ])
            ->assertJson([
                'message' => 'Only the session trainer or admin can generate certificates.',
            ]);

        // Verify no certificate was created
        $this->assertDatabaseMissing('certificates', [
            'session_id' => $this->completedSession->id,
            'issued_by' => $this->otherTrainer->id,
        ]);
    }

    /**
     * Test: KAN-345 - Admin can generate certificates for all sessions
     */
    public function test_admin_can_generate_certificates_for_any_session(): void
    {
        // Act: Admin generates certificates for any session
        $response = $this->actingAs($this->admin)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ])
            ->assertJson([
                'message' => 'Certificates generated successfully.',
            ]);

        // Verify certificate was created in database
        $this->assertDatabaseHas('certificates', [
            'session_id' => $this->completedSession->id,
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->student->id,
        ]);
    }

    /**
     * Test: Unauthenticated user cannot generate certificates
     */
    public function test_unauthenticated_user_cannot_generate_certificates(): void
    {
        // Act: Unauthenticated request
        $response = $this->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        // Assert
        $response->assertStatus(401);

        // Verify no certificate was created
        $this->assertDatabaseMissing('certificates', [
            'session_id' => $this->completedSession->id,
        ]);
    }

    /**
     * Test: KAN-347 - First time generation with 10 completed enrollments
     */
    public function test_first_generation_creates_all_certificates(): void
    {
        // Arrange: Create 10 completed enrollments
        $students = User::factory()->count(10)->create([
            'role_id' => Role::where('name', 'student')->first()->id,
            'status' => 'active',
        ]);

        foreach ($students as $student) {
            Enrollment::create([
                'user_id' => $student->id,
                'session_id' => $this->completedSession->id,
                'status' => 'completed',
                'enrolled_at' => now(),
                'completed_at' => now(),
            ]);
        }

        // Act: Generate certificates for the first time
        $response = $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Certificates generated successfully.',
                'data' => [
                    'created' => 11, // 10 new + 1 from setUp
                    'skipped' => 0,
                ],
            ]);

        // Verify all certificates were created
        $this->assertDatabaseCount('certificates', 11);
    }

    /**
     * Test: KAN-348 - Idempotency: Adding 2 more completed students and regenerating
     */
    public function test_regeneration_only_creates_new_certificates(): void
    {
        // Arrange: Create initial 10 completed enrollments and generate certificates
        $students = User::factory()->count(10)->create([
            'role_id' => Role::where('name', 'student')->first()->id,
            'status' => 'active',
        ]);

        foreach ($students as $student) {
            Enrollment::create([
                'user_id' => $student->id,
                'session_id' => $this->completedSession->id,
                'status' => 'completed',
                'enrolled_at' => now(),
                'completed_at' => now(),
            ]);
        }

        // First generation
        $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        // Add 2 more completed students
        $newStudents = User::factory()->count(2)->create([
            'role_id' => Role::where('name', 'student')->first()->id,
            'status' => 'active',
        ]);

        foreach ($newStudents as $student) {
            Enrollment::create([
                'user_id' => $student->id,
                'session_id' => $this->completedSession->id,
                'status' => 'completed',
                'enrolled_at' => now(),
                'completed_at' => now(),
            ]);
        }

        // Act: Generate certificates again
        $response = $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        // Assert: Only 2 new certificates created, 11 skipped (10 + 1 from setUp)
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Certificates generated successfully.',
                'data' => [
                    'created' => 2,
                    'skipped' => 11, // 10 + 1 from setUp
                ],
            ]);

        // Verify total certificates
        $this->assertDatabaseCount('certificates', 13); // 11 from first + 2 new
    }

    /**
     * Test: KAN-349 - Program-level generation: Student with multiple session completions gets only one certificate
     */
    public function test_program_level_generation_no_duplicates_for_same_student(): void
    {
        // Arrange: Create 3 completed sessions in the same program
        $session2 = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
            'status' => 'completed',
            'approval_status' => 'approved',
        ]);

        $session3 = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
            'status' => 'completed',
            'approval_status' => 'approved',
        ]);

        // Student completes all 3 sessions
        Enrollment::create([
            'user_id' => $this->student->id,
            'session_id' => $session2->id,
            'status' => 'completed',
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'session_id' => $session3->id,
            'status' => 'completed',
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);

        // Act: Generate program-level certificates
        $response = $this->actingAs($this->admin)
            ->postJson("/api/programs/{$this->program->id}/certificates/generate");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        // Verify: Student has only ONE program-level certificate despite completing 3 sessions
        $programCertificates = \App\Models\Certificate::where('user_id', $this->student->id)
            ->where('program_id', $this->program->id)
            ->whereNull('session_id') // Program-level certificates have null session_id
            ->get();

        $this->assertCount(1, $programCertificates, 'Student should have exactly one program-level certificate');
    }

    /**
     * Test: KAN-351 - Student can see their certificate in dashboard
     */
    public function test_student_can_see_certificate_in_dashboard(): void
    {
        // Arrange: Generate certificate
        $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        // Act: Student fetches their certificates
        $response = $this->actingAs($this->student)
            ->getJson('/api/me/certificates');

        // Assert: Student sees their certificate
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'certificate_code',
                        'status',
                        'issued_at',
                        'program_id',
                        'session_id',
                    ],
                ],
            ]);

        // Verify the certificate exists in the response
        $certificates = $response->json('data');
        $this->assertNotEmpty($certificates);
        $this->assertEquals('valid', $certificates[0]['status']);
        $this->assertEquals($this->completedSession->program_id, $certificates[0]['program_id']);
    }

    /**
     * Test: KAN-351 - Trainer can see certificates for their session
     */
    public function test_trainer_can_see_session_certificates(): void
    {
        // Arrange: Generate certificates
        $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        // Act: Trainer fetches session certificates
        $response = $this->actingAs($this->trainer)
            ->getJson("/api/sessions/{$this->completedSession->id}/certificates");

        // Assert: Trainer sees certificates for their session
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'certificate_code',
                        'status',
                        'user_id',
                        'session_id',
                    ],
                ],
            ]);

        // Verify certificates exist
        $certificates = $response->json('data');
        $this->assertNotEmpty($certificates);
        $this->assertEquals($this->completedSession->id, $certificates[0]['session_id']);
    }

    /**
     * Test: KAN-352 - Certificate revocation changes status to revoked
     */
    public function test_certificate_revocation_changes_status(): void
    {
        // Arrange: Generate certificate
        $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        $certificate = \App\Models\Certificate::where('user_id', $this->student->id)
            ->where('session_id', $this->completedSession->id)
            ->first();

        $this->assertNotNull($certificate);
        $this->assertEquals('valid', $certificate->status);

        // Act: Admin revokes the certificate
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/certificates/{$certificate->id}/revoke", [
                'note' => 'Test revocation',
            ]);

        // Assert: Revocation successful
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Certificate revoked successfully.',
            ]);

        // Verify status changed to revoked in database
        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'status' => 'revoked',
        ]);

        // Verify student sees revoked status in dashboard
        $studentResponse = $this->actingAs($this->student)
            ->getJson('/api/me/certificates');

        $studentCertificates = $studentResponse->json('data');
        $revokedCert = collect($studentCertificates)->firstWhere('id', $certificate->id);
        $this->assertEquals('revoked', $revokedCert['status']);

        // Verify trainer sees revoked status in session certificates
        $trainerResponse = $this->actingAs($this->trainer)
            ->getJson("/api/sessions/{$this->completedSession->id}/certificates");

        $trainerCertificates = $trainerResponse->json('data');
        $revokedCertTrainer = collect($trainerCertificates)->firstWhere('id', $certificate->id);
        $this->assertEquals('revoked', $revokedCertTrainer['status']);
    }

    /**
     * Test: KAN-352 - Certificate verification shows revoked status
     */
    public function test_certificate_verification_shows_revoked_status(): void
    {
        // Arrange: Generate and revoke certificate
        $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        $certificate = \App\Models\Certificate::where('user_id', $this->student->id)
            ->where('session_id', $this->completedSession->id)
            ->first();

        // Revoke the certificate
        $this->actingAs($this->admin)
            ->postJson("/api/admin/certificates/{$certificate->id}/revoke", [
                'note' => 'Test verification',
            ]);

        // Act: Verify the certificate using certificate code
        $response = $this->getJson("/api/certificates/verify/{$certificate->certificate_code}");

        // Assert: Verification shows revoked/invalid status
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'certificate_code',
                    'status',
                    'is_valid',
                ],
            ])
            ->assertJson([
                'data' => [
                    'status' => 'revoked',
                    'is_valid' => false,
                ],
            ]);
    }

    /**
     * Test: KAN-388 - Eager generation creates PDF files immediately
     */
    public function test_eager_generation_creates_files_immediately(): void
    {
        // Arrange: Create a global template so it has a template_id
        $globalTemplate = \App\Models\CertificateTemplate::create([
            'name' => 'Global Template',
            'scope' => 'global',
            'layout_config' => ['canvas' => ['width' => 1600, 'height' => 1200]],
            'font_size' => 24,
            'text_color' => '#0000ff',
            'is_active' => true,
        ]);

        // Act: Generate certificates with eager generation enabled
        $response = $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate", [
                'eager_generation' => true,
            ]);

        // Assert: Generation successful
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Certificates generated successfully.',
                'data' => [
                    'created' => 1,
                    'skipped' => 0,
                ],
            ]);

        // Verify certificate was created with file data
        $certificate = \App\Models\Certificate::where('user_id', $this->student->id)
            ->where('session_id', $this->completedSession->id)
            ->first();

        $this->assertNotNull($certificate);
        $this->assertNotNull($certificate->certificate_code);
        $this->assertNotNull($certificate->file_data, 'File data should be generated immediately with eager generation');
        $this->assertNotNull($certificate->file_mime_type, 'File MIME type should be set');
        $this->assertNotNull($certificate->file_size, 'File size should be set');
        $this->assertNotNull($certificate->generated_at, 'Generated timestamp should be set');
        $this->assertEquals($globalTemplate->id, $certificate->template_id, 'Template ID should be set to global template');
    }

    /**
     * Test: KAN-388 - Lazy generation (default) does not create PDF files immediately
     */
    public function test_lazy_generation_does_not_create_files_immediately(): void
    {
        // Act: Generate certificates without eager generation (default)
        $response = $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate");

        // Assert: Generation successful
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Certificates generated successfully.',
                'data' => [
                    'created' => 1,
                    'skipped' => 0,
                ],
            ]);

        // Verify certificate was created without file data
        $certificate = \App\Models\Certificate::where('user_id', $this->student->id)
            ->where('session_id', $this->completedSession->id)
            ->first();

        $this->assertNotNull($certificate);
        $this->assertNotNull($certificate->certificate_code);
        $this->assertNull($certificate->file_data, 'File data should NOT be generated immediately with lazy generation');
        $this->assertNull($certificate->file_mime_type, 'File MIME type should NOT be set');
        $this->assertNull($certificate->file_size, 'File size should NOT be set');
        $this->assertNull($certificate->generated_at, 'Generated timestamp should NOT be set');
    }

    /**
     * Test: KAN-388 - Program-level eager generation creates files for all certificates
     */
    public function test_program_level_eager_generation_creates_files(): void
    {
        // Arrange: Create additional completed session
        $session2 = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
            'status' => 'completed',
            'approval_status' => 'approved',
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'session_id' => $session2->id,
            'status' => 'completed',
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);

        // Act: Generate program-level certificates with eager generation
        $response = $this->actingAs($this->admin)
            ->postJson("/api/programs/{$this->program->id}/certificates/generate", [
                'eager_generation' => true,
            ]);

        // Assert: Generation successful
        $response->assertStatus(200);

        // Verify program-level certificate has file data
        $certificate = \App\Models\Certificate::where('user_id', $this->student->id)
            ->where('program_id', $this->program->id)
            ->whereNull('session_id')
            ->first();

        $this->assertNotNull($certificate);
        $this->assertNotNull($certificate->file_data, 'Program certificate should have file data with eager generation');
        $this->assertNotNull($certificate->file_mime_type);
        $this->assertNotNull($certificate->generated_at);
    }

    /**
     * Test: KAN-390 - Session template takes priority over program template
     */
    public function test_session_template_takes_priority_over_program_template(): void
    {
        // Arrange: Create session template and program template
        $sessionTemplate = \App\Models\CertificateTemplate::create([
            'name' => 'Session Template',
            'scope' => 'session',
            'session_id' => $this->completedSession->id,
            'layout_config' => ['canvas' => ['width' => 1600, 'height' => 1200]],
            'font_size' => 32,
            'text_color' => '#ff0000',
            'is_active' => true,
        ]);

        $programTemplate = \App\Models\CertificateTemplate::create([
            'name' => 'Program Template',
            'scope' => 'program',
            'program_id' => $this->program->id,
            'layout_config' => ['canvas' => ['width' => 1600, 'height' => 1200]],
            'font_size' => 28,
            'text_color' => '#00ff00',
            'is_active' => true,
        ]);

        // Act: Generate certificates with eager generation
        $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate", [
                'eager_generation' => true,
            ]);

        // Assert: Certificate uses session template (not program template)
        $certificate = \App\Models\Certificate::where('session_id', $this->completedSession->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertNotNull($certificate);
        $this->assertEquals($sessionTemplate->id, $certificate->template_id, 'Certificate should use session template');
    }

    /**
     * Test: KAN-390 - Program template takes priority over global template
     */
    public function test_program_template_takes_priority_over_global_template(): void
    {
        // Arrange: Create global template and program template (no session template)
        $globalTemplate = \App\Models\CertificateTemplate::create([
            'name' => 'Global Template',
            'scope' => 'global',
            'layout_config' => ['canvas' => ['width' => 1600, 'height' => 1200]],
            'font_size' => 24,
            'text_color' => '#0000ff',
            'is_active' => true,
        ]);

        $programTemplate = \App\Models\CertificateTemplate::create([
            'name' => 'Program Template',
            'scope' => 'program',
            'program_id' => $this->program->id,
            'layout_config' => ['canvas' => ['width' => 1600, 'height' => 1200]],
            'font_size' => 28,
            'text_color' => '#00ff00',
            'is_active' => true,
        ]);

        // Act: Generate certificates with eager generation
        $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate", [
                'eager_generation' => true,
            ]);

        // Assert: Certificate uses program template (not global template)
        $certificate = \App\Models\Certificate::where('session_id', $this->completedSession->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertNotNull($certificate);
        $this->assertEquals($programTemplate->id, $certificate->template_id, 'Certificate should use program template');
    }

    /**
     * Test: KAN-390 - Global template is used when no session or program template exists
     */
    public function test_global_template_is_used_when_no_specific_template_exists(): void
    {
        // Arrange: Create only a global template (no session or program template)
        $globalTemplate = \App\Models\CertificateTemplate::create([
            'name' => 'Global Template',
            'scope' => 'global',
            'layout_config' => ['canvas' => ['width' => 1600, 'height' => 1200]],
            'font_size' => 24,
            'text_color' => '#0000ff',
            'is_active' => true,
        ]);

        // Act: Generate certificates with eager generation
        $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate", [
                'eager_generation' => true,
            ]);

        // Assert: Certificate uses global template
        $certificate = \App\Models\Certificate::where('session_id', $this->completedSession->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertNotNull($certificate);
        $this->assertEquals($globalTemplate->id, $certificate->template_id, 'Certificate should use global template');
    }

    /**
     * Test: KAN-391 - Fallback template is used when no template exists at all
     */
    public function test_fallback_template_is_used_when_no_templates_exist(): void
    {
        // Arrange: Ensure no templates exist in database
        \App\Models\CertificateTemplate::query()->delete();

        // Act: Generate certificates with eager generation
        $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate", [
                'eager_generation' => true,
            ]);

        // Assert: Certificate is generated successfully with fallback template
        $certificate = \App\Models\Certificate::where('session_id', $this->completedSession->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertNotNull($certificate);
        $this->assertNull($certificate->template_id, 'Certificate should use fallback template (no template_id)');
        $this->assertNotNull($certificate->file_data, 'File should be generated even with fallback template');
        $this->assertNotNull($certificate->file_mime_type);
        $this->assertNotNull($certificate->generated_at);
    }

    /**
     * Test: KAN-390 - Complete template selection order verification
     */
    public function test_template_selection_order_all_templates_present(): void
    {
        // Arrange: Create all types of templates
        $sessionTemplate = \App\Models\CertificateTemplate::create([
            'name' => 'Session Template',
            'scope' => 'session',
            'session_id' => $this->completedSession->id,
            'layout_config' => ['canvas' => ['width' => 1600, 'height' => 1200]],
            'font_size' => 32,
            'text_color' => '#ff0000',
            'is_active' => true,
        ]);

        $programTemplate = \App\Models\CertificateTemplate::create([
            'name' => 'Program Template',
            'scope' => 'program',
            'program_id' => $this->program->id,
            'layout_config' => ['canvas' => ['width' => 1600, 'height' => 1200]],
            'font_size' => 28,
            'text_color' => '#00ff00',
            'is_active' => true,
        ]);

        $globalTemplate = \App\Models\CertificateTemplate::create([
            'name' => 'Global Template',
            'scope' => 'global',
            'layout_config' => ['canvas' => ['width' => 1600, 'height' => 1200]],
            'font_size' => 24,
            'text_color' => '#0000ff',
            'is_active' => true,
        ]);

        // Act: Generate certificates
        $this->actingAs($this->trainer)
            ->postJson("/api/sessions/{$this->completedSession->id}/certificates/generate", [
                'eager_generation' => true,
            ]);

        // Assert: Session template should be selected (highest priority)
        $certificate = \App\Models\Certificate::where('session_id', $this->completedSession->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertNotNull($certificate);
        $this->assertEquals($sessionTemplate->id, $certificate->template_id);
        $this->assertNotEquals($programTemplate->id, $certificate->template_id);
        $this->assertNotEquals($globalTemplate->id, $certificate->template_id);
    }
}
