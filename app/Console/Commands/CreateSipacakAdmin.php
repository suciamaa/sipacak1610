<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
class CreateSipacakAdmin extends Command {
 protected $signature='sipacak:make-admin {email} {name}'; protected $description='Buat atau promosikan akun admin SIPACAK';
 public function handle():int { $email=$this->argument('email');$user=User::firstOrNew(['email'=>$email]);$user->name=$this->argument('name');$user->role='admin';if(!$user->username){$base=strtolower(preg_replace('/[^a-z0-9]/','',explode('@',$email)[0]));$base=substr($base?:'adminuser',0,65);$candidate=str_pad($base,7,'x').random_int(10,99);while(User::where('username',$candidate)->exists()){$candidate=substr($base,0,65).random_int(100,999);}$user->username=$candidate;}if(!$user->exists){$password=$this->secret('Password admin (minimal 12 karakter)');if(strlen((string)$password)<12){$this->error('Password minimal 12 karakter.');return self::FAILURE;}$user->password=Hash::make($password);$user->email_verified_at=now();}$user->save();$this->info('Akun admin siap: '.$email.' | Username: '.$user->username);return self::SUCCESS; }
}
