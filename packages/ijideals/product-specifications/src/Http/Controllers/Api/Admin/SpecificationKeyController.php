<?php

namespace Ijideals\ProductSpecifications\Http\Controllers\Api\Admin; // Corrected namespace

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ijideals\ProductSpecifications\Models\SpecificationKey; // Corrected namespace for model
use Illuminate\Validation\Rule;

class SpecificationKeyController extends Controller
{
    public function __construct()
    {
        // TODO: Add appropriate admin-level authentication and authorization middleware
        // For example: $this->middleware(['auth:sanctum', 'admin']);
    }

    /**
     * Display a listing of the specification keys.
     */
    public function index(Request $request)
    {
        $keys = SpecificationKey::orderBy('name')
            ->paginate($request->input('per_page', 15));
        return response()->json($keys);
    }

    /**
     * Store a newly created specification key in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:specification_keys,name',
            'type' => 'sometimes|string|max:50', // e.g., string, number, boolean
            'unit' => 'nullable|string|max:50',   // e.g., cm, kg
        ]);

        $key = SpecificationKey::create($validatedData);
        return response()->json($key, 201);
    }

    /**
     * Display the specified specification key.
     */
    public function show(SpecificationKey $specificationKey) // Route model binding
    {
        return response()->json($specificationKey);
    }

    /**
     * Update the specified specification key in storage.
     */
    public function update(Request $request, SpecificationKey $specificationKey) // Route model binding
    {
        $validatedData = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('specification_keys')->ignore($specificationKey->id)],
            'type' => 'sometimes|string|max:50',
            'unit' => 'nullable|string|max:50',
        ]);

        $specificationKey->update($validatedData);
        return response()->json($specificationKey);
    }

    /**
     * Remove the specified specification key from storage.
     */
    public function destroy(SpecificationKey $specificationKey) // Route model binding
    {
        // Consider implications: what happens to existing ProductSpecificationValues using this key?
        // Option 1: Disallow deletion if in use (checked here or by DB foreign key constraint with RESTRICT)
        // Option 2: Cascade delete values (handled by DB foreign key constraint with CASCADE - current setup)
        // Option 3: Set key_id to null in values (if foreign key allows nullable and ON DELETE SET NULL)

        // For now, relying on DB cascade or manual cleanup if needed.
        // A check could be added:
        // if ($specificationKey->values()->exists()) {
        //     return response()->json(['message' => 'Cannot delete specification key as it is currently in use.'], 409);
        // }

        $specificationKey->delete();
        return response()->json(['message' => 'Specification key deleted successfully.'], 200);
    }
}
