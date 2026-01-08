<?php

namespace Tests\Feature\Api;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Role;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\CertificateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CertificateTemplateValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin']);
        $trainerRole = Role::firstOrCreate(['name' => 'trainer'], ['label' => 'Trainer']);

        // Create users
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $this->trainer = User::factory()->create([
            'role_id' => $trainerRole->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test: Image is auto-resized to 1920x1080
     */
    public function test_uploaded_image_is_resized_to_1920x1080(): void
    {
        // Arrange: Create 800x600 image
        $image = UploadedFile::fake()->image('test.png', 800, 600);

        // Act: Upload as template background
        $response = $this->actingAs($this->admin)->postJson('/api/admin/certificate-templates', [
            'name' => 'Test Template',
            'scope' => 'global',
            'background_image' => $image,
            'font_size' => 24,
            'text_color' => '#000000',
        ]);

        // Assert: Response success
        $response->assertStatus(201);

        // Assert: Stored image is 1920x1080
        $template = CertificateTemplate::latest()->first();
        $this->assertNotNull($template->background_image);

        $dimensions = getimagesizefromstring($template->background_image);
        $this->assertEquals(1920, $dimensions[0], 'Width should be 1920');
        $this->assertEquals(1080, $dimensions[1], 'Height should be 1080');
        $this->assertEquals('image/png', $template->background_mime_type, 'Should be converted to PNG');
    }

    /**
     * Test: Images smaller than 800x600 are rejected
     */
    public function test_image_smaller_than_800x600_is_rejected(): void
    {
        // Arrange: Create tiny image
        $image = UploadedFile::fake()->image('tiny.png', 400, 300);

        // Act: Upload
        $response = $this->actingAs($this->admin)->postJson('/api/admin/certificate-templates', [
            'name' => 'Test Template',
            'scope' => 'global',
            'background_image' => $image,
            'font_size' => 24,
            'text_color' => '#000000',
        ]);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['background_image']);

        $errors = $response->json('errors.background_image');
        $this->assertStringContainsString('800x600', $errors[0]);
    }

    /**
     * Test: Layout coordinates exceeding image bounds are rejected
     */
    public function test_layout_coordinates_exceeding_image_bounds_are_rejected(): void
    {
        // Arrange: Create image
        $image = UploadedFile::fake()->image('test.png', 800, 600);

        // Act: Upload with invalid layout (x=2000 exceeds 1920 after resize)
        $response = $this->actingAs($this->admin)->postJson('/api/admin/certificate-templates', [
            'name' => 'Test Template',
            'scope' => 'global',
            'background_image' => $image,
            'layout_config' => [
                'name' => ['x' => 2000, 'y' => 500, 'size' => 24],
            ],
            'font_size' => 24,
            'text_color' => '#000000',
        ]);

        // Assert: Validation error
        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertArrayHasKey('layout_config.name.x', $errors);
    }

    /**
     * Test: QR code extending beyond bounds is rejected
     */
    public function test_qr_code_extending_beyond_bounds_is_rejected(): void
    {
        // Arrange: Create image
        $image = UploadedFile::fake()->image('test.png', 800, 600);

        // Act: QR at x=1800, width=200 -> extends to 2000 (exceeds 1920)
        $response = $this->actingAs($this->admin)->postJson('/api/admin/certificate-templates', [
            'name' => 'Test Template',
            'scope' => 'global',
            'background_image' => $image,
            'layout_config' => [
                'qr' => ['x' => 1800, 'y' => 100, 'width' => 200, 'height' => 200],
            ],
            'font_size' => 24,
            'text_color' => '#000000',
        ]);

        // Assert: Validation error
        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertTrue(
            isset($errors['layout_config.qr.width']) || isset($errors['layout_config.qr.x']),
            'Should have validation error for QR code bounds'
        );
    }

    /**
     * Test: Percentage-based coordinates render correctly
     */
    public function test_percentage_based_coordinates_render_correctly(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for rendering tests.');
        }

        // Arrange: Create template with percentage layout
        $image = UploadedFile::fake()->image('test.png', 800, 600);
        $response = $this->actingAs($this->admin)->postJson('/api/admin/certificate-templates', [
            'name' => 'Percentage Template',
            'scope' => 'global',
            'background_image' => $image,
            'layout_config' => [
                'name' => ['x' => '50%', 'y' => '20%', 'size' => 32],
                'qr' => ['x' => '80%', 'y' => '80%', 'size' => 160],
            ],
            'font_size' => 24,
            'text_color' => '#000000',
        ]);

        $response->assertStatus(201);

        $template = CertificateTemplate::latest()->first();

        // Create certificate for rendering test
        $program = Program::factory()->create([
            'created_by' => $this->admin->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $session = TrainingSession::factory()->create([
            'program_id' => $program->id,
            'trainer_id' => $this->admin->id,
            'status' => 'completed',
            'approval_status' => 'approved',
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->admin->id,
            'session_id' => $session->id,
            'status' => 'completed',
        ]);

        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $this->admin->id,
            'program_id' => $program->id,
            'session_id' => $session->id,
            'certificate_code' => 'TEST-PERCENT-123',
            'issued_at' => now(),
            'issued_by' => $this->admin->id,
            'status' => 'valid',
            'template_id' => $template->id,
        ]);

        // Act: Render certificate
        $renderer = app(CertificateRenderer::class);
        $result = $renderer->render($certificate, $template);

        // Assert: Rendering succeeds (no errors about coordinates)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('binary', $result);
        $this->assertArrayHasKey('mime_type', $result);
        $this->assertNotEmpty($result['binary']);
        $this->assertEquals('image/png', $result['mime_type']);
    }

    /**
     * Test: Existing pixel-based coordinates still work (backwards compatibility)
     */
    public function test_existing_pixel_based_coordinates_still_work(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for rendering tests.');
        }

        // Arrange: Create template with integer pixel coordinates (legacy format)
        $image = UploadedFile::fake()->image('test.png', 800, 600);
        $response = $this->actingAs($this->admin)->postJson('/api/admin/certificate-templates', [
            'name' => 'Legacy Template',
            'scope' => 'global',
            'background_image' => $image,
            'layout_config' => [
                'name' => ['x' => 100, 'y' => 200, 'size' => 32],
                'qr' => ['x' => 1500, 'y' => 900, 'size' => 160],
            ],
            'font_size' => 24,
            'text_color' => '#000000',
        ]);

        $response->assertStatus(201);

        $template = CertificateTemplate::latest()->first();

        $program = Program::factory()->create([
            'created_by' => $this->admin->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $session = TrainingSession::factory()->create([
            'program_id' => $program->id,
            'trainer_id' => $this->admin->id,
            'status' => 'completed',
            'approval_status' => 'approved',
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->admin->id,
            'session_id' => $session->id,
            'status' => 'completed',
        ]);

        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $this->admin->id,
            'program_id' => $program->id,
            'session_id' => $session->id,
            'certificate_code' => 'LEGACY-123',
            'issued_at' => now(),
            'issued_by' => $this->admin->id,
            'status' => 'valid',
            'template_id' => $template->id,
        ]);

        // Act: Render certificate
        $renderer = app(CertificateRenderer::class);
        $result = $renderer->render($certificate, $template);

        // Assert: Rendering succeeds
        $this->assertIsArray($result);
        $this->assertNotEmpty($result['binary']);
    }

    /**
     * Test: Mixed pixel and percentage coordinates work
     */
    public function test_mixed_pixel_and_percentage_coordinates(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for rendering tests.');
        }

        // Arrange: Create template with mixed coordinates
        $image = UploadedFile::fake()->image('test.png', 800, 600);
        $response = $this->actingAs($this->admin)->postJson('/api/admin/certificate-templates', [
            'name' => 'Mixed Template',
            'scope' => 'global',
            'background_image' => $image,
            'layout_config' => [
                'name' => ['x' => '50%', 'y' => 200, 'size' => 32], // Mix: x=%, y=pixel
                'course' => ['x' => 100, 'y' => '30%', 'size' => 24], // Mix: x=pixel, y=%
            ],
            'font_size' => 24,
            'text_color' => '#000000',
        ]);

        $response->assertStatus(201);

        $template = CertificateTemplate::latest()->first();

        $program = Program::factory()->create([
            'created_by' => $this->admin->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $session = TrainingSession::factory()->create([
            'program_id' => $program->id,
            'trainer_id' => $this->admin->id,
            'status' => 'completed',
            'approval_status' => 'approved',
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->admin->id,
            'session_id' => $session->id,
            'status' => 'completed',
        ]);

        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $this->admin->id,
            'program_id' => $program->id,
            'session_id' => $session->id,
            'certificate_code' => 'MIX-123',
            'issued_at' => now(),
            'issued_by' => $this->admin->id,
            'status' => 'valid',
            'template_id' => $template->id,
        ]);

        // Act: Render
        $renderer = app(CertificateRenderer::class);
        $result = $renderer->render($certificate, $template);

        // Assert: Succeeds
        $this->assertNotEmpty($result['binary']);
    }

    /**
     * Test: Percentage coordinates work without background image (canvas-based)
     */
    public function test_percentage_coordinates_work_without_background_image(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for rendering tests.');
        }

        // Arrange: Canvas-based template (no background_image)
        $template = CertificateTemplate::create([
            'name' => 'Canvas Template',
            'scope' => 'global',
            'layout_config' => [
                'canvas' => ['width' => 1600, 'height' => 1200],
                'name' => ['x' => '50%', 'y' => '40%', 'size' => 32],
            ],
            'font_size' => 24,
            'text_color' => '#000000',
            'is_active' => true,
        ]);

        $program = Program::factory()->create([
            'created_by' => $this->admin->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $session = TrainingSession::factory()->create([
            'program_id' => $program->id,
            'trainer_id' => $this->admin->id,
            'status' => 'completed',
            'approval_status' => 'approved',
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->admin->id,
            'session_id' => $session->id,
            'status' => 'completed',
        ]);

        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $this->admin->id,
            'program_id' => $program->id,
            'session_id' => $session->id,
            'certificate_code' => 'CANVAS-123',
            'issued_at' => now(),
            'issued_by' => $this->admin->id,
            'status' => 'valid',
            'template_id' => $template->id,
        ]);

        // Act: Render
        $renderer = app(CertificateRenderer::class);
        $result = $renderer->render($certificate, $template);

        // Assert: Percentage resolves against canvas dimensions
        $this->assertNotEmpty($result['binary']);
    }

    /**
     * Test: Updating template validates new layout
     */
    public function test_updating_template_validates_new_layout(): void
    {
        // Arrange: Create template
        $image = UploadedFile::fake()->image('test.png', 800, 600);
        $response = $this->actingAs($this->admin)->postJson('/api/admin/certificate-templates', [
            'name' => 'Original Template',
            'scope' => 'global',
            'background_image' => $image,
            'font_size' => 24,
            'text_color' => '#000000',
        ]);

        $response->assertStatus(201);

        $template = CertificateTemplate::latest()->first();

        // Act: Update with invalid layout
        $updateResponse = $this->actingAs($this->admin)->putJson("/api/admin/certificate-templates/{$template->id}", [
            'layout_config' => [
                'name' => ['x' => 5000, 'y' => 100, 'size' => 24], // x=5000 exceeds 1920
            ],
        ]);

        // Assert: Validation fails
        $updateResponse->assertStatus(422);
        $errors = $updateResponse->json('errors');
        $this->assertArrayHasKey('layout_config.name.x', $errors);
    }

    /**
     * Test: Layout validation passes with valid coordinates
     */
    public function test_layout_validation_passes_with_valid_coordinates(): void
    {
        // Arrange: Create image
        $image = UploadedFile::fake()->image('test.png', 800, 600);

        // Act: Upload with valid layout
        $response = $this->actingAs($this->admin)->postJson('/api/admin/certificate-templates', [
            'name' => 'Valid Layout Template',
            'scope' => 'global',
            'background_image' => $image,
            'layout_config' => [
                'name' => ['x' => 960, 'y' => 540, 'size' => 32], // Center of 1920x1080
                'course' => ['x' => '25%', 'y' => '75%', 'size' => 24],
                'qr' => ['x' => 100, 'y' => 100, 'width' => 160, 'height' => 160],
            ],
            'font_size' => 24,
            'text_color' => '#000000',
        ]);

        // Assert: Success
        $response->assertStatus(201);
        $this->assertDatabaseHas('certificate_templates', [
            'name' => 'Valid Layout Template',
        ]);
    }
}
