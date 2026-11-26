<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class DetailProgress extends Model {
    protected $table='detail_progress'; protected $fillable=['progress_id','desa','sls','desa_kelurahan_id','jenis_wilayah','jenis_data_wilayah','sls_id','blok_sensus','sub_sls_id','jam_mulai','jam_selesai','assignment_keluarga','assignment_usaha','assignment_usaha_dengan_keluarga','jumlah_assignment','pemecahan_masalah','jumlah_responden','jumlah_responden_diawasi','jumlah_dokumen_diperiksa','perjalanan_jam_mulai','perjalanan_jam_selesai','perjalanan_uraian','koordinasi_jam_mulai','koordinasi_jam_selesai','koordinasi_uraian','kegiatan_jam_mulai','kegiatan_jam_selesai','kegiatan_uraian','kegiatan_rincian','output_rincian','output_uraian','permasalahan_uraian'];
    protected $casts = ['kegiatan_rincian' => 'array', 'output_rincian' => 'array'];
    public function progress(): BelongsTo { return $this->belongsTo(ProgressHarian::class,'progress_id'); }
    public function desaKelurahan() { return $this->belongsTo(MasterDesaKelurahan::class, 'desa_kelurahan_id'); }
    public function masterSls() { return $this->belongsTo(MasterSls::class, 'sls_id'); }
    public function masterSubSls() { return $this->belongsTo(MasterSubSls::class, 'sub_sls_id'); }
    public function uraian(): HasMany { return $this->hasMany(DetailProgressUraian::class, 'detail_progress_id'); }
    public function permasalahan(): HasMany { return $this->hasMany(DetailProgressPermasalahan::class, 'detail_progress_id'); }
    public function dokumentasi(): HasMany { return $this->hasMany(Dokumentasi::class,'detail_progress_id'); }
}
