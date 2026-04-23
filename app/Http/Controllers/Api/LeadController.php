<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Interaction;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    public function receive(Request $request)
    {
        // 1. VALIDACIÓN DE SEGURIDAD (API TOKEN)
        $source = Source::where('api_token', $request->header('X-Aurora-Token'))
            ->where('is_active', true)
            ->first();

        if (!$source) {
            return response()->json(['error' => 'No autorizado o fuente inactiva'], 401);
        }

        // 2. VALIDACIÓN DE DATOS REQUERIDOS
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'first_name' => 'required|string',
            'service_id' => 'exists:services,id' // Opcional pero recomendado
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 3. LÓGICA DE NO DUPLICIDAD (updateOrCreate)
        // Si el email existe, actualiza nombre y teléfono. Si no, lo crea.
        $customer = Customer::updateOrCreate(
            ['email' => $request->email], // Criterio de búsqueda
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'metadata' => $request->metadata, // Guardamos datos extra como JSON
            ]
        );

        // 4. REGISTRAR LA INTERACCIÓN
        $interaction = Interaction::create([
            'customer_id' => $customer->id,
            'source_id' => $source->id,
            'service_id' => $request->service_id,
            'status' => 'nuevo',
            'notes' => 'Lead recibido vía API desde ' . $source->name,
        ]);

        return response()->json([
            'message' => 'Lead procesado correctamente',
            'interaction_id' => $interaction->id
        ], 201);
    }
}