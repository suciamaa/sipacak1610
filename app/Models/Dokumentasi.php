<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Dokumentasi extends Model {
    protected $table='dokumentasi';
    protected $fillable=[
        'detail_progress_id','nama_file','file_path','mime_type','ukuran',
        'latitude','longitude','gps_accuracy','captured_at','captured_timezone',
        'server_received_at','capture_source',
        'desa','kecamatan','kabupaten','provinsi','altitude','speed_kmh'
    ];
    protected function casts(): array {
        return ['latitude'=>'decimal:7','longitude'=>'decimal:7','gps_accuracy'=>'decimal:2','altitude'=>'decimal:2','speed_kmh'=>'decimal:2','captured_at'=>'datetime','server_received_at'=>'datetime'];
    }
    public function detail(): BelongsTo { return $this->belongsTo(DetailProgress::class,'detail_progress_id'); }
}
