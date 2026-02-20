<?php

// app/Models/Organization.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Organization extends Model {
    protected $fillable = ['name', 'slug', 'is_active', 'blocked_at', 'block_reason'];

    public function users(): HasMany {
        return $this->hasMany(User::class);
    }

    public function isBlocked(): bool {
        return !is_null($this->blocked_at);
    }
    public function deactivate(string $reason = null): void {
        $this->is_active = false;
        $this->blocked_at = now();
        $this->block_reason = $reason;
        $this->save();

        // Opcional: Registrar en actividad
        activity()
            ->performedOn($this)
            ->log('Organización desactivada: ' . ($reason ?? 'Sin razón'));
    }

    public function activate(): void {
        $this->is_active = true;
        $this->blocked_at = null;
        $this->block_reason = null;
        $this->save();

        activity()
            ->performedOn($this)
            ->log('Organización activada');
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Método para cerrar empresa (solo creador)
    public function close(string $reason = null): void {
        // Verificar que quien ejecuta es el creador
        if (auth()->id() !== $this->created_by) {
            abort(403, 'Solo el creador de la empresa puede cerrarla.');
        }

        // Dar de baja a TODOS los usuarios (soft delete)
        foreach ($this->users as $user) {
            $user->deleted_at = now();
            $user->save();
        }

        // Cerrar la organización
        $this->is_active = false;
        $this->blocked_at = now();
        $this->block_reason = $reason ?? 'Cierre solicitado por creador';
        $this->save();

        activity()
            ->performedOn($this)
            ->withProperties(['users_affected' => $this->users->count()])
            ->log('Empresa cerrada por su creador');
    }

    // Método para verificar si el usuario puede cerrar
    public function canBeClosedBy(User $user): bool {
        return $user->id === $this->created_by || $user->is_platform_admin;
    }
}
