<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Mail\SaasQuoteMail;
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

    public function corporate(): Response
    {
        return Inertia::render('CorporateLanding');
    }

    public function sendSaasQuote(Request $request)
    {
        // Honeypot anti-bots
        if ($request->filled('website')) {
            return redirect()->back()->with('success', '¡Solicitud de licencia SaaS recibida correctamente!');
        }

        $validated = $request->validate([
            'restaurant_name' => ['required', 'string', 'min:2', 'max:150'],
            'contact_name'    => ['required', 'string', 'min:2', 'max:120'],
            'contact_phone'   => ['required', 'string', 'min:6', 'max:40'],
            'contact_email'   => ['required', 'email', 'max:150'],
            'selected_plan'   => ['required', 'string', 'max:150'],
            'cloud_provider'  => ['nullable', 'string', 'max:150'],
            'branches_count'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'message'         => ['nullable', 'string', 'max:2000'],
        ], [
            'restaurant_name.required' => 'Ingresa el nombre del restaurante o empresa.',
            'contact_name.required'    => 'Ingresa el nombre de la persona de contacto.',
            'contact_phone.required'   => 'Ingresa un número de teléfono o WhatsApp.',
            'contact_email.required'   => 'Ingresa tu correo corporativo para enviarte la cotización.',
            'contact_email.email'      => 'Ingresa un correo electrónico válido.',
            'selected_plan.required'   => 'Selecciona un plan de licencia SaaS.',
        ]);

        $quoteData = [
            'restaurant_name' => trim($validated['restaurant_name']),
            'contact_name'    => trim($validated['contact_name']),
            'contact_phone'   => trim($validated['contact_phone']),
            'contact_email'   => strtolower(trim($validated['contact_email'])),
            'selected_plan'   => trim($validated['selected_plan']),
            'cloud_provider'  => trim((string) ($validated['cloud_provider'] ?? 'Nube Administrada Alta Disponibilidad')),
            'branches_count'  => (int) ($validated['branches_count'] ?? 1),
            'message'         => trim((string) ($validated['message'] ?? '')),
            'sent_at'         => now()->format('d/m/Y H:i:s'),
        ];

        $recipient = config('mail.contact_to') ?: config('mail.from.address') ?: 'administracion@lasbrazas.bo';

        try {
            Mail::to($recipient)->send(new SaasQuoteMail($quoteData));
        } catch (\Throwable $e) {
            \Log::error('Error enviando correo de cotización SaaS: ' . $e->getMessage());
            return redirect()->back()->withErrors([
                'message' => 'No pudimos enviar tu solicitud de cotización en este momento. Intenta nuevamente en unos minutos.',
            ]);
        }

        return redirect()->back()->with(
            'success',
            "¡Solicitud de cotización enviada! BrazasTech S.R.L. recibió el pedido de \"{$quoteData['restaurant_name']}\" para el {$quoteData['selected_plan']} y te contactaremos pronto."
        );
    }
}
