<?php

namespace App\Http\Controllers;

use App\Models\LieuExamen;
use App\Http\Requests\StoreLieuExamenRequest;
use App\Http\Requests\UpdateLieuExamenRequest;

class LieuExamenController extends Controller
{
    public function index()
    {
        return LieuExamen::all();
    }

    public function store(StoreLieuExamenRequest $request)
    {
        $lieuExamen = LieuExamen::create($request->validated());
        return response()->json($lieuExamen, 201);
    }

    public function show(LieuExamen $lieuExamen)
    {
        return $lieuExamen;
    }

    public function update(UpdateLieuExamenRequest $request, LieuExamen $lieuExamen)
    {
        $lieuExamen->update($request->validated());
        return $lieuExamen;
    }

    public function destroy(LieuExamen $lieuExamen)
    {
        $lieuExamen->delete();
        return response()->noContent();
    }
}
