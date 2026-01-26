# Certificate Storage Policy & Size Management

**Implementation Reference:** KAN-393, KAN-394, KAN-395

This document outlines the certificate storage policies, file size limits, and recommendations for managing certificate storage in the Training Management System.

## Table of Contents

- [File Size Limits (KAN-393)](#file-size-limits-kan-393)
- [Storage Policies (KAN-394)](#storage-policies-kan-394)
- [Storage Management (KAN-395)](#storage-management-kan-395)
- [Monitoring & Maintenance](#monitoring--maintenance)
- [Recommendations by Deployment Size](#recommendations-by-deployment-size)

---

## File Size Limits (KAN-393)

### Configured Limits

The system enforces the following file size limits (configurable in `config/certificates.php`):

| File Type | Default Limit | Configuration Key | Environment Variable |
|-----------|---------------|-------------------|----------------------|
| Template Background Images | 5 MB (5120 KB) | `max_file_sizes.background_image` | `CERT_MAX_BACKGROUND_SIZE` |
| Certificate PDF Files | 2 MB (2048 KB) | `max_file_sizes.certificate_file` | `CERT_MAX_FILE_SIZE` |

### Validation Behavior

#### Template Background Images
- **Upload Validation:** Enforced during template creation/update via Laravel validation rules
- **Error Response:** HTTP 422 with validation error if file exceeds limit
- **Location:** `app/Http/Controllers/Api/CertificateTemplateController.php:164`

#### Certificate PDF Files
- **Generation Validation:** Enforced after PDF rendering, before database storage
- **Warning Threshold:** Logs warning if file exceeds 50% of maximum size
- **Error Behavior:** Throws `RuntimeException` if file exceeds maximum size
- **Location:** `app/Services/CertificateFileService.php:175`

### Adjusting Limits

To change file size limits, update your `.env` file:

```env
# Set background image limit to 10MB
CERT_MAX_BACKGROUND_SIZE=10240

# Set certificate PDF limit to 3MB
CERT_MAX_FILE_SIZE=3072
```

Or modify `config/certificates.php` directly:

```php
'max_file_sizes' => [
    'background_image' => 10240,  // 10 MB
    'certificate_file' => 3072,   // 3 MB
],
```

---

## Storage Policies (KAN-394)

### Available Policies

The system supports three storage policies:

#### 1. `always` - Always Store PDFs
- **Behavior:** Store all generated PDFs in the database
- **Use Case:** Small deployments with high performance requirements
- **Pros:**
  - Fastest certificate access
  - No re-generation needed
  - Best user experience
- **Cons:**
  - Highest storage usage
  - Database can grow large quickly
- **Recommended For:** <10,000 certificates

#### 2. `on_demand` - Generate and Store on Request (Default)
- **Behavior:** Generate PDFs only when requested (download/view), store after first generation
- **Use Case:** Medium to large deployments
- **Pros:**
  - Balanced storage usage
  - Certificates cached after first access
  - Reasonable performance
- **Cons:**
  - Slower first access
  - Storage still grows over time
- **Recommended For:** 10,000-100,000 certificates

#### 3. `temporary` - Generate Without Storing
- **Behavior:** Generate PDFs on every request, never store in database
- **Use Case:** Very large deployments or storage-constrained environments
- **Pros:**
  - Minimal storage usage
  - No database bloat
- **Cons:**
  - Slowest performance (regenerate every time)
  - Higher CPU usage
  - Not implemented yet (future enhancement)
- **Recommended For:** >100,000 certificates

### Setting Storage Policy

Update your `.env` file:

```env
# Options: always, on_demand, temporary
CERT_STORAGE_POLICY=on_demand
```

Or modify `config/certificates.php`:

```php
'storage_policy' => 'on_demand',
```

### Generation Modes

The system supports two generation modes:

#### Lazy Generation (Default)
- PDFs generated only when downloaded/viewed
- Minimizes upfront processing
- Best for storage efficiency

#### Eager Generation
- PDFs generated immediately when certificate is created
- Can be enabled via API parameter: `eager_generation=true`
- Best for certificates that will be accessed immediately

**Setting Default Mode:**

```env
# Options: lazy, eager
CERT_DEFAULT_GENERATION=lazy
```

---

## Storage Management (KAN-395)

### Cleanup Policy

The system can automatically clean up old certificate files to manage database size.

#### Configuration

```php
'cleanup' => [
    'enabled' => false,                    // Enable/disable cleanup
    'retention_days' => 365,               // Keep files for this many days
    'cleanup_statuses' => ['revoked'],     // Only clean these statuses
],
```

#### Manual Cleanup

Run the cleanup command manually:

```bash
# Dry run to see what would be cleaned
php artisan certificates:cleanup --dry-run

# Clean up revoked certificates older than retention period
php artisan certificates:cleanup

# Force cleanup even if disabled in config
php artisan certificates:cleanup --force

# Clean specific statuses
php artisan certificates:cleanup --status=revoked --status=expired
```

#### Automated Cleanup

Schedule in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Run cleanup weekly
    $schedule->command('certificates:cleanup')
        ->weekly()
        ->sundays()
        ->at('02:00');
}
```

---

## Monitoring & Maintenance

### Storage Report

Generate a comprehensive storage report:

```bash
# Human-readable report
php artisan certificates:storage-report

# JSON output for integration
php artisan certificates:storage-report --json
```

The report includes:
- Total certificates and file counts
- Storage usage by type (PDFs, backgrounds)
- Average file sizes
- Status breakdown
- Largest certificate files
- Configuration summary
- Automated recommendations

### Monitoring Configuration

```php
'monitoring' => [
    'storage_warning_threshold' => 1000,  // Warn at 1GB
    'log_large_files' => true,            // Log large file warnings
],
```

### Log Messages

The system logs important storage events:

```php
// Large certificate file generated
Log::warning('Large certificate file generated', [
    'certificate_id' => 123,
    'file_size_kb' => 1500,
    'max_size_kb' => 2048,
]);
```

---

## Recommendations by Deployment Size

### Small Deployments (<10,000 certificates)

**Configuration:**
```env
CERT_STORAGE_POLICY=always
CERT_DEFAULT_GENERATION=eager
CERT_CLEANUP_ENABLED=false
```

**Strategy:**
- Store all PDFs for best performance
- Use eager generation for frequently accessed certificates
- Cleanup not necessary at this scale

**Expected Storage:** <1 GB

---

### Medium Deployments (10,000-100,000 certificates)

**Configuration:**
```env
CERT_STORAGE_POLICY=on_demand
CERT_DEFAULT_GENERATION=lazy
CERT_CLEANUP_ENABLED=true
CERT_CLEANUP_RETENTION_DAYS=365
```

**Strategy:**
- Generate and store on demand
- Use lazy generation to minimize upfront processing
- Enable periodic cleanup of revoked certificates
- Monitor storage usage monthly

**Expected Storage:** 1-10 GB

---

### Large Deployments (>100,000 certificates)

**Configuration:**
```env
CERT_STORAGE_POLICY=on_demand
CERT_DEFAULT_GENERATION=lazy
CERT_CLEANUP_ENABLED=true
CERT_CLEANUP_RETENTION_DAYS=180
CERT_STORAGE_WARNING_MB=5000
```

**Strategy:**
- Use on_demand storage exclusively
- Implement aggressive cleanup policies
- Monitor storage usage weekly
- Consider external storage solutions (S3, etc.) if storage exceeds 10GB
- Schedule regular cleanup jobs

**Expected Storage:** >10 GB (consider external storage)

---

## Performance vs Storage Trade-offs

| Strategy | Performance | Storage Usage | CPU Usage | Recommended Scale |
|----------|-------------|---------------|-----------|-------------------|
| Always + Eager | ⭐⭐⭐⭐⭐ | ⭐ | ⭐⭐ | Small |
| On-Demand + Lazy | ⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ | Medium |
| Temporary + Lazy | ⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | Large |

---

## Future Enhancements

### External Storage Integration
- Store PDFs in S3/cloud storage instead of database
- Keep only metadata in database
- Reduces database size significantly

### Compression
- Compress PDFs before storage
- Can reduce storage by 30-50%
- Trade-off: slightly slower access time

### Caching Layer
- Implement Redis/Memcached for frequently accessed certificates
- Reduces database load
- Improves response times

---

## Troubleshooting

### Database Growing Too Large

1. Run storage report to identify issues:
   ```bash
   php artisan certificates:storage-report
   ```

2. Check for certificates with large files:
   ```bash
   php artisan certificates:storage-report --json | jq '.largest_certificates'
   ```

3. Clean up old/revoked certificates:
   ```bash
   php artisan certificates:cleanup --dry-run
   php artisan certificates:cleanup
   ```

4. Adjust storage policy if needed:
   ```env
   CERT_STORAGE_POLICY=on_demand
   ```

### Files Exceeding Size Limits

1. Check template backgrounds - may need optimization
2. Reduce template background image resolution/quality
3. Consider removing backgrounds from templates
4. Adjust size limits if appropriate

### Slow Certificate Generation

1. Enable eager generation for frequently accessed certificates
2. Implement caching layer
3. Optimize template rendering
4. Consider upgrading server resources

---

## Related Files

- Configuration: `config/certificates.php`
- File Service: `app/Services/CertificateFileService.php`
- Template Controller: `app/Http/Controllers/Api/CertificateTemplateController.php`
- Cleanup Command: `app/Console/Commands/CleanupCertificateFiles.php`
- Report Command: `app/Console/Commands/CertificateStorageReport.php`
- Tests: `tests/Feature/Api/CertificateFileSizeLimitTest.php`
