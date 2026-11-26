<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 portrait;
            /* Margin halaman dikendalikan oleh wrapper agar konsisten di Dompdf. */
            margin: 0;
        }

        @font-face {
            font-family: "Arial";
            font-style: normal;
            font-weight: 400;
            src: url("{{ public_path('fonts/Arial-Regular.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: "Arial";
            font-style: normal;
            font-weight: 700;
            src: url("{{ public_path('fonts/Arial-Bold.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: "Arial";
            font-style: italic;
            font-weight: 400;
            src: url("{{ public_path('fonts/Arial-Italic.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: "Arial";
            font-style: italic;
            font-weight: 700;
            src: url("{{ public_path('fonts/Arial-BoldItalic.ttf') }}") format("truetype");
        }

        * { font-family: "Arial", sans-serif; }
        html, body { margin: 0; padding: 0; }
        body { font-family: "Arial", sans-serif; font-size: 9pt; line-height: 1.15; color: #111; }

        .lpd-report {
            padding: 12mm 12.5mm 10mm;
        }

        .report-title {
            margin: 0 0 7mm;
            text-align: center;
            font-size: 10pt;
            line-height: 1.2;
            font-weight: 700;
        }

        /* Identitas memakai baris inline-block, bukan tabel fixed-layout.
           Dompdf sebelumnya memperlebar kolom nilai sehingga jarak setelah ':' jauh. */
        .identity-table {
            width: auto;
            margin: 0 0 2mm;
            border: 0;
            font-size: 9pt;
            line-height: 1.2;
        }
        .identity-row { display: block; white-space: nowrap; margin: 0 0 0.6mm; }
        .identity-label { display: inline-block; width: 31mm; vertical-align: top; }
        .identity-colon { display: inline-block; width: 2mm; vertical-align: top; }
        .identity-value { display: inline-block; margin-left: 0.5mm; vertical-align: top; }

        .lpd-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9pt;
            line-height: 1.15;
        }
        .lpd-table th, .lpd-table td {
            border: 0.65pt solid #333;
            padding: 1mm 1.2mm;
            vertical-align: top;
        }
        /* Proporsi kolom diadaptasi dari generator LPD native se2026-lanjutan.
           Gunakan persentase agar Dompdf tidak meratakan semua kolom. */
        .lpd-table th:nth-child(1), .lpd-table td:nth-child(1) { width: 4.6%; }
        .lpd-table th:nth-child(2), .lpd-table td:nth-child(2) { width: 14.3%; }
        .lpd-table th:nth-child(3), .lpd-table td:nth-child(3) { width: 42.6%; }
        .lpd-table th:nth-child(4), .lpd-table td:nth-child(4) { width: 17%; }
        .lpd-table th:nth-child(5), .lpd-table td:nth-child(5) { width: 21.5%; }
        .lpd-table thead { display: table-header-group; }
        .lpd-table tr { page-break-inside: auto; }
        .ishoma-row { page-break-inside: avoid; }

        .table-headings th {
            height: 18mm;
            padding: 1mm 0.8mm;
            text-align: center;
            vertical-align: middle;
            font-size: 9pt;
            line-height: 1.2;
            font-weight: 700;
        }
        .table-headings th:nth-child(3) { white-space: nowrap; }
        .table-indexes th {
            height: 6.5mm;
            padding: 1mm 0.5mm;
            text-align: center;
            vertical-align: middle;
            font-size: 9pt;
            font-weight: 400;
        }
        .lpd-table tbody td { font-size: 9pt; line-height: 1.15; }
        .number-cell, .time-cell { text-align: center; white-space: nowrap; }
        .narrative-cell, .problem-cell, .solution-cell {
            text-align: justify;
            white-space: pre-line;
            overflow-wrap: break-word;
            word-wrap: break-word;
            hyphens: none;
        }
        .number-cell, .time-cell { vertical-align: top; }
        .activity-row td { padding-top: 1.2mm; padding-bottom: 1.2mm; }
        .ishoma-row td { vertical-align: middle; padding-top: 2mm; padding-bottom: 2mm; }
        .ishoma-cell, .dash-cell { text-align: center; }
        .ishoma-cell { font-weight: 400; }
        .empty-row { text-align: center; padding: 16px !important; }

        .signature-block {
            width: 34%;
            margin-left: auto;
            text-align: center;
            page-break-inside: avoid;
            font-size: 9pt;
        }
        .signature-roomy, .signature-compact { margin-top: 44mm; }
        .signature-date { margin-bottom: 2mm; white-space: nowrap; line-height: 1.2; }
        .signature-label { margin-top: 0; line-height: 1.2; }
        .signature-name { margin-top: 16mm; font-weight: 400; line-height: 1.2; }

        .documentation-page {
            page-break-before: always;
            page-break-inside: avoid;
            padding: 12mm 12.5mm 10mm;
        }
        .documentation-title {
            display: table;
            width: 100%;
            height: 8mm;
            margin: 0;
            padding: 0;
            border: 0.65px solid #555;
            background: #b8b4b4;
            box-sizing: border-box;
            text-align: center;
            font-size: 9pt;
            font-weight: 700;
            page-break-inside: avoid;
        }
        .documentation-title span {
            display: table-cell;
            width: 100%;
            height: 8mm;
            text-align: center;
            vertical-align: middle;
            line-height: 1.2;
            font-weight: 700;
        }
        .photo-grid { width: 100%; margin-top: 17mm; border-collapse: collapse; table-layout: fixed; }
        .photo-grid tr { page-break-inside: avoid; }
        .photo-cell { width: 50%; padding: 0 2mm 8mm; vertical-align: top; }
        .photo-frame { width: 100%; height: 52mm; border-collapse: collapse; border: 0.7px solid #333; table-layout: fixed; }
        .photo-frame td { height: 52mm; padding: 1mm; text-align: center; vertical-align: middle; }
        .photo-frame img { max-width: 100%; max-height: 49mm; width: auto; height: auto; display: block; margin: 0 auto; }
        .photo-frame, .photo-frame td { overflow: hidden; }
        .photo-missing { font-size: 9pt; color: #666; }
        .photo-caption { margin-top: 1mm; padding-left: 1mm; font-size: 9pt; line-height: 1.25; page-break-inside: avoid; }
        .photo-caption strong { font-weight: 700; }
        .no-documentation { margin-top: 25mm; text-align: center; font-size: 9pt; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach($items as $index => $item)
        @include('pdf.partials.lpd-document', [
            'assignment' => $item['assignment'],
            'progress' => $item['progress'],
            'lpdTimeline' => $item['lpdTimeline'],
        ])
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
