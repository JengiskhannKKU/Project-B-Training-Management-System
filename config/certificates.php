<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Certificate Storage Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration options for certificate storage,
    | file size limits, and generation policies.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | File Size Limits (KAN-393)
    |--------------------------------------------------------------------------
    |
    | Define maximum file sizes for various certificate-related files.
    | Sizes are in kilobytes (KB).
    |
    */
    'max_file_sizes' => [
        // Maximum size for template background images (in KB)
        // Default: 5MB (5120 KB)
        'background_image' => env('CERT_MAX_BACKGROUND_SIZE', 5120),

        // Maximum size for generated certificate PDF files (in KB)
        // Default: 2MB (2048 KB)
        // Note: Generated certificates are typically 100-500KB
        'certificate_file' => env('CERT_MAX_FILE_SIZE', 2048),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Policy (KAN-394, KAN-395)
    |--------------------------------------------------------------------------
    |
    | Define how certificate files should be stored.
    |
    | Options:
    | - 'always' : Always store generated PDFs in database (eager + lazy on-demand)
    | - 'on_demand' : Only generate and store PDFs when requested (lazy only)
    | - 'temporary' : Generate PDFs on-demand and cache for limited time
    |
    */
    'storage_policy' => env('CERT_STORAGE_POLICY', 'on_demand'),

    /*
    |--------------------------------------------------------------------------
    | Default Generation Mode
    |--------------------------------------------------------------------------
    |
    | Set the default file generation mode for certificate creation.
    |
    | Options:
    | - 'lazy' : Generate PDF files only when needed (download/view)
    | - 'eager' : Generate PDF files immediately upon certificate creation
    |
    | Note: This is the default; API calls can override with 'eager_generation' parameter
    |
    */
    'default_generation_mode' => env('CERT_DEFAULT_GENERATION', 'lazy'),

    /*
    |--------------------------------------------------------------------------
    | File Cleanup Policy
    |--------------------------------------------------------------------------
    |
    | Define when to clean up stored certificate files to manage database size.
    |
    */
    'cleanup' => [
        // Enable automatic cleanup of old certificate files
        'enabled' => env('CERT_CLEANUP_ENABLED', false),

        // Keep certificate files for this many days before cleanup
        // Default: 365 days (1 year)
        'retention_days' => env('CERT_CLEANUP_RETENTION_DAYS', 365),

        // Only clean up certificates with these statuses
        // Options: 'revoked', 'expired', etc.
        'cleanup_statuses' => ['revoked'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Recommendations (KAN-394)
    |--------------------------------------------------------------------------
    |
    | Based on usage patterns, the system can recommend storage strategies:
    |
    | Small deployments (<10,000 certificates):
    |   - Use 'always' storage policy for best performance
    |   - Enable eager generation for frequently accessed certificates
    |
    | Medium deployments (10,000-100,000 certificates):
    |   - Use 'on_demand' storage policy to save space
    |   - Enable lazy generation (default)
    |   - Consider periodic cleanup of revoked certificates
    |
    | Large deployments (>100,000 certificates):
    |   - Use 'on_demand' or 'temporary' storage policy
    |   - Use lazy generation exclusively
    |   - Enable aggressive cleanup policies
    |   - Consider external storage solutions (S3, etc.)
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Enable monitoring to track storage usage and performance.
    |
    */
    'monitoring' => [
        // Log warnings when database storage exceeds threshold (in MB)
        'storage_warning_threshold' => env('CERT_STORAGE_WARNING_MB', 1000), // 1GB

        // Log individual file size warnings
        'log_large_files' => env('CERT_LOG_LARGE_FILES', true),
    ],
];
