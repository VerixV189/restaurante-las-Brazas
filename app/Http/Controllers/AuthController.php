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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    protected function isClientUser(User $user): bool
    {
        $user->loadMissing('role');

        return strtolower((string) ($user->role?->name ?? '')) === 'cliente';
    }

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
            /** @var User $user */
            $user = Auth::user();

            // Bloqueo estricto: el Portal de Clientes (/login) solo admite cuentas con rol Cliente
            if (! $this->isClientUser($user)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Esta cuenta pertenece al personal operativo. Por seguridad, el equipo debe iniciar sesión exclusivamente desde el Portal de Personal (/staff/login).',
                ]);
            }

            if (! $user->is_active && ! $user->hasVerifiedEmail()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

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

    public function showStaffLogin(): Response
    {
        return Inertia::render('Auth/StaffLogin');
    }

    public function staffLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            /** @var User $user */
            $user = Auth::user();

            // Bloqueo estricto: /staff/login solo admite roles internos (Administrador, Chef, Mesero, Recepcionista, Repartidor)
            if ($this->isClientUser($user)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Acceso exclusivo para personal autorizado. Si eres cliente, ingresa desde el Portal de Comensales (/login).',
                ]);
            }

            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Tu cuenta de personal se encuentra desactivada. Comunícate con el Administrador.',
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Las credenciales corporativas no coinciden con nuestros registros.',
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

    protected function mobileGoogleRedirectResponse(array $params, bool $isError = false, ?string $displayMessage = null)
    {
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $deepLinkUrl = 'lasbrazas://auth/callback?' . $query;
        $intentUrl = 'intent://auth/callback?' . $query . '#Intent;scheme=lasbrazas;package=com.example.restaurante_movil;end';

        $text = $displayMessage
            ?? ($isError
                ? (string) ($params['error'] ?? 'No se pudo completar el inicio de sesión con Google.')
                : 'Te estamos redirigiendo automáticamente a la aplicación móvil de Restaurante Las Brazas...');

        return response()
            ->view('auth.mobile-google-callback', [
                'deepLinkUrl'    => $deepLinkUrl,
                'intentUrl'      => $intentUrl,
                'isError'        => $isError,
                'displayMessage' => $text,
            ], 200)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function redirectToGoogle(Request $request)
    {
        $isMobile = strtolower((string) $request->query('source', '')) === 'mobile';

        // En el navegador web normal, si el usuario ya está autenticado, mantener redirección a /dashboard
        if (!$isMobile && Auth::check()) {
            return redirect('/dashboard');
        }

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            $missingMsg = 'El inicio de sesión con Google está listo para activarse. Configura GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en el archivo .env del servidor.';
            if ($isMobile) {
                return $this->mobileGoogleRedirectResponse(['error' => $missingMsg], true);
            }
            return redirect()->route('login')->with('error', $missingMsg);
        }

        $redirectUri = config('services.google.redirect');
        if (!str_starts_with((string) $redirectUri, 'http')) {
            $redirectUri = url($redirectUri ?: '/auth/google/callback');
        }

        $state = ($isMobile ? 'mobile_' : 'web_') . Str::random(32);
        $request->session()->put('google_oauth_state', $state);

        // Respaldo de un solo uso en caché para flujo móvil (evita fallo si Chrome en Android cambia cookies entre redirecciones)
        if ($isMobile) {
            Cache::put("google_oauth_mobile_state:{$state}", true, now()->addMinutes(10));
        }

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
        $returnedState = (string) $request->input('state', '');
        $expectedState = (string) $request->session()->pull('google_oauth_state', '');
        $isMobile = str_starts_with($returnedState, 'mobile_') || str_starts_with($expectedState, 'mobile_');

        if ($request->filled('error')) {
            if ($isMobile && $returnedState !== '') {
                Cache::forget("google_oauth_mobile_state:{$returnedState}");
            }
            $cancelMsg = 'Inicio de sesión con Google cancelado por el usuario.';
            if ($isMobile) {
                return $this->mobileGoogleRedirectResponse(['error' => $cancelMsg], true);
            }
            return redirect()->route('login')->with('error', $cancelMsg);
        }

        $stateValid = $expectedState !== '' && hash_equals($expectedState, $returnedState);
        if (!$stateValid && $isMobile && $returnedState !== '') {
            // Verificar respaldo atómico de un solo uso en caché si Chrome Android perdió la cookie de sesión
            $stateValid = (bool) Cache::pull("google_oauth_mobile_state:{$returnedState}");
        } elseif ($isMobile && $returnedState !== '') {
            Cache::forget("google_oauth_mobile_state:{$returnedState}");
        }

        if (!$stateValid || !$request->filled('code')) {
            $expiredMsg = 'La sesión de seguridad con Google expiró. Inténtalo nuevamente.';
            if ($isMobile) {
                return $this->mobileGoogleRedirectResponse(['error' => $expiredMsg], true);
            }
            return redirect()->route('login')->with('error', $expiredMsg);
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
                $tokenErr = 'No se pudo validar el token de acceso con Google.';
                if ($isMobile) {
                    return $this->mobileGoogleRedirectResponse(['error' => $tokenErr], true);
                }
                return redirect()->route('login')->with('error', $tokenErr);
            }

            $userInfoResponse = Http::withToken($tokenResponse->json('access_token'))
                ->timeout(15)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (!$userInfoResponse->successful() || empty($userInfoResponse->json('email'))) {
                $profileErr = 'No se pudo obtener la información de perfil desde Google.';
                if ($isMobile) {
                    return $this->mobileGoogleRedirectResponse(['error' => $profileErr], true);
                }
                return redirect()->route('login')->with('error', $profileErr);
            }

            $googleProfile = $userInfoResponse->json();
            $email = strtolower(trim($googleProfile['email']));
            $name = trim($googleProfile['name'] ?? ($googleProfile['given_name'] ?? explode('@', $email)[0]));
        } catch (\Throwable $e) {
            \Log::warning('Error autenticando con Google OAuth: ' . $e->getMessage());
            $connErr = 'Error de conexión al comunicarse con los servidores de Google.';
            if ($isMobile) {
                return $this->mobileGoogleRedirectResponse(['error' => $connErr], true);
            }
            return redirect()->route('login')->with('error', $connErr);
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
                    ['name' => $user->name, 'phone' => $user->phone]
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

            $welcomeMsg = "¡Bienvenido a Las Brazas, {$user->name}! Tu cuenta con Google ha sido creada y te enviamos a tu correo los Términos del Servicio, Condiciones de Reserva y Política de Privacidad.";

            if ($isMobile) {
                $user->loadMissing('role');
                $plainToken = $user->createToken('flutter_google_mobile')->plainTextToken;

                return $this->mobileGoogleRedirectResponse([
                    'token'   => $plainToken,
                    'id'      => (string) $user->id,
                    'name'    => $user->name,
                    'email'   => $user->email,
                    'role'    => $user->role?->name ?? 'Cliente',
                    'is_new'  => '1',
                    'message' => $welcomeMsg,
                ], false, $welcomeMsg);
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended('/dashboard')->with('success', $welcomeMsg);
        }

        // Bloqueo estricto: Google OAuth es exclusivo para el Portal de Clientes (/login y App Móvil)
        if (! $this->isClientUser($user)) {
            $staffErr = 'Tu correo pertenece al equipo operativo. Por seguridad, el personal debe iniciar sesión exclusivamente con su correo y contraseña.';
            if ($isMobile) {
                return $this->mobileGoogleRedirectResponse(['error' => $staffErr], true);
            }

            return redirect()->route('staff.login')->with(
                'error',
                'Tu correo pertenece al equipo operativo. Por seguridad, el personal debe iniciar sesión exclusivamente en el Portal de Personal con su correo y contraseña.'
            );
        }

        // Cliente ya existente
        if (!$user->is_active || !$user->email_verified_at) {
            $user->forceFill([
                'is_active'         => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        if ($isMobile) {
            $user->loadMissing('role');
            $plainToken = $user->createToken('flutter_google_mobile')->plainTextToken;
            $returningMsg = "¡Bienvenido de vuelta a Las Brazas, {$user->name}!";

            return $this->mobileGoogleRedirectResponse([
                'token'   => $plainToken,
                'id'      => (string) $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
                'role'    => $user->role?->name ?? 'Cliente',
                'is_new'  => '0',
                'message' => $returningMsg,
            ], false, $returningMsg);
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
