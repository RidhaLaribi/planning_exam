<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEtudiantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Handling both User creation and Etudiant profile or just profile?
        // Usually API creates both. I'll require user info here if creating from scratch,
        // OR rely on User being created first.
        // User asked for "EtudiantController" CRUD. Standard is: Input has name, email affecting User table?
        // Or Etudiant CRUD just links to existing User?
        // Requirement: "Etudiants (id, nom, prenom... user_id)".
        // I'll assume standard CRUD on Etudiant resource requires user_id OR creates it.
        // I will assume simple Foreign Key requirement here: user_id provided.
        // BUT, better UX is creating User+Etudiant.
        // I'll validate 'user_id' exists.
        
        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'formation_id' => 'required|exists:formations,id',
            'promo' => 'required|string|max:20',
            'user_id' => 'required|exists:users,id|unique:etudiants,user_id',
        ];
    }
}
