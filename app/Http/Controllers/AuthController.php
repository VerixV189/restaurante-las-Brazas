<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Mail\TermsMail;
use App\Mail\ConditionsMail;
use App\Mail\PrivacyPolicyMail;
use App\Mail\VerifyAccountMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            if (! $user->is_active && ! $user->hasVerifiedEmail()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Tu cuenta aún no ha sido verificada. Revisa tu correo y haz clic en el enlace de verificación para activarla.',
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ]);
    }

    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users,email',
            'phone'                 => 'nullable|string|max:30',
            'password'              => 'required|string|min:8|confirmed',
            'terms'                 => 'accepted',
            'privacy'               => 'accepted',
        ], [
            'name.required'         => 'El nombre completo es obligatorio.',
            'email.required'        => 'El correo electrónico es obligatorio.',
            'email.email'           => 'Debe ingresar un correo electrónico válido.',
            'email.unique'          => 'Este correo electrónico ya se encuentra registrado.',
            'password.required'     => 'La contraseña es obligatoria.',
            'password.min'          => 'La contraseña debe contener al menos 8 caracteres.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
            'terms.accepted'        => 'Debe aceptar los términos y condiciones del sistema para continuar.',
            'privacy.accepted'      => 'Debe aceptar la política de privacidad para continuar.',
        ]);

        // Asegurar que exista el rol "Cliente" para registrar al usuario
        $role = Role::firstOrCreate(
            ['name' => 'Cliente'],
            ['description' => 'Cliente comensal registrado en el portal']
        );

        $user = User::create([
            'role_id'           => $role->id,
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'phone'             => $validated['phone'] ?? null,
            'password'          => Hash::make($validated['password']),
            'is_active'         => false,
            'email_verified_at' => null,
        ]);

        // Generar URL firmada de verificación de cuenta (válida por 24 horas)
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24),
            [
                'id'   => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        // Enviar los 4 correos requeridos:
        // 1. Términos del Servicio
        // 2. Condiciones y Políticas del Sitio
        // 3. Política de Privacidad y Protección de Datos
        // 4. Verificación de Cuenta
        try {
            Mail::to($user->email)->send(new TermsMail($user));
            Mail::to($user->email)->send(new ConditionsMail($user));
            Mail::to($user->email)->send(new PrivacyPolicyMail($user));
            Mail::to($user->email)->send(new VerifyAccountMail($user, $verificationUrl));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error enviando correos de registro: ' . $e->getMessage());
        }

        return redirect()->route('login')->with(
            'success',
            '¡Registro completado con éxito! Se han enviado 4 correos a ' . $user->email . ': Términos del Servicio, Condiciones del Sitio, Política de Privacidad y el enlace para Verificar tu Cuenta.'
        );
    }

    public function showTerms(): Response
    {
        return Inertia::render('Terms');
    }

    public function showPrivacy(): Response
    {
        return Inertia::render('Privacy');
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')->with('error', 'El enlace de verificación no es válido.');
        }

        if (! $request->hasValidSignature()) {
            return redirect()->route('login')->with('error', 'El enlace de verificación ha expirado. Por favor solicita uno nuevo.');
        }

        if ($user->hasVerifiedEmail()) {
            if (! $user->is_active) {
                $user->update(['is_active' => true]);
            }
            return redirect()->route('login')->with('success', 'Tu cuenta ya ha sido verificada previamente. Puedes iniciar sesión.');
        }

        $user->markEmailAsVerified();
        $user->update(['is_active' => true]);

        return redirect()->route('login')->with(
            'success',
            '¡Excelente! Tu correo electrónico ha sido verificado con éxito. Ya puedes iniciar sesión con tus credenciales.'
        );
    }

    public function redirectToGoogle(Request $request)
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('login')->with(
                'error',
                'El inicio de sesión con Google está listo para activarse. Configura GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en el archivo .env del servidor.'
            );
        }

        $redirectUri = config('services.google.redirect');
        if (!str_starts_with((string) $redirectUri, 'http')) {
            $redirectUri = url($redirectUri ?: '/auth/google/callback');
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'prompt'        => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function handleGoogleCallback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('login')->with('error', 'Inicio de sesión con Google cancelado por el usuario.');
        }

        $expectedState = $request->session()->pull('google_oauth_state');
        if (!$expectedState || $request->input('state') !== $expectedState || !$request->filled('code')) {
            return redirect()->route('login')->with('error', 'La sesión de seguridad con Google expiró. Inténtalo nuevamente.');
        }

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect');
        if (!str_starts_with((string) $redirectUri, 'http')) {
            $redirectUri = url($redirectUri ?: '/auth/google/callback');
        }

        try {
            $tokenResponse = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'code'          => $request->input('code'),
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
                'grant_type'    => 'authorization_code',
            ]);

            if (!$tokenResponse->successful() || empty($tokenResponse->json('access_token'))) {
                return redirect()->route('login')->with('error', 'No se pudo validar el token de acceso con Google.');
            }

            $userInfoResponse = Http::withToken($tokenResponse->json('access_token'))
                ->timeout(15)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (!$userInfoResponse->successful() || empty($userInfoResponse->json('email'))) {
                return redirect()->route('login')->with('error', 'No se pudo obtener la información de perfil desde Google.');
            }

            $googleProfile = $userInfoResponse->json();
            $email = strtolower(trim($googleProfile['email']));
            $name = trim($googleProfile['name'] ?? ($googleProfile['given_name'] ?? explode('@', $email)[0]));
        } catch (\Throwable $e) {
            \Log::warning('Error autenticando con Google OAuth: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Error de conexión al comunicarse con los servidores de Google.');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $clientRole = Role::firstOrCreate(
                ['name' => 'Cliente'],
                ['description' => 'Cliente externo para reservas y pedidos']
            );

            $user = User::create([
                'name'              => $name,
                'email'             => $email,
                'password'          => Hash::make(Str::random(32)),
                'role_id'           => $clientRole->id,
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            try {
                \App\Models\Customer::firstOrCreate(
                    ['email' => $user->email],
                    ['name' => $user->name, 'loyalty_points' => 0]
                );
            } catch (\Throwable $e) {
                \Log::warning('No se pudo sincronizar ficha de cliente en registro Google: ' . $e->getMessage());
            }

            // Enviar únicamente los 3 correos informativos (Términos, Condiciones y Política de Privacidad), sin correo de validación
            try {
                Mail::to($user->email)->send(new TermsMail($user));
                Mail::to($user->email)->send(new ConditionsMail($user));
                Mail::to($user->email)->send(new PrivacyPolicyMail($user));
            } catch (\Throwable $e) {
                \Log::warning('Error enviando correos informativos tras registro con Google: ' . $e->getMessage());
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended('/dashboard')->with(
                'success',
                "¡Bienvenido a Las Brazas, {$user->name}! Tu cuenta con Google ha sido creada y te enviamos a tu correo los Términos del Servicio, Condiciones de Reserva y Política de Privacidad."
            );
        }

        // Usuario ya existente (Administrador, Chef, Mesero, Recepcionista, Repartidor o Cliente)
        if (!$user->is_active || !$user->email_verified_at) {
            $user->forceFill([
                'is_active'         => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->withHeaders([
            'Clear-Site-Data' => '"cache"',
            'Cache-Control'   => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
