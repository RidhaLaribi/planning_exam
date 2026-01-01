<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Http\Requests\StoreDepartementRequest;
use App\Http\Requests\UpdateDepartementRequest;

class DepartementController extends Controller
{
    public function index()
    {
        return Departement::withCount(['formations', 'professeurs'])->get();
    }

    public function store(StoreDepartementRequest $request)
    {
        $departement = Departement::create($request->validated());
        return response()->json($departement, 201);
    }

    public function show(Departement $departement)
    {
        return $departement->load(['formations', 'professeurs']);
    }

    public function update(UpdateDepartementRequest $request, Departement $departement)
    {
        $departement->update($request->validated());
        return $departement;
    }

    public function destroy(Departement $departement)
    {
        $departement->delete();
        return response()->noContent();
    }
}
