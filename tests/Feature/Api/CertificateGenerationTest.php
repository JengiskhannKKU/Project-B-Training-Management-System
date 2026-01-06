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
}
