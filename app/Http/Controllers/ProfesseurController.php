<?php

namespace App\Http\Controllers;

use App\Models\Professeur;
use App\Http\Requests\StoreProfesseurRequest;
use App\Http\Requests\UpdateProfesseurRequest;

class ProfesseurController extends Controller
{
    public function index()
    {
        return Professeur::with(['user', 'departement'])->get();
    }

    public function store(StoreProfesseurRequest $request)
    {
        $professeur = Professeur::create($request->validated());
        return response()->json($professeur, 201);
    }

    public function show(Professeur $professeur)
    {
        return $professeur->load(['user', 'departement', 'examens']);
    }

    public function update(UpdateProfesseurRequest $request, Professeur $professeur)
    {
        $professeur->update($request->validated());
        return $professeur;
    }

    public function destroy(Professeur $professeur)
    {
        $professeur->delete();
        return response()->noContent();
    }
}
