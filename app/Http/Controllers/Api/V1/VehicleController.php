<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\VehicleFilterData;
use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Services\Catalog\VehicleCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleController extends Controller
{
    public function index(Request $request, VehicleCatalogService $catalog): AnonymousResourceCollection
    {
        $filters = VehicleFilterData::fromRequest($request);

        return VehicleResource::collection($catalog->listing($filters));
    }

    public function show(string $uuid, VehicleCatalogService $catalog): VehicleResource|JsonResponse
    {
        $vehicle = $catalog->findPublishedByUuid($uuid);

        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not found.'], 404);
        }

        return new VehicleResource($vehicle);
    }
}
