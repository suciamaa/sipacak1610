<?php require_once __DIR__.'/includes/bootstrap.php'; if (!current_user()) redirect('/login.php'); redirect(current_user()['role_user']==='admin'?'/admin/dashboard.php':'/petugas/dashboard.php');
