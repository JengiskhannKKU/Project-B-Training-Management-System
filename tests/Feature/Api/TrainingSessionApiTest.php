<?php

namespace Tests\Feature\Api;

use App\Models\Program;
use App\Models\Role;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingSessionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $trainer;
    protected Program $program;
    protected string $token;

    /**
     * Setup authenticated user before each test.
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

        // Create users
        $this->user = User::factory()->create([
            'role_id' => $studentRole->id,
            'status' => 'active',
        ]);

        $this->trainer = User::factory()->create([
            'role_id' => $trainerRole->id,
            'status' => 'active',
        ]);

        // Create a program for testing
        $this->program = Program::factory()->create();

        // Create API token
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test: GET /api/sessions - List all sessions
     */
    public function test_can_get_all_sessions(): void
    {
        // Arrange: Create some sessions
        TrainingSession::factory()->count(3)->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
        ]);

        // Act: Send GET request
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/sessions');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'program_id',
                        'title',
                        'start_date',
                        'end_date',
                        'capacity',
                        'trainer_id',
                        'status',
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Sessions retrieved successfully',
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test: GET /api/sessions - Empty list
     */
    public function test_can_get_empty_session_list(): void
    {
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/sessions');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sessions retrieved successfully',
                'data' => []
            ]);
    }

    /**
     * Test: GET /api/sessions?program_id=X - Filter by program
     */
    public function test_can_filter_sessions_by_program(): void
    {
        // Arrange: Create another program
        $anotherProgram = Program::factory()->create();

        // Create sessions for both programs
        TrainingSession::factory()->count(2)->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
        ]);

        TrainingSession::factory()->count(3)->create([
            'program_id' => $anotherProgram->id,
            'trainer_id' => $this->trainer->id,
        ]);

        // Act: Filter by first program
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/sessions?program_id={$this->program->id}");

        // Assert: Should only get 2 sessions
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // All sessions should belong to the filtered program
        $sessions = $response->json('data');
        foreach ($sessions as $session) {
            $this->assertEquals($this->program->id, $session['program_id']);
        }
    }

    /**
     * Test: GET /api/sessions/{id} - Get single session
     */
    public function test_can_get_single_session(): void
    {
        // Arrange
        $session = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
            'title' => 'Laravel Advanced - Batch 1',
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/sessions/{$session->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Session retrieved successfully',
                'data' => [
                    'id' => $session->id,
                    'title' => 'Laravel Advanced - Batch 1',
                    'program_id' => $this->program->id,
                ]
            ]);
    }

    /**
     * Test: GET /api/sessions/{id} - 404 when not found
     */
    public function test_returns_404_when_session_not_found(): void
    {
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/sessions/99999');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test: POST /api/sessions - Create new session
     */
    public function test_can_create_session(): void
    {
        // Arrange
        $sessionData = [
            'program_id' => $this->program->id,
            'title' => 'Vue.js Fundamentals - Batch 1',
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'capacity' => 30,
            'trainer_id' => $this->trainer->id,
            'location' => 'Room A101',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/sessions', $sessionData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Session created successfully',
                'data' => [
                    'title' => 'Vue.js Fundamentals - Batch 1',
                    'program_id' => $this->program->id,
                    'capacity' => 30,
                ]
            ]);

        // Check database
        $this->assertDatabaseHas('training_sessions', [
            'title' => 'Vue.js Fundamentals - Batch 1',
            'program_id' => $this->program->id,
        ]);
    }

    /**
     * Test: POST /api/sessions - Validation fails when required fields missing
     */
    public function test_create_session_validation_fails_when_required_fields_missing(): void
    {
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/sessions', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'program_id',
                'title',
                'start_date',
                'end_date',
                'capacity',
                'trainer_id'
            ]);
    }

    /**
     * Test: POST /api/sessions - Validation: start_date must be before end_date
     */
    public function test_create_session_fails_when_start_date_after_end_date(): void
    {
        // Arrange: Invalid dates
        $sessionData = [
            'program_id' => $this->program->id,
            'title' => 'Test Session',
            'start_date' => '2025-02-28',  // After end_date
            'end_date' => '2025-02-01',
            'capacity' => 30,
            'trainer_id' => $this->trainer->id,
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/sessions', $sessionData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    /**
     * Test: POST /api/sessions - Validation: end_time must be after start_time
     */
    public function test_create_session_fails_when_end_time_before_start_time(): void
    {
        // Arrange
        $sessionData = [
            'program_id' => $this->program->id,
            'title' => 'Test Session',
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'start_time' => '17:00',  // After end_time
            'end_time' => '09:00',
            'capacity' => 30,
            'trainer_id' => $this->trainer->id,
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/sessions', $sessionData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);
    }

    /**
     * Test: POST /api/sessions - Validation: capacity must be at least 1
     */
    public function test_create_session_fails_when_capacity_is_zero(): void
    {
        // Arrange
        $sessionData = [
            'program_id' => $this->program->id,
            'title' => 'Test Session',
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'capacity' => 0,  // Invalid
            'trainer_id' => $this->trainer->id,
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/sessions', $sessionData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['capacity']);
    }

    /**
     * Test: POST /api/sessions - Validation: program_id must exist
     */
    public function test_create_session_fails_when_program_not_exists(): void
    {
        // Arrange
        $sessionData = [
            'program_id' => 99999,  // Non-existent
            'title' => 'Test Session',
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'capacity' => 30,
            'trainer_id' => $this->trainer->id,
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/sessions', $sessionData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['program_id']);
    }

    /**
     * Test: POST /api/sessions - Validation: trainer_id must exist
     */
    public function test_create_session_fails_when_trainer_not_exists(): void
    {
        // Arrange
        $sessionData = [
            'program_id' => $this->program->id,
            'title' => 'Test Session',
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'capacity' => 30,
            'trainer_id' => 99999,  // Non-existent
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/sessions', $sessionData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trainer_id']);
    }

    /**
     * Test: POST /api/sessions - Validation: invalid status
     */
    public function test_create_session_fails_with_invalid_status(): void
    {
        // Arrange
        $sessionData = [
            'program_id' => $this->program->id,
            'title' => 'Test Session',
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'capacity' => 30,
            'trainer_id' => $this->trainer->id,
            'status' => 'invalid-status',  // Invalid
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/sessions', $sessionData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test: PUT /api/sessions/{id} - Update session
     */
    public function test_can_update_session(): void
    {
        // Arrange
        $session = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
            'title' => 'Original Title',
            'capacity' => 20,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/sessions/{$session->id}", [
            'title' => 'Updated Title',
            'capacity' => 40,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Session updated successfully',
                'data' => [
                    'id' => $session->id,
                    'title' => 'Updated Title',
                    'capacity' => 40,
                ]
            ]);

        // Check database
        $this->assertDatabaseHas('training_sessions', [
            'id' => $session->id,
            'title' => 'Updated Title',
            'capacity' => 40,
        ]);
    }

    /**
     * Test: PUT /api/sessions/{id} - Partial update
     */
    public function test_can_partially_update_session(): void
    {
        // Arrange
        $session = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
            'title' => 'Original Title',
            'capacity' => 20,
        ]);

        // Act: Update only title
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/sessions/{$session->id}", [
            'title' => 'New Title Only',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'New Title Only',
                    'capacity' => 20,  // Unchanged
                ]
            ]);
    }

    /**
     * Test: PUT /api/sessions/{id} - Update with date validation
     */
    public function test_update_session_validates_dates(): void
    {
        // Arrange
        $session = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
        ]);

        // Act: Try to set end_date before start_date
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/sessions/{$session->id}", [
            'end_date' => '2025-01-15',  // Before start_date
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /**
     * Test: DELETE /api/sessions/{id} - Delete session
     */
    public function test_can_delete_session(): void
    {
        // Arrange
        $session = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/sessions/{$session->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Session deleted successfully',
                'data' => null,
            ]);

        // Check database
        $this->assertDatabaseMissing('training_sessions', [
            'id' => $session->id,
        ]);
    }

    /**
     * Test: DELETE /api/sessions/{id} - 404 when not found
     */
    public function test_delete_returns_404_when_session_not_found(): void
    {
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson('/api/sessions/99999');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test: Unauthenticated requests are rejected
     */
    public function test_unauthenticated_requests_are_rejected(): void
    {
        // Act
        $response = $this->getJson('/api/sessions');

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    /**
     * Test: Response format is consistent
     */
    public function test_all_success_responses_have_consistent_format(): void
    {
        // Create a session
        $session = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
        ]);

        // Test GET all
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/sessions');

        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);

        // Test GET single
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/sessions/{$session->id}");

        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
    }
}
