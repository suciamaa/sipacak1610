<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Lpd extends Model {
    protected $table='lpd'; protected $fillable=['penugasan_id','tanggal','status_lpd','file_pdf','generated_at','layout_version'];
    protected function casts(): array { return ['tanggal'=>'date','generated_at'=>'datetime']; }
    public function penugasan(): BelongsTo { return $this->belongsTo(Penugasan::class); }
}
