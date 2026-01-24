<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Completion - {{ $trainee_name }}</title>
    <style>
        @page {
            size: 297mm 210mm;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            width: 297mm;
            height: 210mm;
            background: #fff;
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .certificate {
            display: inline-block;
            width: 275mm;
            border: 3px solid #1a365d;
            padding: 4mm;
            margin: 8mm auto;
        }

        .certificate-content {
            border: 1px solid #b8860b;
            text-align: center;
            padding: 6mm 12mm;
        }

        .header {
            margin-bottom: 2mm;
        }

        .logo {
            height: 12mm;
        }

        .org-name {
            font-size: 11pt;
            font-weight: bold;
            color: #1a365d;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 2mm 0 0 0;
        }

        .org-tagline {
            font-size: 6pt;
            color: #666;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin: 0;
        }

        .title {
            font-size: 30pt;
            font-weight: bold;
            color: #1a365d;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin: 7mm 0 0 0;
        }

        .subtitle {
            font-size: 11pt;
            color: #b8860b;
            font-style: italic;
            letter-spacing: 2px;
            margin: 0;
        }

        .certify-text {
            font-size: 10pt;
            color: #444;
            margin: 8mm 0 2mm 0;
        }

        .recipient-name {
            font-size: 22pt;
            font-weight: bold;
            color: #1a365d;
            border-bottom: 2px solid #b8860b;
            display: inline-block;
            padding: 0 15mm 2mm 15mm;
            margin: 0;
        }

        .completion-text {
            font-size: 10pt;
            color: #444;
            margin: 2mm 0;
        }

        .course-name {
            font-size: 13pt;
            font-weight: bold;
            color: #2c5282;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 2mm 0;
        }

        .course-description {
            font-size: 8pt;
            color: #666;
            margin: 0 0 3mm 0;
        }

        .details {
            margin: 3mm 0;
        }

        .detail-item {
            display: inline-block;
            margin: 0 15mm;
            text-align: center;
        }

        .detail-label {
            font-size: 6pt;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .detail-value {
            font-size: 9pt;
            font-weight: bold;
            color: #1a365d;
        }

        .signatures {
            margin: 9mm 0 0 0;
        }

        .signature-block {
            display: inline-block;
            width: 60mm;
            text-align: center;
            margin: 0 12mm;
            vertical-align: top;
        }

        .signature-line {
            border-top: 1px solid #1a365d;
            padding-top: 2mm;
            margin-top: 11mm;
        }

        .signer-name {
            font-size: 9pt;
            font-weight: bold;
            color: #1a365d;
        }

        .signer-title {
            font-size: 7pt;
            color: #666;
        }

        .footer {
            margin-top: 9mm;
            margin-bottom: 0;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }

        .footer-table td {
            width: 33.33%;
            vertical-align: middle;
            padding: 0 5mm;
        }

        .footer-left {
            text-align: left;
        }

        .footer-center {
            text-align: center;
        }

        .footer-right {
            text-align: right;
        }

        .issue-label {
            font-size: 6pt;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .issue-value {
            font-size: 8pt;
            color: #1a365d;
            font-weight: bold;
        }

        .seal {
            display: inline-block;
            font-size: 7pt;
            font-weight: bold;
            color: #b8860b;
            text-align: center;
            border: 2px solid #b8860b;
            padding: 3mm 5mm;
            letter-spacing: 1px;
        }

        .qr-code {
            width: 14mm;
            height: 14mm;
        }

        .cert-number {
            font-size: 6pt;
            color: #666;
        }

        .verify-text {
            font-size: 5pt;
            color: #999;
        }
    </style>
</head>
<body>
<div class="certificate">
    <div class="certificate-content">
        <div class="header">
            @if($organization_logo)
                <img src="{{ $organization_logo }}" alt="Logo" class="logo" /><br>
            @endif
            <p class="org-name">{{ $organization_name }}</p>
            <p class="org-tagline">Professional Development & Training</p>
        </div>

        <p class="title">Certificate</p>
        <p class="subtitle">of Completion</p>

        <p class="certify-text">This is to certify that</p>
        <p class="recipient-name">{{ $trainee_name }}</p>
        <p class="completion-text">has successfully completed the training program</p>

        <p class="course-name">{{ $course_name }}</p>
        @if($course_description)
            <p class="course-description">{{ Str::limit($course_description, 120) }}</p>
        @endif

        <div class="details">
            @if($start_date && $end_date)
                <div class="detail-item">
                    <div class="detail-label">Training Period</div>
                    <div class="detail-value">{{ $start_date }} - {{ $end_date }}</div>
                </div>
            @endif
            @if($total_hours)
                <div class="detail-item">
                    <div class="detail-label">Duration</div>
                    <div class="detail-value">{{ $total_hours }} Hours</div>
                </div>
            @endif
            @if($score)
                <div class="detail-item">
                    <div class="detail-label">Score</div>
                    <div class="detail-value">{{ $score }}%</div>
                </div>
            @endif
        </div>

        <div class="signatures">
            @php
                $displayTrainers = collect($trainers)->take(2);
                $hasAuthorized = !empty($authorized_signatory);
            @endphp

            @foreach($displayTrainers as $trainer)
                <div class="signature-block">
                    <div class="signature-line">
                        <div class="signer-name">{{ $trainer->name ?? $trainer['name'] ?? 'Instructor' }}</div>
                        <div class="signer-title">Course Instructor</div>
                    </div>
                </div>
            @endforeach

            @if($hasAuthorized)
                <div class="signature-block">
                    <div class="signature-line">
                        <div class="signer-name">{{ $authorized_signatory }}</div>
                        <div class="signer-title">Director</div>
                    </div>
                </div>
            @endif
        </div>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td class="footer-left">
                        <div class="issue-label">Date of Issue</div>
                        <div class="issue-value">{{ $issue_date }}</div>
                    </td>
                    <td class="footer-center">
                        <div class="seal">CERTIFIED</div>
                    </td>
                    <td class="footer-right">
                        @if($qr_code)
                            <img src="{{ $qr_code }}" alt="QR" class="qr-code" /><br>
                        @endif
                        <div class="cert-number">{{ $certificate_code }}</div>
                        <div class="verify-text">Scan to verify</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>
