<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Interaction;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Filament\Actions\Action;

class LeadController extends Controller
{
    public function receive(Request $request)
    {
        // 1. VALIDACIÓN DE SEGURIDAD (API TOKEN)
        $source = Source::where('api_token', $request->header('X-Aurora-Token'))
            ->where('is_active', true)
            ->first();

        if (!$source) {
            $admin = User::find(1);
            if ($admin) {
                $notifData = Notification::make()
                    ->title('Alerta de Seguridad: API')
                    ->body('Intento de acceso con token inválido desde: ' . $request->ip())
                    ->danger()
                    ->icon('heroicon-o-shield-exclamation')
                    ->actions([
                        Action::make('check_sources')
                            ->label('Revisar Fuentes')
                            ->url('/admin/sources') // Te lleva al listado general
                            ->color('danger'),
                    ])
                    ->getDatabaseMessage();

                $admin->notifications()->create([
                    'id' => Str::uuid()->toString(),
                    'type' => 'Filament\Notifications\DatabaseNotification',
                    'data' => $notifData['data'] ?? $notifData,
                    'read_at' => null,
                ]);
            }
            return response()->json(['error' => 'No autorizado o fuente inactiva'], 401);
        }

        // 2. VALIDACIÓN DE DATOS REQUERIDOS
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'first_name' => 'required|string',
            'service_id' => 'nullable|exists:services,id',
            'message' => 'nullable|string',
            'phone' => 'nullable|string',
            'last_name' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {

            $admin = User::find(1);
            if ($admin) {
                $notifData = Notification::make()
                    ->title('Error de Validación en Lead')
                    ->body("La fuente '{$source->name}' envió datos incompletos.")
                    ->warning()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->actions([
                        Action::make('fix_source')
                            ->label('Ver Fuente')
                            ->url("/admin/sources/{$source->id}/edit") // Te lleva directo a la fuente culpable
                            ->color('warning'),
                    ])
                    ->getDatabaseMessage();

                $admin->notifications()->create([
                    'id' => Str::uuid()->toString(),
                    'type' => 'Filament\Notifications\DatabaseNotification',
                    'data' => $notifData['data'] ?? $notifData,
                    'read_at' => null,
                ]);
            }

            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 3. LÓGICA DE NO DUPLICIDAD (updateOrCreate)
        // Si el email existe, actualiza nombre y teléfono. Si no, lo crea.
        $customer = Customer::query()
            ->where('email', $request->email)
            ->when($request->filled('phone'), function ($query) use ($request) {
                $query->orWhere('phone', $request->phone);
            })
            ->first();

        $isRecurrentCustomer = (bool) $customer;

        if ($customer) {
            $customer->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'metadata' => $request->metadata,
            ]);
        } else {
            $customer = Customer::create([
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'metadata' => $request->metadata,
            ]);
        }

        // 4. REGISTRAR LA INTERACCIÓN
        $interaction = Interaction::create([
            'customer_id' => $customer->id,
            'source_id' => $source->id,
            'service_id' => $request->service_id,
            'status' => 'nuevo',
            'message' => $request->message,
            'notes' => 'Lead recibido vía API desde ' . $source->name,
        ]);

        return response()->json([
            'message' => 'Lead procesado correctamente',
            'interaction_id' => $interaction->id,
            'is_recurrent_customer' => $isRecurrentCustomer,
            'previous_interactions_count' => $customer->interactions()
                ->where('id', '!=', $interaction->id)
                ->count(),
        ], 201);
    }
}