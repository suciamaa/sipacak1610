<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;
    protected $fillable = ['name', 'username', 'email', 'password', 'role'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['email_verified_at'=>'datetime','password'=>'hashed']; }
    public function petugas(): HasOne { return $this->hasOne(Petugas::class); }
    public function activityLogs(): HasMany { return $this->hasMany(ActivityLog::class); }

    public function sendPasswordResetNotification($token): void { $this->notify(new ResetPasswordNotification($token)); }

    public function sendEmailVerificationNotification(): void { $this->notify(new VerifyEmailNotification()); }
}
