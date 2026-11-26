<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . APP_BASE_URL . $path); exit; }
function flash(string $type, string $message): void { $_SESSION['flash'] = ['type'=>$type,'message'=>$message]; }
function show_flash(): void { if (!empty($_SESSION['flash'])) { $f=$_SESSION['flash']; unset($_SESSION['flash']); echo '<div class="alert alert-'.e($f['type']).' alert-dismissible fade show">'.e($f['message']).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; } }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_check(): void { if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) { http_response_code(419); exit('Token keamanan tidak valid. Muat ulang halaman.'); } }
function format_tanggal(?string $date): string { if (!$date) return '-'; return date('d-m-Y', strtotime($date)); }
function tujuan_label(string $v): string { return ['lpd'=>'LPD','kwitansi'=>'Kwitansi','keduanya'=>'LPD dan Kwitansi'][$v] ?? $v; }
function kecamatan_list(): array { return ['INDRALAYA','INDRALAYA UTARA','INDRALAYA SELATAN','TANJUNG RAJA','TANJUNG BATU','MUARA KUANG','RAMBANG KUANG','LUBUK KELIAT','PAYARAMAN','RANTAU ALAI','KANDIS','RANTAU PANJANG','SUNGAI PINANG','PEMULUTAN','PEMULUTAN SELATAN','PEMULUTAN BARAT']; }
function page_header(string $title): void { $u=current_user(); ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=e($title)?> | SIPACAK</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?=APP_BASE_URL?>/assets/css/app.css" rel="stylesheet"></head><body>
<nav class="navbar navbar-expand-lg navbar-dark app-nav"><div class="container-fluid px-4"><a class="navbar-brand fw-bold" href="<?=APP_BASE_URL?>/">SIPACAK</a><div class="ms-auto d-flex align-items-center gap-3 text-white"><span class="small"><?=e($u['username'] ?? '')?> · <?=e($u['role_user'] ?? '')?></span><a class="btn btn-sm btn-outline-light" href="<?=APP_BASE_URL?>/account.php">Pengaturan Akun</a><a class="btn btn-sm btn-outline-light" href="<?=APP_BASE_URL?>/logout.php">Logout</a></div></div></nav><main class="container-fluid px-4 py-4"><div class="mb-4"><div class="text-secondary small">SISTEM INFORMASI PENGELOLAAN KEGIATAN DAN ADMINISTRASI PETUGAS</div><h1 class="h3 fw-bold mb-0"><?=e($title)?></h1></div><?php show_flash(); ?>
<?php }
function page_footer(): void { ?><div class="py-4 text-center text-secondary small">SIPACAK · BPS Kabupaten Ogan Ilir</div></main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html><?php }
