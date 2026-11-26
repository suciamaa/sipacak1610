<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
@page { size: A4 portrait; margin: 10mm; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { font-family: "Times", serif; font-size: 9.5pt; line-height: 1.22; color: #111; }
.page {
    position: relative;
    width: 100%;
    padding: 0 10mm;
    margin: 0;
    page-break-inside: auto;
    break-inside: auto;
}
.page-break { height: 0; margin: 0; padding: 0; page-break-after: always; break-after: page; }
h2 { font-size: 11pt; font-weight: bold; text-align: center; margin: 0 0 10mm; }
.receipt-title { padding-top: 3mm; margin-bottom: 13mm; }
.receipt-logo { position: absolute; top: 0; left: 10mm; width: 19mm; height: 19mm; }
.receipt-logo img { width: 19mm; height: auto; }
.line { display: block; width: 100%; margin: 0 0 4mm; line-height: 1.25; page-break-inside: avoid; }
.label { display: inline-block; width: 43mm; vertical-align: top; }
.colon { display: inline-block; width: 4mm; vertical-align: top; }
.line .value { display: inline-block; width: calc(100% - 47mm); vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
.sign { position: absolute; top: 91mm; left: 10mm; width: calc(100% - 20mm); border-collapse: collapse; table-layout: fixed; }
.sign td { width: 33.333%; text-align: left; vertical-align: top; padding: 4px 8px 0 0; line-height: 1.22; }
.under { text-decoration: underline; font-weight: normal; }
.center { text-align: center; }
.right { text-align: right; }
.ordinary-line, .identity-line { display: block; width: 100%; margin: 0 0 1.5mm; line-height: 1.2; }
.ordinary-line span:first-child { display: inline-block; width: 52mm; }
.ordinary-line span:nth-child(2) { display: inline-block; width: 4mm; }
.ordinary-line span:last-child { display: inline; overflow-wrap: break-word; }
.identity-line span:first-child { display: inline-block; width: 23mm; }
.identity-line span:nth-child(2) { display: inline-block; width: 4mm; }
.identity-line span:last-child { display: inline; overflow-wrap: break-word; }
.grid { border-collapse: collapse; table-layout: fixed; margin-top: 3mm; margin-bottom: 3mm; }
.wide-table { width: calc(100% + 20mm); margin-left: -10mm; margin-right: -10mm; }
.grid td, .grid th { border: 0.6px solid #333; padding: 3mm 2mm; vertical-align: top; line-height: 1.2; overflow-wrap: break-word; word-wrap: break-word; }
.grid th { text-align: center; font-weight: normal; }
.detail-table td { padding-top: 2.5mm; padding-bottom: 2.5mm; }
.detail-line { display: block; width: 100%; line-height: 1.25; margin: 0 0 1mm; page-break-inside: avoid; }
.detail-label { display: inline-block; width: 38mm; vertical-align: top; }
.detail-colon { display: inline-block; width: 4mm; text-align: center; vertical-align: top; }
.detail-value { display: inline-block; width: calc(100% - 42mm); vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
p { margin: 3mm 0; line-height: 1.25; overflow-wrap: break-word; }
.signature { width: 100%; margin-top: 11mm; border-collapse: collapse; table-layout: fixed; }
.signature td { width: 50%; vertical-align: top; text-align: left; padding: 0 8px; line-height: 1.22; }
.calculation-title { margin: 8mm 0 2mm; font-size: 10pt; text-align: center; }
.calculation { width: 72%; margin: 0 auto; border-collapse: collapse; }
.calculation td { padding: 1mm 2mm; }
.ppk-block { width: 100%; margin-top: 7mm; padding-right: 2mm; text-align: right; line-height: 1.22; }
.expenditure-table td { padding-top: 3mm; padding-bottom: 3mm; }
</style>
</head>
<body>
@include('pdf.partials.kwitansi-document')
</body>
</html>
