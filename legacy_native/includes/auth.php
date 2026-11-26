<?php
declare(strict_types=1);
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!current_user()) redirect('/login.php'); }
function require_role(string $role): void { require_login(); if ((current_user()['role_user'] ?? '') !== $role) { http_response_code(403); exit('Akses ditolak.'); } }
function login_user(array $user): void { session_regenerate_id(true); unset($_SESSION['selected_petugas_id']); $_SESSION['user']=['id_user'=>(int)$user['id_user'],'id_kegiatan'=>$user['id_kegiatan']===null?null:(int)$user['id_kegiatan'],'username'=>$user['username'],'role_user'=>$user['role_user']]; }

function require_selected_petugas(int $petugasId): void {
    require_role('petugas');
    if ($petugasId < 1 || (int)($_SESSION['selected_petugas_id'] ?? 0) !== $petugasId) {
        http_response_code(403); exit('Identitas petugas belum dipilih atau tidak sesuai sesi. Kembali ke dashboard dan pilih identitas Anda.');
    }
    global $pdo;
    $q=$pdo->prepare('SELECT COUNT(*) FROM petugas WHERE id_petugas=? AND id_user=? AND deleted_at IS NULL');
    $q->execute([$petugasId,current_user()['id_user']]);
    if (!(int)$q->fetchColumn()) { http_response_code(403); exit('Identitas petugas tidak terhubung dengan akun login.'); }
}
