<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManualController extends Controller
{
    /**
     * Determina si la petición HTTP entrante corresponde al subdominio del manual.
     */
    public static function isManualHost(Request $request): bool
    {
        $host = strtolower($request->getHost());
        $configuredDomain = config('manual.domain');

        if (is_string($configuredDomain) && $configuredDomain !== '' && $host === strtolower($configuredDomain)) {
            return true;
        }

        return str_starts_with($host, 'manual.');
    }

    /**
     * Renderiza el Manual de Usuario Web Interactivo (Inertia Manual/Index).
     */
    public function index(Request $request): Response
    {
        $isSubdomain = self::isManualHost($request);
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();
        $portSuffix = ($port && !in_array($port, [80, 443], true)) ? ':' . $port : '';

        if ($isSubdomain) {
            $baseHost = preg_replace('/^manual\./i', '', $host);
            $mainSystemUrl = "{$scheme}://{$baseHost}{$portSuffix}";
            $manualUrl = "{$scheme}://{$host}{$portSuffix}";
            $pdfUrl = '/pdf';
        } else {
            $configuredManualDomain = config('manual.domain');
            $cleanHost = preg_replace('/^www\./i', '', $host);
            $manualHost = (is_string($configuredManualDomain) && $configuredManualDomain !== '')
                ? $configuredManualDomain
                : "manual.{$cleanHost}";

            $mainSystemUrl = '/';
            $manualUrl = "{$scheme}://{$manualHost}{$portSuffix}";
            $pdfUrl = '/manual/pdf';
        }

        $pdfFilename = (string) config('manual.pdf_filename', 'Manual_Usuario_Completo_Las_Brazas.pdf');
        $pdfPath = public_path('manual/' . $pdfFilename);
        $pdfExists = file_exists($pdfPath);
        $pdfSizeKb = $pdfExists ? (int) ceil(filesize($pdfPath) / 1024) : 0;

        return Inertia::render('Manual/Index', [
            'isSubdomain' => $isSubdomain,
            'mainSystemUrl' => $mainSystemUrl,
            'manualSubdomainUrl' => $manualUrl,
            'pdfUrl' => $pdfUrl,
            'pdfDownloadUrl' => $pdfUrl . '?download=1',
            'pdfAvailable' => $pdfExists,
            'pdfSizeKb' => $pdfSizeKb,
            'pdfFilename' => $pdfFilename,
            'appVersion' => (string) config('manual.version', '2.4.0'),
        ]);
    }

    /**
     * Sirve o descarga el Manual de Usuario Completo en formato PDF.
     */
    public function downloadPdf(Request $request): BinaryFileResponse
    {
        $pdfFilename = (string) config('manual.pdf_filename', 'Manual_Usuario_Completo_Las_Brazas.pdf');
        $pdfPath = public_path('manual/' . $pdfFilename);

        if (!file_exists($pdfPath)) {
            abort(404, 'El archivo PDF del manual de usuario aún no se encuentra disponible.');
        }

        $headers = [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'public, max-age=3600',
        ];

        if ($request->boolean('download')) {
            return response()->download($pdfPath, $pdfFilename, $headers);
        }

        return response()->file($pdfPath, array_merge($headers, [
            'Content-Disposition' => 'inline; filename="' . $pdfFilename . '"',
        ]));
    }
}
