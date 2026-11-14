<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Mail\TermsMail;
use App\Mail\ConditionsMail;
use App\Mail\VerifyAccountMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
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
        ], [
            'name.required'         => 'El nombre completo es obligatorio.',
            'email.required'        => 'El correo electrónico es obligatorio.',
            'email.email'           => 'Debe ingresar un correo electrónico válido.',
            'email.unique'          => 'Este correo electrónico ya se encuentra registrado.',
            'password.required'     => 'La contraseña es obligatoria.',
            'password.min'          => 'La contraseña debe contener al menos 8 caracteres.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
            'terms.accepted'        => 'Debe aceptar los términos y condiciones del sistema para continuar.',
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

        // Enviar los 3 correos requeridos:
        // 1. Términos del Servicio
        // 2. Condiciones y Políticas del Sitio
        // 3. Verificación de Cuenta
        try {
            Mail::to($user->email)->send(new TermsMail($user));
            Mail::to($user->email)->send(new ConditionsMail($user));
            Mail::to($user->email)->send(new VerifyAccountMail($user, $verificationUrl));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error enviando correos de registro: ' . $e->getMessage());
        }

        return redirect()->route('login')->with(
            'success',
            '¡Registro completado con éxito! Se han enviado 3 correos a ' . $user->email . ': Términos del Servicio, Condiciones del Sitio y el enlace para Verificar tu Cuenta.'
        );
    }

    public function showTerms(): Response
    {
        return Inertia::render('Terms');
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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
