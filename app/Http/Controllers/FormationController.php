<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use App\Http\Requests\StoreFormationRequest;
use App\Http\Requests\UpdateFormationRequest;

class FormationController extends Controller
{
    public function index()
    {
        return Formation::with('departement')->get();
    }

    public function store(StoreFormationRequest $request)
    {
        $formation = Formation::create($request->validated());
        return response()->json($formation, 201);
    }

    public function show(Formation $formation)
    {
        return $formation->load(['departement', 'modules', 'etudiants']);
    }

    public function update(UpdateFormationRequest $request, Formation $formation)
    {
        $formation->update($request->validated());
        return $formation;
    }

    public function destroy(Formation $formation)
    {
        $formation->delete();
        return response()->noContent();
    }
}
