<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Kegiatan extends Model {
    protected $table = 'kegiatan';
    protected $fillable = ['nama_kegiatan','nomor_kegiatan','tanggal_mulai','tanggal_selesai','status','deskripsi','tujuan_akhir','jenis_data_wilayah'];
    protected function casts(): array { return ['tanggal_mulai'=>'date','tanggal_selesai'=>'date']; }
    public function roles(): HasMany { return $this->hasMany(KegiatanRole::class); }
    public function administrasiKwitansi(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(AdministrasiKwitansi::class); }
    public function penugasan(): HasMany { return $this->hasMany(Penugasan::class); }
}
