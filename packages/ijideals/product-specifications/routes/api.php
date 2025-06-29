<?php

use Illuminate\Support\Facades\Route;
use IJIDeals\ProductSpecifications\Http\Controllers\Api\Admin\SpecificationKeyController;

Route::group(['prefix' => 'api/v1/admin', 'middleware' => ['api'/*, 'auth:sanctum', 'admin'*/]], function () {
    // TODO: Ensure 'auth:sanctum' and an 'admin' role/permission middleware are applied appropriately in a real app.
    // The 'api' middleware group from Laravel usually includes throttling.
    // The 'auth:sanctum' would protect against unauthenticated access.
    // An 'admin' middleware would check for admin privileges.

    Route::apiResource('specification-keys', SpecificationKeyController::class);
});
