<?php
namespace App\Http\Controllers\Petugas;
use App\Http\Controllers\Controller;
use App\Models\Penugasan;
class DashboardController extends Controller { public function __invoke(){ $assignments=Penugasan::with(['kegiatan','kecamatan'])->whereHas('petugas',fn($q)=>$q->where('user_id',auth()->id()))->where('status_penugasan','aktif')->latest()->get();return view('petugas.dashboard',compact('assignments')); } }
