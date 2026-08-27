<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
  public function index(Request $request)
  {
    $source = Source::where(
      'api_token',
      $request->header('X-Aurora-Token')
    )
      ->where('is_active', true)
      ->first();

    if (!$source) {
      return response()->json([
        'error' => 'No autorizado o fuente inactiva',
      ], 401);
    }

    $services = $source->services()
      ->wherePivot('is_active', true)
      ->get()
      ->map(function ($service) {
        return [
          'id' => $service->id,
          'name' => $service->name,
          'description' => $service->pivot->description,
          'price' => $service->pivot->price,
        ];
      });

    return response()->json([
      'data' => $services,
    ]);
  }
}