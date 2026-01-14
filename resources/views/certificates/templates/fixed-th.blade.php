<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบประกาศนียบัตร - {{ $trainee_name }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', 'TH Sarabun New', sans-serif;
            background: white;
            position: relative;
        }

        .certificate {
            width: 297mm;
            height: 210mm;
            padding: 15mm;
            background: white;
            position: relative;
        }

        .border-frame {
            border: 3px solid #1e40af;
            border-radius: 10px;
            padding: 10mm;
            height: 100%;
            position: relative;
            box-shadow: inset 0 0 20px rgba(30, 64, 175, 0.1);
        }

        .inner-border {
            border: 1px solid #93c5fd;
            padding: 8mm;
            height: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 8mm;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5mm;
        }

        .org-logo {
            width: 50mm;
            height: auto;
            margin-bottom: 3mm;
        }

        .org-name {
            font-size: 18pt;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 2mm;
        }

        .cert-title {
            font-size: 26pt;
            font-weight: 700;
            color: #991b1b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 3mm;
        }

        .cert-subtitle {
            font-size: 14pt;
            color: #991b1b;
            font-style: italic;
            margin-top: 1mm;
        }

        .content {
            text-align: center;
            margin-top: 5mm;
        }

        .intro-text {
            font-size: 13pt;
            color: #374151;
            margin-bottom: 4mm;
        }

        .trainee-name {
            font-size: @php
                echo mb_strlen($trainee_name) > 40 ? '20pt' :
                     (mb_strlen($trainee_name) > 30 ? '24pt' : '28pt');
            @endphp;
            font-weight: 700;
            color: #1e3a8a;
            margin: 4mm 0;
            padding: 2mm 0;
            border-bottom: 2px solid #1e40af;
            display: inline-block;
            min-width: 60%;
        }

        .program-info {
            margin: 6mm 0;
            text-align: center;
        }

        .program-name {
            font-size: 16pt;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2mm;
        }

        .program-description {
            font-size: 11pt;
            color: #6b7280;
            max-width: 80%;
            margin: 0 auto 2mm auto;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .training-details {
            display: table;
            width: 100%;
            margin: 4mm 0;
            padding: 2mm 0;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row {
            display: table;
            width: 100%;
            margin-bottom: 2mm;
        }

        .detail-item {
            display: table-cell;
            text-align: center;
            width: 33.33%;
            padding: 0 2mm;
        }

        .detail-label {
            font-size: 9pt;
            color: #6b7280;
            margin-bottom: 1mm;
        }

        .detail-value {
            font-size: 12pt;
            font-weight: 600;
            color: #1f2937;
        }

        .skills-section {
            margin-top: 4mm;
            text-align: left;
            max-width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .skills-title {
            font-size: 10pt;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1mm;
        }

        .skills-content {
            font-size: 9pt;
            color: #6b7280;
            line-height: 1.5;
        }

        .footer-section {
            position: absolute;
            bottom: 10mm;
            left: 15mm;
            right: 15mm;
            display: table;
            width: calc(100% - 30mm);
        }

        .signatures-container {
            display: table-cell;
            width: 70%;
        }

        .signatures {
            display: table;
            width: 100%;
        }

        .signature-block {
            display: table-cell;
            text-align: center;
            padding: 0 3mm;
            vertical-align: bottom;
        }

        .signature-image {
            width: 35mm;
            height: 12mm;
            margin-bottom: 2mm;
        }

        .signature-line {
            border-top: 1px solid #374151;
            padding-top: 2mm;
            font-size: 10pt;
            color: #1f2937;
            font-weight: 500;
        }

        .signature-title {
            font-size: 8pt;
            color: #6b7280;
            margin-top: 1mm;
        }

        .qr-container {
            display: table-cell;
            width: 30%;
            text-align: center;
            vertical-align: bottom;
        }

        .qr-code {
            width: 22mm;
            height: 22mm;
            border: 2px solid #e5e7eb;
            padding: 1mm;
            border-radius: 3px;
        }

        .cert-code {
            font-size: 8pt;
            color: #6b7280;
            margin-top: 2mm;
            font-weight: 600;
        }

        .verification-url {
            font-size: 7pt;
            color: #9ca3af;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="border-frame">
            <div class="inner-border">
                <!-- Header -->
                <div class="header">
                    @if($organization_logo)
                        <img src="{{ $organization_logo }}" alt="Logo" class="org-logo" />
                    @endif
                    <div class="org-name">{{ $organization_name }}</div>
                </div>

                <!-- Title -->
                <div class="cert-title">ใบประกาศนียบัตร</div>
                <div class="cert-subtitle">Certificate of Completion</div>

                <!-- Content -->
                <div class="content">
                    <div class="intro-text">ขอมอบให้ไว้เพื่อแสดงว่า</div>

                    <div class="trainee-name">{{ $trainee_name }}</div>

                    <div class="intro-text" style="margin-top: 4mm;">
                        ได้ผ่านการอบรมหลักสูตร
                    </div>

                    <div class="program-info">
                        <div class="program-name">{{ $course_name }}</div>

                        @if($course_description)
                            <div class="program-description">{{ $course_description }}</div>
                        @endif
                    </div>

                    <!-- Training Details -->
                    <div class="training-details">
                        @if($start_date && $end_date)
                            <div class="detail-row">
                                <div class="detail-item">
                                    <div class="detail-label">ระยะเวลาการอบรม</div>
                                    <div class="detail-value">{{ $start_date }} - {{ $end_date }}</div>
                                </div>

                                @if($total_hours)
                                    <div class="detail-item">
                                        <div class="detail-label">รวมชั่วโมง</div>
                                        <div class="detail-value">{{ $total_hours }} ชั่วโมง</div>
                                    </div>
                                @endif

                                <div class="detail-item">
                                    <div class="detail-label">วันที่ออกเอกสาร</div>
                                    <div class="detail-value">{{ $issue_date }}</div>
                                </div>
                            </div>
                        @else
                            <div class="detail-row">
                                @if($total_hours)
                                    <div class="detail-item">
                                        <div class="detail-label">รวมชั่วโมง</div>
                                        <div class="detail-value">{{ $total_hours }} ชั่วโมง</div>
                                    </div>
                                @endif

                                <div class="detail-item">
                                    <div class="detail-label">วันที่ออกเอกสาร</div>
                                    <div class="detail-value">{{ $issue_date }}</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if($skills)
                        <div class="skills-section">
                            <div class="skills-title">ความรู้และทักษะที่ได้รับ:</div>
                            <div class="skills-content">{{ $skills }}</div>
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="footer-section">
                    <div class="signatures-container">
                        <div class="signatures">
                            @php
                                $displayTrainers = collect($trainers)->take(3);
                                $trainerCount = count($displayTrainers);
                                $hasAuthorized = !empty($authorized_signatory);
                                $totalSignatures = $trainerCount + ($hasAuthorized ? 1 : 0);
                            @endphp

                            <!-- Trainer Signatures -->
                            @foreach($displayTrainers as $index => $trainer)
                                <div class="signature-block">
                                    @if(isset($trainer_signatures[$index]))
                                        <img src="{{ $trainer_signatures[$index] }}" alt="Signature" class="signature-image" />
                                    @else
                                        <div style="height: 12mm;"></div>
                                    @endif
                                    <div class="signature-line">{{ $trainer->name ?? $trainer['name'] ?? 'N/A' }}</div>
                                    <div class="signature-title">วิทยากร</div>
                                </div>
                            @endforeach

                            <!-- Authorized Signatory -->
                            @if($authorized_signatory)
                                <div class="signature-block">
                                    @if($authorized_signature)
                                        <img src="{{ $authorized_signature }}" alt="Signature" class="signature-image" />
                                    @else
                                        <div style="height: 12mm;"></div>
                                    @endif
                                    <div class="signature-line">{{ $authorized_signatory }}</div>
                                    <div class="signature-title">ผู้อำนวยการ</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div class="qr-container">
                        <img src="{{ $qr_code }}" alt="QR Code" class="qr-code" />
                        <div class="cert-code">{{ $certificate_code }}</div>
                        <div class="verification-url">ตรวจสอบความถูกต้อง</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
