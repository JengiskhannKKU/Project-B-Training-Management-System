<?php

namespace App\Listeners;

use App\Events\SessionCompleted;
use App\Models\Certificate;
use App\Services\CertificateGenerationService;
use Illuminate\Support\Facades\Log;

class GenerateCertificatesAutomatically
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private CertificateGenerationService $certificateService
    ) {
    }

    /**
     * Handle the event - automatically generate certificates when session is marked complete.
     */
    public function handle(SessionCompleted $event): void
    {
        $session = $event->session;
        $completedBy = $event->completedBy;

        // Check if certificates already exist for this session
        $existingCount = Certificate::where('session_id', $session->id)
            ->where('status', 'valid')
            ->count();

        if ($existingCount > 0) {
            Log::info('Certificates already exist for session', [
                'session_id' => $session->id,
                'existing_count' => $existingCount,
            ]);
            return;
        }

        // Log the automatic generation
        Log::info('Auto-generating certificates for completed session', [
            'session_id' => $session->id,
            'session_title' => $session->title,
            'completed_by_user_id' => $completedBy->id,
            'completed_by_user_name' => $completedBy->name,
        ]);

        try {
            // Generate certificates for all eligible enrollments (attendance >= 80%)
            $batch = $this->certificateService->generateCertificatesForSession(
                session: $session,
                issuerId: $completedBy->id,
                eagerGenerate: false, // Generate PDFs on-demand (faster)
                language: 'en' // Default to English as per recent changes
            );

            Log::info('Certificates auto-generated successfully', [
                'session_id' => $session->id,
                'batch_id' => $batch->id,
                'total_count' => $batch->total_count,
                'generated_count' => $batch->generated_count,
                'failed_count' => $batch->failed_count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to auto-generate certificates', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't throw - we don't want to fail the session completion if certificate generation fails
            // Admin can manually regenerate later
        }
    }
}
