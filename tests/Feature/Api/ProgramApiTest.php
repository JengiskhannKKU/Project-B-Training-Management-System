<?php

namespace Tests\Feature\Api;

use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    /**
     * Setup authenticated user before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with student role (default role from registration)
        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            ['label' => 'Student']
        );

        $this->user = User::factory()->create([
            'role_id' => $studentRole->id,
            'status' => 'active',
        ]);

        // Create API token
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test: GET /api/programs - List all programs
     */
    public function test_can_get_all_programs(): void
    {
        // Arrange: Create some programs
        Program::factory()->count(3)->create();

        // Act: Send GET request
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/programs');

        // Assert: Check response structure and status
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'description',
                        'category',
                        'duration_hours',
                        'image_url',
                        'status',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Programs retrieved successfully',
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test: GET /api/programs - Empty list when no programs exist
     */
    public function test_can_get_empty_program_list(): void
    {
        // Act: Send GET request when no programs exist
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/programs');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Programs retrieved successfully',
                'data' => []
            ]);
    }

    /**
     * Test: GET /api/programs/{id} - Get single program
     */
    public function test_can_get_single_program(): void
    {
        // Arrange: Create a program
        $program = Program::factory()->create([
            'name' => 'Laravel Advanced',
            'code' => 'LAR-ADV-001',
            'category' => 'Web Development',
        ]);

        // Act: Send GET request
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/programs/{$program->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Program retrieved successfully',
                'data' => [
                    'id' => $program->id,
                    'name' => 'Laravel Advanced',
                    'code' => 'LAR-ADV-001',
                    'category' => 'Web Development',
                ]
            ]);
    }

    /**
     * Test: GET /api/programs/{id} - 404 when program not found
     */
    public function test_returns_404_when_program_not_found(): void
    {
        // Act: Request non-existent program
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/programs/99999');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test: POST /api/programs - Create new program
     */
    public function test_can_create_program(): void
    {
        // Arrange: Prepare program data
        $programData = [
            'name' => 'Vue.js Fundamentals',
            'code' => 'VUE-FUND-001',
            'description' => 'Learn Vue.js from scratch',
            'category' => 'Frontend Development',
            'duration_hours' => 30,
            'image_url' => 'https://example.com/vue-course.jpg',
            'status' => 'active',
        ];

        // Act: Send POST request
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/programs', $programData);

        // Assert: Check response
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Program created successfully',
                'data' => [
                    'name' => 'Vue.js Fundamentals',
                    'code' => 'VUE-FUND-001',
                    'category' => 'Frontend Development',
                    'duration_hours' => 30,
                ]
            ]);

        // Assert: Check database
        $this->assertDatabaseHas('programs', [
            'name' => 'Vue.js Fundamentals',
            'code' => 'VUE-FUND-001',
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test: POST /api/programs - Validation error when required fields missing
     */
    public function test_create_program_validation_fails_when_required_fields_missing(): void
    {
        // Arrange: Empty data
        $programData = [];

        // Act: Send POST request
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/programs', $programData);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'category', 'duration_hours', 'status']);
    }

    /**
     * Test: POST /api/programs - Validation error for duplicate code
     */
    public function test_create_program_fails_with_duplicate_code(): void
    {
        // Arrange: Create existing program
        Program::factory()->create(['code' => 'DUP-CODE-001']);

        // Act: Try to create program with same code
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/programs', [
            'name' => 'Test Program',
            'code' => 'DUP-CODE-001', // Duplicate
            'category' => 'Testing',
            'duration_hours' => 10,
            'status' => 'active',
        ]);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /**
     * Test: POST /api/programs - Validation error for invalid duration
     */
    public function test_create_program_fails_with_invalid_duration(): void
    {
        // Act: Send POST with invalid duration
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/programs', [
            'name' => 'Test Program',
            'code' => 'TEST-001',
            'category' => 'Testing',
            'duration_hours' => 0, // Invalid: must be at least 1
            'status' => 'active',
        ]);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['duration_hours']);
    }

    /**
     * Test: POST /api/programs - Validation error for invalid status
     */
    public function test_create_program_fails_with_invalid_status(): void
    {
        // Act: Send POST with invalid status
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/programs', [
            'name' => 'Test Program',
            'code' => 'TEST-001',
            'category' => 'Testing',
            'duration_hours' => 10,
            'status' => 'invalid-status', // Invalid
        ]);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test: PUT /api/programs/{id} - Update program
     */
    public function test_can_update_program(): void
    {
        // Arrange: Create a program
        $program = Program::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description',
            'duration_hours' => 20,
        ]);

        // Act: Send PUT request
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/programs/{$program->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'duration_hours' => 40,
        ]);

        // Assert: Check response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Program updated successfully',
                'data' => [
                    'id' => $program->id,
                    'name' => 'Updated Name',
                    'description' => 'Updated Description',
                    'duration_hours' => 40,
                ]
            ]);

        // Assert: Check database
        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'duration_hours' => 40,
        ]);
    }

    /**
     * Test: PUT /api/programs/{id} - Partial update (only some fields)
     */
    public function test_can_partially_update_program(): void
    {
        // Arrange
        $program = Program::factory()->create([
            'name' => 'Original Name',
            'code' => 'ORIG-001',
            'duration_hours' => 20,
        ]);

        // Act: Update only name
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/programs/{$program->id}", [
            'name' => 'New Name Only',
        ]);

        // Assert: Name changed, code unchanged
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'New Name Only',
                    'code' => 'ORIG-001', // Unchanged
                ]
            ]);
    }

    /**
     * Test: PUT /api/programs/{id} - Cannot update with duplicate code
     */
    public function test_update_program_fails_with_duplicate_code(): void
    {
        // Arrange: Create two programs
        $program1 = Program::factory()->create(['code' => 'PROG-001']);
        $program2 = Program::factory()->create(['code' => 'PROG-002']);

        // Act: Try to update program2 with program1's code
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/programs/{$program2->id}", [
            'code' => 'PROG-001', // Duplicate
        ]);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /**
     * Test: DELETE /api/programs/{id} - Delete program
     */
    public function test_can_delete_program(): void
    {
        // Arrange: Create a program
        $program = Program::factory()->create();

        // Act: Send DELETE request
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/programs/{$program->id}");

        // Assert: Check response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Program deleted successfully',
                'data' => null,
            ]);

        // Assert: Check database (hard delete)
        $this->assertDatabaseMissing('programs', [
            'id' => $program->id,
        ]);
    }

    /**
     * Test: DELETE /api/programs/{id} - 404 when deleting non-existent program
     */
    public function test_delete_returns_404_when_program_not_found(): void
    {
        // Act: Delete non-existent program
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson('/api/programs/99999');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test: Unauthenticated requests are rejected
     */
    public function test_unauthenticated_requests_are_rejected(): void
    {
        // Act: Send request without token
        $response = $this->getJson('/api/programs');

        // Assert: 401 Unauthorized
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    /**
     * Test: Response format is consistent across all endpoints
     */
    public function test_all_success_responses_have_consistent_format(): void
    {
        // Create a program
        $program = Program::factory()->create();

        // Test GET all
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/programs');

        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);

        // Test GET single
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/programs/{$program->id}");

        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
    }
}
