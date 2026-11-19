<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Table;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(\Illuminate\Http\Request $request): Response
    {
        if (ManualController::isManualHost($request)) {
            return app(ManualController::class)->index($request);
        }

        $categories = Category::where('is_active', true)->get();
        $dishes = Dish::with('category')->where('is_available', true)->get();
        $tables = Table::with('area')->where('status', 'available')->get();

        return Inertia::render('Landing', [
            'categories' => $categories,
            'dishes' => $dishes,
            'tables' => $tables,
        ]);
    }

    public function reserve(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'reservation_date' => 'required|date',
            'guest_count' => 'required|integer|min:1|max:20',
            'table_id' => 'nullable|exists:tables,id',
            'special_requests' => 'nullable|string',
        ]);

        $customer = Customer::firstOrCreate(
            ['phone' => $validated['customer_phone']],
            [
                'name' => $validated['customer_name'],
                'email' => $validated['customer_email'] ?? null,
            ]
        );

        Reservation::create([
            'customer_id' => $customer->id,
            'table_id' => $validated['table_id'] ?? null,
            'reservation_date' => $validated['reservation_date'],
            'guest_count' => $validated['guest_count'],
            'status' => 'pending',
            'special_requests' => $validated['special_requests'] ?? null,
        ]);

        return redirect()->back()->with('success', '¡Tu reserva ha sido enviada con éxito! Nos comunicaremos para confirmar.');
    }

    public function sendContact(Request $request)
    {
        // Honeypot anti-bots: si el campo oculto "website" viene lleno, ignoramos silenciosamente
        if ($request->filled('website')) {
            return redirect()->back()->with('success', '¡Gracias por escribirnos! Hemos recibido tu mensaje.');
        }

        $validated = $request->validate([
            'name'    => ['required', 'string', 'min:2', 'max:120'],
            'email'   => ['required', 'email', 'max:150'],
            'phone'   => ['required', 'string', 'min:6', 'max:40'],
            'subject' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'name.required'    => 'Por favor ingresa tu nombre completo.',
            'name.min'         => 'El nombre debe tener al menos 2 caracteres.',
            'email.required'   => 'Por favor ingresa tu correo electrónico.',
            'email.email'      => 'Ingresa una dirección de correo válida.',
            'phone.required'   => 'Por favor ingresa tu teléfono o WhatsApp de contacto.',
            'subject.required' => 'Selecciona el motivo de tu mensaje.',
            'message.required' => 'Por favor escribe tu mensaje o consulta.',
            'message.min'      => 'El mensaje debe contener al menos 10 caracteres.',
        ]);

        $contactData = [
            'name'    => trim($validated['name']),
            'email'   => strtolower(trim($validated['email'])),
            'phone'   => trim($validated['phone']),
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'sent_at' => now()->format('d/m/Y H:i:s'),
            'ip'      => (string) $request->ip(),
        ];

        $recipient = config('mail.contact_to') ?: config('mail.from.address') ?: 'administracion@lasbrazas.bo';

        try {
            Mail::to($recipient)->send(new ContactMessageMail($contactData));
        } catch (\Throwable $e) {
            \Log::error('Error enviando correo de formulario Contáctenos: ' . $e->getMessage());
            return redirect()->back()->withErrors([
                'message' => 'No pudimos enviar el mensaje en este momento. Por favor verifica tu conexión o contáctanos al teléfono del restaurante.',
            ]);
        }

        return redirect()->back()->with('success', '¡Mensaje enviado con éxito! Hemos recibido tu consulta en nuestro correo y te responderemos a la brevedad.');
    }
}
