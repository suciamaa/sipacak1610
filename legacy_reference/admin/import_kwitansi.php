<?php
// Endpoint lama hanya membaca dan menampilkan Excel tanpa menyimpan kwitansi.
// Alihkan ke alur import aktif yang memvalidasi dan menyimpan data.
require_once __DIR__.'/../includes/functions.php';
redirect('admin/kwitansi_excel.php');
