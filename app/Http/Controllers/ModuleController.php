<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Http\Requests\StoreModuleRequest;
use App\Http\Requests\UpdateModuleRequest;

class ModuleController extends Controller
{
    public function index()
    {
        return Module::with(['formation', 'preRequis'])->get();
    }

    public function store(StoreModuleRequest $request)
    {
        $module = Module::create($request->validated());
        return response()->json($module, 201);
    }

    public function show(Module $module)
    {
        return $module->load(['formation', 'preRequis', 'examens']);
    }

    public function update(UpdateModuleRequest $request, Module $module)
    {
        $module->update($request->validated());
        return $module;
    }

    public function destroy(Module $module)
    {
        $module->delete();
        return response()->noContent();
    }
}
