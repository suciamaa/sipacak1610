<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{AuthenticatedSessionController,RegisteredUserController,PasswordResetOtpController};
use App\Http\Controllers\Admin\{DashboardController as AdminDashboard, KegiatanController, PenugasanController, LpdController, KwitansiController, MonitoringController};
use App\Http\Controllers\Petugas\{DashboardController as PetugasDashboard, ProgressController, DokumenController};

Route::get('/',fn()=>redirect()->route('login'))->name('home');
Route::middleware('guest')->group(function(){
 Route::get('/login',[AuthenticatedSessionController::class,'create'])->name('login');Route::post('/login',[AuthenticatedSessionController::class,'store'])->name('login.store');
 Route::get('/register',[RegisteredUserController::class,'create'])->name('register');Route::post('/register',[RegisteredUserController::class,'store'])->name('register.store');
 Route::get('/forgot-password',[PasswordResetOtpController::class,'create'])->name('password.request');
 Route::post('/forgot-password',[PasswordResetOtpController::class,'send'])->middleware('throttle:10,15')->name('password.email');
 Route::get('/verify-reset-otp',[PasswordResetOtpController::class,'showVerify'])->name('password.otp.verify');
 Route::post('/verify-reset-otp',[PasswordResetOtpController::class,'verify'])->middleware('throttle:10,15')->name('password.otp.check');
 Route::post('/resend-reset-otp',[PasswordResetOtpController::class,'resend'])->middleware('throttle:10,15')->name('password.otp.resend');
 Route::get('/reset-password/otp',[PasswordResetOtpController::class,'showReset'])->name('password.otp.reset');
 Route::post('/reset-password/otp',[PasswordResetOtpController::class,'reset'])->middleware('throttle:10,15')->name('password.otp.update');
});
Route::middleware('auth')->group(function(){
 Route::post('/logout',[AuthenticatedSessionController::class,'destroy'])->name('logout');
//  Route::get('/email/verify',[EmailVerificationController::class,'notice'])->name('verification.notice');Route::get('/email/verify/{id}/{hash}',[EmailVerificationController::class,'verify'])->middleware('signed')->name('verification.verify');Route::post('/email/verification-notification',[EmailVerificationController::class,'resend'])->middleware('throttle:6,1')->name('verification.send');
 Route::get('/dashboard',fn()=>auth()->user()->role==='admin'?redirect()->route('admin.dashboard'):redirect()->route('petugas.dashboard'))->name('dashboard');
 Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function(){
  Route::get('/dashboard',AdminDashboard::class)->name('dashboard');
  // Semua pengelolaan berpusat di Dashboard -> Kelola Kegiatan. Rute lama diarahkan ke sana.
  Route::redirect('/administrasi','/admin/dashboard')->name('administrasi.index');
  Route::get('/administrasi/{kegiatan}',fn(\App\Models\Kegiatan $kegiatan)=>redirect()->route('admin.kegiatan.show',$kegiatan))->name('administrasi.show');
  Route::get('/kegiatan',[KegiatanController::class,'index'])->name('kegiatan.index');
  Route::resource('kegiatan',KegiatanController::class)->except('index');
  Route::get('/kegiatan/{kegiatan}/penugasan/create',[PenugasanController::class,'create'])->name('penugasan.create');Route::post('/kegiatan/{kegiatan}/penugasan',[PenugasanController::class,'store'])->name('penugasan.store');Route::get('/penugasan/{penugasan}/edit',[PenugasanController::class,'edit'])->name('penugasan.edit');Route::put('/penugasan/{penugasan}',[PenugasanController::class,'update'])->name('penugasan.update');Route::delete('/penugasan/{penugasan}',[PenugasanController::class,'destroy'])->name('penugasan.destroy');
  Route::get('/kegiatan/{kegiatan}/lpd',[LpdController::class,'index'])->name('lpd.index');Route::get('/kegiatan/{kegiatan}/lpd/progress/{progress}',[LpdController::class,'showProgress'])->name('lpd.progress.show');Route::get('/kegiatan/{kegiatan}/lpd/progress/{progress}/photo/{photo}',[LpdController::class,'progressPhoto'])->name('lpd.progress.photo');Route::get('/kegiatan/{kegiatan}/lpd/{document}/download',[LpdController::class,'download'])->name('lpd.download');Route::post('/kegiatan/{kegiatan}/lpd/generate/{progress}',[LpdController::class,'generate'])->name('lpd.generate');
  
  Route::get('/kegiatan/{kegiatan}/kwitansi',[KwitansiController::class,'index'])->name('kwitansi.index');Route::get('/kegiatan/{kegiatan}/kwitansi/template',[KwitansiController::class,'template'])->name('kwitansi.template');Route::post('/kegiatan/{kegiatan}/kwitansi/import',[KwitansiController::class,'import'])->name('kwitansi.import');Route::post('/kegiatan/{kegiatan}/kwitansi/generate/{progress}',[KwitansiController::class,'generate'])->name('kwitansi.generate');
  Route::get('/kegiatan/{kegiatan}/monitoring',[MonitoringController::class,'index'])->name('monitoring.index');Route::get('/kegiatan/{kegiatan}/monitoring/petugas/{penugasan}',[MonitoringController::class,'show'])->name('monitoring.show');Route::put('/kegiatan/{kegiatan}/monitoring/detail/{detail}',[MonitoringController::class,'updateDetail'])->name('monitoring.detail.update');Route::get('/kegiatan/{kegiatan}/monitoring/file/{document}',[MonitoringController::class,'file'])->name('monitoring.file');Route::get('/kegiatan/{kegiatan}/monitoring/preview/{document}',[MonitoringController::class,'preview'])->name('monitoring.preview');
 });
 Route::middleware(['role:petugas'])->prefix('petugas')->name('petugas.')->group(function(){
  Route::get('/dashboard',PetugasDashboard::class)->name('dashboard');Route::get('/penugasan/{penugasan}/progress',[ProgressController::class,'index'])->name('progress.index');Route::get('/penugasan/{penugasan}/wilayah/desa',[ProgressController::class,'desaOptions'])->name('progress.wilayah.desa');Route::get('/penugasan/{penugasan}/wilayah/blok-sensus',[ProgressController::class,'blokSensusOptions'])->name('progress.wilayah.blok-sensus');Route::get('/penugasan/{penugasan}/wilayah/desa/{desa}/sls',[ProgressController::class,'slsOptions'])->name('progress.wilayah.sls');Route::get('/penugasan/{penugasan}/progress/create',[ProgressController::class,'create'])->name('progress.create');Route::post('/penugasan/{penugasan}/progress',[ProgressController::class,'store'])->name('progress.store');Route::get('/penugasan/{penugasan}/progress/{progress}/edit',[ProgressController::class,'edit'])->name('progress.edit');Route::put('/penugasan/{penugasan}/progress/{progress}',[ProgressController::class,'update'])->name('progress.update');Route::delete('/penugasan/{penugasan}/progress/{progress}',[ProgressController::class,'destroy'])->name('progress.destroy');Route::get('/penugasan/{penugasan}/dokumentasi/{document}',[ProgressController::class,'documentation'])->name('progress.documentation');Route::get('/penugasan/{penugasan}/documents',[DokumenController::class,'index'])->name('documents.index');Route::get('/penugasan/{penugasan}/documents/lpd/{document}',[DokumenController::class,'lpd'])->name('documents.lpd');Route::get('/penugasan/{penugasan}/documents/kwitansi/{document}',[DokumenController::class,'kwitansi'])->name('documents.kwitansi');Route::get('/penugasan/{penugasan}/documents/download/{type}',[DokumenController::class,'download'])->whereIn('type',['lpd','kwitansi'])->name('documents.download');
 });
});
