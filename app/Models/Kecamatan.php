<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Kecamatan extends Model {
    protected $table = 'kecamatan';
    protected $fillable = ['nama_kecamatan'];
    public function petugasAsal(): HasMany { return $this->hasMany(Petugas::class, 'kecamatan_asal_id'); }
    public function penugasan(): HasMany { return $this->hasMany(Penugasan::class); }
}
