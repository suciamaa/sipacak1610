# Master Wilayah dan SLS SIPACAK

- `wilayah_ogan_ilir.json` berisi 16 kecamatan, 227 desa, dan 14 kelurahan berdasarkan daftar pada instruksi.
- `desa_sls.json` dan `sub_sls.json` merupakan sumber SLS/Sub-SLS bawaan proyek.
- Seeder hanya mengimpor data SLS/Sub-SLS untuk 11 kecamatan yang secara eksplisit disebut memiliki referensi pada instruksi.
- Variasi ejaan sumber berikut dipetakan ke ejaan master wilayah: `PULAU KABAL` → `Pulau Kabai`, `SUAK BATOK` → `Soak Batok`, `SERIBANDING` → `Sribanding`, `JAGALANA` → `Jagalano`.
- Sumber lama memuat `TALANG TENGAH` untuk Kecamatan Lubuk Keliat, sedangkan daftar master memuat `Talang Tengah Darat` dan `Talang Tengah Laut`. Seeder tidak menebak pemetaannya dan akan memberi peringatan; data 10 SLS tersebut belum ditautkan.
- Lima kecamatan yang tidak termasuk daftar ketersediaan SLS pada instruksi tidak diisi SLS/Sub-SLS, walaupun file lama memiliki entri untuk sebagian wilayah tersebut.
