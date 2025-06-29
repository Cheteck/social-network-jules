<?php

namespace Ijideals\CatalogManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth; // For future permission checks
use Ijideals\CatalogManager\Models\ProductOption;
use Ijideals\CatalogManager\Models\ProductOptionValue;
use Illuminate\Validation\Rule;

class ProductOptionController extends Controller
{
    protected $optionModelClass;
    protected $valueModelClass;

    public function __construct()
    {
        // For MVP, assume platform admin access is checked elsewhere or via global middleware
        // $this->middleware('auth:api');
        // $this->middleware('can:manage_product_options'); // Example permission

        $this->optionModelClass = config('catalog-manager.product_option_model', ProductOption::class);
        $this->valueModelClass = config('catalog-manager.product_option_value_model', ProductOptionValue::class);
    }

    /**
     * Display a listing of product options.
     */
    public function index(Request $request)
    {
        // TODO: Add pagination if list becomes very long
        $options = $this->optionModelClass::with('values')->orderBy('name')->get();
        return response()->json($options);
    }

    /**
     * Store a newly created product option.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:'.app($this->optionModelClass)->getTable().',name',
            'display_type' => 'sometimes|string|in:dropdown,radio,color_swatch,text_input', // Example types
        ]);

        $option = $this->optionModelClass::create($validated);
        return response()->json($option, 201);
    }

    /**
     * Display the specified product option.
     */
    public function show(int $optionId)
    {
        $option = $this->optionModelClass::with('values')->find($optionId);
        if (!$option) {
            return response()->json(['message' => 'Product option not found.'], 404);
        }
        return response()->json($option);
    }

    /**
     * Update the specified product option.
     */
    public function update(Request $request, int $optionId)
    {
        $option = $this->optionModelClass::find($optionId);
        if (!$option) {
            return response()->json(['message' => 'Product option not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:'.$option->getTable().',name,'.$option->id,
            'display_type' => 'sometimes|string|in:dropdown,radio,color_swatch,text_input',
        ]);

        $option->update($validated);
        return response()->json($option->load('values'));
    }

    /**
     * Remove the specified product option.
     */
    public function destroy(int $optionId)
    {
        $option = $this->optionModelClass::find($optionId);
        if (!$option) {
            return response()->json(['message' => 'Product option not found.'], 404);
        }
        // Deleting an option will also delete its values due to onDelete('cascade') in migration
        $option->delete();
        return response()->json(['message' => 'Product option deleted successfully.']);
    }

    // --- Product Option Values ---

    /**
     * List values for a specific product option.
     */
    public function indexValues(int $optionId)
    {
        $option = $this->optionModelClass::find($optionId);
        if (!$option) {
            return response()->json(['message' => 'Product option not found.'], 404);
        }
        return response()->json($option->values()->orderBy('order_column')->get());
    }

    /**
     * Store a new value for a specific product option.
     */
    public function storeValue(Request $request, int $optionId)
    {
        $option = $this->optionModelClass::find($optionId);
        if (!$option) {
            return response()->json(['message' => 'Product option not found.'], 404);
        }

        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255',
                        Rule::unique(app($this->valueModelClass)->getTable(), 'value')->where('product_option_id', $optionId)],
            'display_label' => 'nullable|string|max:255',
            'order_column' => 'sometimes|integer',
        ]);

        $value = $option->values()->create($validated);
        return response()->json($value, 201);
    }

    /**
     * Update a specific product option value.
     */
    public function updateValue(Request $request, int $optionId, int $valueId)
    {
        $option = $this->optionModelClass::find($optionId);
        if (!$option) {
            return response()->json(['message' => 'Product option not found.'], 404);
        }
        $value = $option->values()->find($valueId);
        if (!$value) {
            return response()->json(['message' => 'Product option value not found.'], 404);
        }

        $validated = $request->validate([
            'value' => ['sometimes','required', 'string', 'max:255',
                        Rule::unique($value->getTable(), 'value')->where('product_option_id', $optionId)->ignore($value->id)],
            'display_label' => 'nullable|string|max:255',
            'order_column' => 'sometimes|integer',
        ]);

        $value->update($validated);
        return response()->json($value);
    }

    /**
     * Delete a specific product option value.
     */
    public function destroyValue(int $optionId, int $valueId)
    {
        $option = $this->optionModelClass::find($optionId);
        if (!$option) {
            return response()->json(['message' => 'Product option not found.'], 404);
        }
        $value = $option->values()->find($valueId);
        if (!$value) {
            return response()->json(['message' => 'Product option value not found.'], 404);
        }

        // TODO: Consider implications: if this value is used by variants, what happens?
        // For now, simple delete. Might need to prevent deletion if in use.
        $value->delete();
        return response()->json(['message' => 'Product option value deleted successfully.']);
    }
}
