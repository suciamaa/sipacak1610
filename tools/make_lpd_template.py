# Membuat ulang templates/lpd_template.pdf (butuh: pip install reportlab). Jalankan: python3 make_lpd_template.py
# Koordinat diukur dari Template_LPD_Harian.pdf (revisi 25 Nov 2026). Y asal kiri-atas; baseline teks = nilai Y.
from reportlab.pdfgen import canvas
W,H=595.28,841.89
c=canvas.Canvas('lpd_template.pdf',pagesize=(W,H)); Y=lambda y:H-y
COLS=(36.0,56.82,128.05,358.13,450.15,560.18)
c.setFont('Helvetica-Bold',9); c.drawCentredString(298.0,Y(45.86),'LAPORAN PERJALANAN DINAS DALAM KOTA < 8 JAM')
c.setFont('Helvetica',9)
for y,l in ((72.89,'Nama Pelaksana'),(85.48,'Wilayah Tugas'),(98.08,'Tanggal')): c.drawString(36,Y(y),l); c.drawString(119.05,Y(y),':')
c.setLineWidth(0.4)
for y in (104.83,169.25,193.25): c.line(COLS[0],Y(y),COLS[5],Y(y))
for x in COLS: c.line(x,Y(104.83),x,Y(193.25))
c.setFont('Helvetica-Bold',9)
for x,y,t in ((79.83,134.69,'Pukul'),(79.62,147.69,'(WIB)'),(42.42,141.69,'No'),(193.25,141.69,'Uraian Laporan Kegiatan'),(375.92,141.69,'Permasalahan'),(463.35,141.69,'Pemecahan Masalah')): c.drawString(x,Y(y),t)
c.setFont('Helvetica',9)
for x,t in ((42.83,'(1)'),(87.42,'(2)'),(238.08,'(3)'),(399.13,'(4)'),(500.15,'(5)')): c.drawString(x,Y(187.71),t)
c.showPage()
c.setFillColorRGB(0.678,0.667,0.667); c.setLineWidth(0.5); c.rect(36,Y(63.6),574.7-36,18,stroke=1,fill=1)
c.setFillColorRGB(0,0,0); c.setFont('Helvetica',9); c.drawCentredString(304.7,Y(59.28),'DOKUMENTASI PELAKSANAAN')
c.showPage(); c.save()
