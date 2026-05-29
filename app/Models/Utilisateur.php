<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Utilisateur extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = ['nom', 'prenom', 'email', 'role', 'motdePasse'];

    protected $hidden = ['motdePasse', 'remember_token'];

    protected function casts(): array
    {
        return [
            'motdePasse' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->motdePasse;
    }

    public function seConnecter(string $nomToken = 'auth_token'): string
    {
        return $this->createToken($nomToken)->plainTextToken;
    }

    public function seDeconnecter(): void
    {
        $this->tokens()->delete();
    }

    public function consulterProfil(): array
    {
        return $this->only(['id', 'nom', 'prenom', 'email', 'role']);
    }

    public function modifierProfil(array $attributs): bool
    {
        return $this->update($attributs);
    }

    public function administrateur()
    {
        return $this->hasOne(Administrateur::class, 'utilisateur_id');
    }

    public function conducteur()
    {
        return $this->hasOne(Conducteur::class, 'utilisateur_id');
    }
}
