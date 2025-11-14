<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Can access Filament panel.
     * Allow admin, supervisor, super_admin, and petugas roles
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin', 'supervisor', 'petugas', 'pengurus']);
    }

    /**
     * Activity reports submitted by this user (petugas)
     */
    public function activityReports()
    {
        return $this->hasMany(ActivityReport::class, 'petugas_id');
    }

    /**
     * Activity reports approved by this user (supervisor/admin)
     */
    public function approvedReports()
    {
        return $this->hasMany(ActivityReport::class, 'approved_by');
    }

    /**
     * Jadwal kebersihan assigned to this user (petugas)
     */
    public function jadwalKebersihan()
    {
        return $this->hasMany(JadwalKebersihan::class, 'petugas_id');
    }

    /**
     * Penilaian for this user (petugas)
     */
    public function penilaian()
    {
        return $this->hasMany(Penilaian::class, 'petugas_id');
    }

    /**
     * Penilaian created by this user (penilai/supervisor)
     */
    public function penilaianDibuat()
    {
        return $this->hasMany(Penilaian::class, 'penilai_id');
    }
}
