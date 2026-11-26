<!doctype html><html lang="id"><head><meta charset="utf-8"><style>
@page{size:A4 portrait;margin:10mm 10mm 10mm 10mm}
*{box-sizing:border-box}
body{font-family:"Times",serif;font-size:9pt;line-height:1.18;color:#111}
.page{position:relative;min-height:0;width:100%;page-break-inside:avoid;break-inside:avoid}
.page-break{page-break-after:always;break-after:page}
h2{font-size:9pt;font-weight:bold;text-align:center;margin:0 0 16px;text-decoration:none}
.page:first-child h2{padding-top:24mm;margin-bottom:12mm}
.line{display:block;width:100%;margin:2.2px 0;line-height:1.2;overflow-wrap:break-word;page-break-inside:avoid}
.label{display:inline-block;width:42mm;vertical-align:top}.line .value{display:inline-block;width:calc(100% - 46mm);vertical-align:top}
.sign{position:absolute;top:91mm;left:0;width:100%;border-collapse:collapse;table-layout:fixed}
.sign td{width:33.333%;text-align:left;vertical-align:top;padding:4px 10px 0 0;line-height:1.2}
.under{text-decoration:underline;font-weight:normal}
.center{text-align:center}.right{text-align:right}
.plain{width:100%;border-collapse:collapse}
.detail-lines{width:100%;border-collapse:collapse;table-layout:fixed}
.detail-lines td{border:0;padding:1px 2px;vertical-align:top;line-height:1.2}
.detail-lines td:first-child{width:39mm;white-space:nowrap}
.detail-lines td:nth-child(2){width:4mm;text-align:center}
.calculation{width:72%;margin-left:auto;margin-right:auto}
.calculation td{padding:1px 3px}
.ppk-block{padding-right:2mm;line-height:1.2}
.plain td{padding:2px 3px;vertical-align:top}
.grid{width:100%;border-collapse:collapse;margin-top:12px;table-layout:fixed}
.grid td,.grid th{border:0.6px solid #333;padding:4px 5px;vertical-align:top;line-height:1.2}
.grid th{text-align:center;font-weight:normal}
.signature{width:100%;margin-top:15mm;border-collapse:collapse;table-layout:fixed}
.signature td{width:50%;vertical-align:top;text-align:left;padding:0 8px;line-height:1.2}
.signature .name{padding-top:58px;text-decoration:underline}
.small{font-size:8pt}\n/* Detail petugas tanpa kotak, titik dua sejajar */
.detail-lines{display:block;width:100%;border:0;margin:0;padding:0}
.detail-line{display:block;width:100%;clear:both;line-height:1.25;padding:1px 0;border:0}
.detail-line span{display:inline-block;vertical-align:top}
.detail-line span:first-child{width:39mm}
.detail-line span:nth-child(2){width:4mm;text-align:center}
.detail-line span:last-child{width:calc(100% - 43mm)}
/* Hindari halaman kosong akibat tinggi minimum halaman yang terlalu besar */
.page{position:relative;min-height:0;width:100%;page-break-inside:avoid;break-inside:avoid}
.page-break{page-break-after:always;break-after:page}

</style></head><body>
@foreach($items as $index => $item)
@include('pdf.partials.kwitansi-document', ['assignment' => $item['assignment'], 'progress' => $item['progress'], 'admin' => $item['admin'], 'data' => $item['data']])
@if(!$loop->last)<div class="page-break"></div>@endif
@endforeach
</body></html>
