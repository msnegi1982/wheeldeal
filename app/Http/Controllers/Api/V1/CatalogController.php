<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\VehicleResource;
use App\Services\Catalog\VehicleCatalogService;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function home(VehicleCatalogService $catalog): JsonResponse
    {
        $data = $catalog->home();

        return response()->json([
            'data' => [
                'featured' => VehicleResource::collection($data['featured'])->resolve(),
                'latest' => VehicleResource::collection($data['latest'])->resolve(),
                'popular' => VehicleResource::collection($data['popular'])->resolve(),
                'categories' => CategoryResource::collection($data['categories'])->resolve(),
                'brands' => BrandResource::collection($data['brands'])->resolve(),
                'stats' => $data['stats'],
            ],
        ]);
    }

    public function filters(VehicleCatalogService $catalog): JsonResponse
    {
        return response()->json([
            'data' => $catalog->filterOptions(),
        ]);
    }
}
