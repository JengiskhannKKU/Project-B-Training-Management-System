<?php

namespace Tests\Feature\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\CertificateFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CertificateFileServiceTest extends TestCase
{
    use RefreshDatabase;

    private function skipIfGdMissing(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for certificate rendering.');
        }
    }

    public function test_generate_and_store_file_uses_session_template_priority(): void
    {
        $this->skipIfGdMissing();

        $student = User::factory()->create();
        $program = Program::factory()->create();
        $session = TrainingSession::factory()->create([
            'program_id' => $program->id,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'session_id' => $session->id,
            'status' => 'completed',
            'enrolled_at' => now(),
        ]);

        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $student->id,
            'program_id' => $program->id,
            'session_id' => $session->id,
            'issued_by' => $student->id,
            'issued_at' => now(),
            'certificate_code' => 'CERT-' . Str::upper(Str::random(10)),
            'status' => 'valid',
        ]);

        $programTemplate = CertificateTemplate::create([
            'name' => 'Program Template',
            'scope' => 'program',
            'program_id' => $program->id,
            'layout_config' => ['name' => ['x' => 50, 'y' => 80]],
            'is_active' => true,
        ]);

        $sessionTemplate = CertificateTemplate::create([
            'name' => 'Session Template',
            'scope' => 'session',
            'session_id' => $session->id,
            'layout_config' => ['name' => ['x' => 40, 'y' => 70]],
            'is_active' => true,
        ]);

        $service = new CertificateFileService();
        $updated = $service->generateAndStoreFile($certificate);

        $this->assertNotNull($updated->file_data);
        $this->assertSame('image/png', $updated->file_mime_type);
        $this->assertSame($sessionTemplate->id, $updated->template_id);
        $this->assertNotNull($updated->generated_at);
        $this->assertGreaterThan(0, $updated->file_size);

        $this->assertNotEquals($programTemplate->id, $updated->template_id);
    }

    public function test_generate_and_store_file_falls_back_to_global_template(): void
    {
        $this->skipIfGdMissing();

        $student = User::factory()->create();
        $program = Program::factory()->create();
        $session = TrainingSession::factory()->create([
            'program_id' => $program->id,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'session_id' => $session->id,
            'status' => 'completed',
            'enrolled_at' => now(),
        ]);

        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $student->id,
            'program_id' => $program->id,
            'session_id' => $session->id,
            'issued_by' => $student->id,
            'issued_at' => now(),
            'certificate_code' => 'CERT-' . Str::upper(Str::random(10)),
            'status' => 'valid',
        ]);

        $globalTemplate = CertificateTemplate::create([
            'name' => 'Global Template',
            'scope' => 'global',
            'layout_config' => ['name' => ['x' => 30, 'y' => 60]],
            'is_active' => true,
        ]);

        $service = new CertificateFileService();
        $updated = $service->generateAndStoreFile($certificate);

        $this->assertSame($globalTemplate->id, $updated->template_id);
        $this->assertNotNull($updated->file_data);
        $this->assertSame('image/png', $updated->file_mime_type);
    }

    public function test_generate_and_store_file_is_idempotent_when_file_exists(): void
    {
        $this->skipIfGdMissing();

        $student = User::factory()->create();
        $program = Program::factory()->create();
        $session = TrainingSession::factory()->create([
            'program_id' => $program->id,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'session_id' => $session->id,
            'status' => 'completed',
            'enrolled_at' => now(),
        ]);

        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $student->id,
            'program_id' => $program->id,
            'session_id' => $session->id,
            'issued_by' => $student->id,
            'issued_at' => now(),
            'certificate_code' => 'CERT-' . Str::upper(Str::random(10)),
            'status' => 'valid',
            'file_data' => 'existing',
            'file_mime_type' => 'image/png',
            'file_size' => 8,
            'generated_at' => now()->subDay(),
        ]);

        CertificateTemplate::create([
            'name' => 'Session Template',
            'scope' => 'session',
            'session_id' => $session->id,
            'layout_config' => ['name' => ['x' => 40, 'y' => 70]],
            'is_active' => true,
        ]);

        $service = new CertificateFileService();
        $updated = $service->generateAndStoreFile($certificate);

        $this->assertSame('existing', $updated->file_data);
        $this->assertSame('image/png', $updated->file_mime_type);
        $this->assertSame(8, $updated->file_size);
    }
}
