<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable {
    use LogsActivity; // Para auditoría
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
        'role',
        'approved_at',
        'is_platform_admin',
        'last_login_at',
        'last_login_ip',
    ];


    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'approved_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_platform_admin' => 'boolean',
        'deletion_requested_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];


    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    public function isApproved(): bool {
        return !is_null($this->approved_at);
    }

    public function isAdmin(): bool {
        return $this->role === 'admin' || $this->is_platform_admin;
    }

    public function hasRole(string $role): bool {
        return $this->role === $role;
    }

    // Configuración de Activity Log
    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'approved_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Usuario {$eventName}");
    }

    public function requestDeletion(): void {
        $this->deletion_requested_at = now();
        $this->deletion_approved = false;
        $this->save();

        activity()
            ->performedOn($this)
            ->log('Solicitó baja de cuenta');
    }

    public function approveDeletion(): void {
        $this->deletion_approved = true;
        $this->deleted_at = now();
        $this->save();

        activity()
            ->performedOn($this)
            ->log('Baja de cuenta aprobada');
    }

    public function rejectDeletion(): void {
        $this->deletion_requested_at = null;
        $this->deletion_approved = false;
        $this->save();

        activity()
            ->performedOn($this)
            ->log('Solicitud de baja rechazada');
    }

    public function hasRequestedDeletion(): bool {
        return !is_null($this->deletion_requested_at) && !$this->deletion_approved;
    }

    public function scopeWithPendingDeletion($query) {
        return $query->whereNotNull('deletion_requested_at')
            ->where('deletion_approved', false);
    }
}
