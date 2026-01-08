<?php

namespace Tests\Feature\Api;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Role;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\CertificateFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CertificateFileSizeLimitTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $trainer;
    protected Program $program;
    protected TrainingSession $session;

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

        // Create program and session
        $this->program = Program::factory()->create([
            'created_by' => $this->trainer->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $this->session = TrainingSession::factory()->create([
            'program_id' => $this->program->id,
            'trainer_id' => $this->trainer->id,
            'status' => 'completed',
            'approval_status' => 'approved',
        ]);
    }

    /**
     * Test: KAN-393 - Background image size limit validation
     */
    public function test_background_image_respects_size_limit(): void
    {
        // Arrange: Set a small max size for testing (100 KB)
        config(['certificates.max_file_sizes.background_image' => 100]);

        // Create a file larger than the limit (1x1 white PNG, then pad it)
        $image = UploadedFile::fake()->image('background.png', 1, 1)->size(150); // 150 KB

        // Act: Try to create template with oversized background
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/certificate-templates', [
                'name' => 'Test Template',
                'scope' => 'global',
                'background_image' => $image,
                'font_size' => 24,
                'text_color' => '#000000',
            ]);

        // Assert: Should fail validation
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['background_image']);
    }

    /**
     * Test: KAN-393 - Background image within limit is accepted
     */
    public function test_background_image_within_limit_is_accepted(): void
    {
        // Arrange: Set a reasonable max size
        config(['certificates.max_file_sizes.background_image' => 5120]); // 5MB

        // Create an image that meets minimum dimension requirements (800x600)
        $image = UploadedFile::fake()->image('background.png', 1000, 800)->size(50); // 50 KB

        // Act: Create template with valid background
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/certificate-templates', [
                'name' => 'Test Template',
                'scope' => 'global',
                'background_image' => $image,
                'font_size' => 24,
                'text_color' => '#000000',
            ]);

        // Assert: Should succeed
        $response->assertStatus(201);

        $this->assertDatabaseHas('certificate_templates', [
            'name' => 'Test Template',
            'scope' => 'global',
        ]);
    }

    /**
     * Test: KAN-393 - Certificate file size is validated during generation
     */
    public function test_certificate_file_size_is_validated(): void
    {
        // This test would require mocking the renderer to produce a large file
        // For now, we'll test that the validation method exists and works
        $this->markTestIncomplete('Requires mocking CertificateRenderer to produce large files');
    }

    /**
     * Test: KAN-393 - Large file warning is logged
     */
    public function test_large_certificate_file_logs_warning(): void
    {
        // This test would require log assertion and mocking
        $this->markTestIncomplete('Requires log assertion for large file warnings');
    }

    /**
     * Test: KAN-395 - Storage policy configuration is accessible
     */
    public function test_storage_policy_configuration_is_accessible(): void
    {
        // Act: Get configuration values
        $storagePolicy = config('certificates.storage_policy');
        $defaultGeneration = config('certificates.default_generation_mode');
        $maxFileSize = config('certificates.max_file_sizes.certificate_file');
        $maxBackgroundSize = config('certificates.max_file_sizes.background_image');

        // Assert: Configuration values are set
        $this->assertNotNull($storagePolicy);
        $this->assertNotNull($defaultGeneration);
        $this->assertIsNumeric($maxFileSize);
        $this->assertIsNumeric($maxBackgroundSize);
        $this->assertGreaterThan(0, $maxFileSize);
        $this->assertGreaterThan(0, $maxBackgroundSize);
    }

    /**
     * Test: KAN-394 - Default storage policy is on_demand
     */
    public function test_default_storage_policy_is_on_demand(): void
    {
        // Assert: Default policy should be 'on_demand' to save space
        $this->assertEquals('on_demand', config('certificates.storage_policy'));
    }

    /**
     * Test: KAN-394 - Default generation mode is lazy
     */
    public function test_default_generation_mode_is_lazy(): void
    {
        // Assert: Default generation should be 'lazy' to save space
        $this->assertEquals('lazy', config('certificates.default_generation_mode'));
    }

    /**
     * Test: Cleanup configuration is accessible
     */
    public function test_cleanup_configuration_is_accessible(): void
    {
        // Act: Get cleanup configuration
        $cleanupEnabled = config('certificates.cleanup.enabled');
        $retentionDays = config('certificates.cleanup.retention_days');
        $cleanupStatuses = config('certificates.cleanup.cleanup_statuses');

        // Assert: Configuration values are set
        $this->assertIsBool($cleanupEnabled);
        $this->assertIsNumeric($retentionDays);
        $this->assertIsArray($cleanupStatuses);
        $this->assertGreaterThan(0, $retentionDays);
    }

    /**
     * Test: Monitoring configuration is accessible
     */
    public function test_monitoring_configuration_is_accessible(): void
    {
        // Act: Get monitoring configuration
        $warningThreshold = config('certificates.monitoring.storage_warning_threshold');
        $logLargeFiles = config('certificates.monitoring.log_large_files');

        // Assert: Configuration values are set
        $this->assertIsNumeric($warningThreshold);
        $this->assertIsBool($logLargeFiles);
        $this->assertGreaterThan(0, $warningThreshold);
    }
}
