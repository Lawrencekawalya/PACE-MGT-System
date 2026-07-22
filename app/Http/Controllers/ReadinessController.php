<?php

namespace App\Http\Controllers;

use App\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;

class ReadinessController extends Controller
{
    public function __invoke(SystemHealthService $health): JsonResponse
    {
        $result = $health->infrastructure();
        $ready = $health->isReady();

        return response()->json([
            'status' => $ready ? 'ready' : 'unavailable',
            'checked_at' => $result['checked_at'],
        ], $ready ? 200 : 503);
    }
}
