<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Petugas extends Model {
    protected $table = 'petugas';
    protected $fillable = ['user_id','nama_lengkap','kecamatan_asal_id','desa_kelurahan_asal_id','nomor_whatsapp'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function kecamatanAsal(): BelongsTo { return $this->belongsTo(Kecamatan::class, 'kecamatan_asal_id'); }
    public function desaKelurahanAsal(): BelongsTo { return $this->belongsTo(MasterDesaKelurahan::class, 'desa_kelurahan_asal_id'); }
    public function penugasan(): HasMany { return $this->hasMany(Penugasan::class); }
}
