<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mailtrap:test {--to=} {--subject=}', function () {
    $apiKey = env('MAILTRAP_API_KEY');

    if (! $apiKey) {
        $this->error('MAILTRAP_API_KEY is not set.');
        return;
    }

    $to = $this->option('to') ?: env('MAILTRAP_TO_EMAIL') ?: 'you@example.com';
    $subject = $this->option('subject') ?: 'Mailtrap Laravel test';

    $isSandbox = filter_var(env('MAILTRAP_USE_SANDBOX', false), FILTER_VALIDATE_BOOL);
    $inboxIdEnv = env('MAILTRAP_INBOX_ID');
    $inboxId = is_numeric($inboxIdEnv) ? (int) $inboxIdEnv : null;

    $client = MailtrapClient::initSendingEmails(
        apiKey: $apiKey,
        isSandbox: $isSandbox,
        inboxId: $inboxId
    );

    $email = (new MailtrapEmail())
        ->from(new Address(env('MAIL_FROM_ADDRESS', 'no-reply@example.com'), env('MAIL_FROM_NAME', 'Training Management')))
        ->to(new Address($to))
        ->subject($subject)
        ->text('Mailtrap API test from Laravel.');

    $response = $client->send($email);

    $this->info('Sent. Response:');
    $this->line(print_r(ResponseHelper::toArray($response), true));
})->purpose('Send a test email via Mailtrap API');

// Schedule: Close expired sessions daily at midnight
Schedule::command('sessions:close-expired')->daily();

// Schedule: Evaluate enrollment completions daily at 1 AM
Schedule::command('enrollments:evaluate-completions')->dailyAt('01:00');

// Schedule: Auto-complete sessions 7 days after end_date, runs daily at 2 AM
Schedule::command('sessions:auto-complete --days=7')->dailyAt('02:00');
