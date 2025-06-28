<?php

namespace Ijideals\SearchEngine\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchController extends Controller
{
    /**
     * Perform a global search across configured models.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        if (empty($query)) {
            return response()->json(['message' => 'Search query cannot be empty.', 'results' => []], 400);
        }

        $requestedTypes = $request->input('types');
        if (is_string($requestedTypes)) {
            $requestedTypes = array_map('trim', explode(',', $requestedTypes));
        }

        $searchableModels = config('search-engine.searchable_models', []);
        $results = [];
        $defaultPerPage = config('search-engine.pagination_items', 10);

        foreach ($searchableModels as $alias => $modelClass) {
            if (!empty($requestedTypes) && !in_array($alias, $requestedTypes)) {
                continue; // Skip if this model type was not requested
            }

            if (!class_exists($modelClass) || !method_exists($modelClass, 'search')) {
                // Log this misconfiguration
                logger()->warning("[SearchEngine] Model class {$modelClass} for alias '{$alias}' not found or not searchable.");
                continue;
            }

            // Perform search and paginate.
            // Note: Scout's paginate() method might behave differently per driver.
            // For 'database' driver, it usually works well.
            // For more complex result structures or if mixing multiple paginators becomes an issue,
            // one might fetch all results (e.g., ->get()) then manually paginate the combined collection.
            // However, for now, let's paginate per model type.

            $modelResults = $modelClass::search($query)
                ->paginate($request->input("per_page_{$alias}", $defaultPerPage), $alias . '_page');
                // Using model-specific page name like 'users_page', 'posts_page' to avoid conflicts in query string

            // Basic transformation: just get the items and pagination info.
            // A more robust solution would use API Resources for each model type.
            $results[$alias] = [
                'data' => $modelResults->items(),
                'pagination' => [
                    'total' => $modelResults->total(),
                    'per_page' => $modelResults->perPage(),
                    'current_page' => $modelResults->currentPage(),
                    'last_page' => $modelResults->lastPage(),
                    'from' => $modelResults->firstItem(),
                    'to' => $modelResults->lastItem(),
                    // 'next_page_url' => $modelResults->nextPageUrl(), // Be careful with these if you have multiple paginators on one client page
                    // 'prev_page_url' => $modelResults->previousPageUrl(),
                ],
            ];
        }

        if (empty($results)) {
             return response()->json(['query' => $query, 'message' => 'No results found or specified types are not searchable.', 'results' => []]);
        }

        return response()->json(['query' => $query, 'results' => $results]);
    }
}
