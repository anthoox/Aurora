<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\Source;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function store(Request $request)
    {
        $source = Source::where('api_token', $request->header('X-Aurora-Token'))
            ->where('is_active', true)
            ->firstOrFail();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $customer = Customer::firstOrCreate(
            ['email' => $data['email']],
            [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]
        );

        $contactMessage = ContactMessage::create([
            'customer_id' => $customer->id,
            'source_id' => $source->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'],
            'status' => 'nuevo',
        ]);

        return response()->json([
            'message' => 'Mensaje de contacto recibido correctamente',
            'contact_message_id' => $contactMessage->id,
        ], 201);
    }
}