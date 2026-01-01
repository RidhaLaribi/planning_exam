<?php

namespace App\Http\Controllers;

use App\Models\Etudiant;
use App\Http\Requests\StoreEtudiantRequest;
use App\Http\Requests\UpdateEtudiantRequest;
use Illuminate\Http\Request;

class EtudiantController extends Controller
{
    public function index(Request $request)
    {
        $query = Etudiant::with(['user', 'formation']);
        
        if ($request->has('formation_id')) {
            $query->where('formation_id', $request->formation_id);
        }

        return $query->get();
    }

    public function store(StoreEtudiantRequest $request)
    {
        $etudiant = Etudiant::create($request->validated());
        return response()->json($etudiant, 201);
    }

    public function show(Etudiant $etudiant)
    {
        return $etudiant->load(['user', 'formation', 'modules']);
    }

    public function update(UpdateEtudiantRequest $request, Etudiant $etudiant)
    {
        $etudiant->update($request->validated());
        return $etudiant;
    }

    public function destroy(Etudiant $etudiant)
    {
        $etudiant->delete();
        return response()->noContent();
    }
}
