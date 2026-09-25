<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class FloorPlanController extends Controller
{
    public function index(): Response
    {
        $areas = Area::with(['tables' => function ($query) {
            $query->orderBy('table_number');
        }])->get();

        return Inertia::render('FloorPlans/Index', [
            'areas' => $areas,
        ]);
    }

    /**
     * Procesa la imagen del plano enviándola al microservicio YOLO de Organización
     * o genera una detección inteligente simulada si el servicio no está encendido.
     */
    public function detect(Request $request): JsonResponse
    {
        $request->validate([
            'plano_imagen' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'ancho_m' => 'required|numeric|min:1|max:100',
            'alto_m' => 'required|numeric|min:1|max:100',
            'mesa_min_m' => 'nullable|numeric|min:0.1',
            'mesa_max_m' => 'nullable|numeric|min:0.5',
            'silla_min_m' => 'nullable|numeric|min:0.1',
            'silla_max_m' => 'nullable|numeric|min:0.3',
        ]);

        $image = $request->file('plano_imagen');
        $anchoM = (float) $request->input('ancho_m', 10.0);
        $altoM = (float) $request->input('alto_m', 8.0);
        $mesaMin = (float) $request->input('mesa_min_m', 0.5);
        $mesaMax = (float) $request->input('mesa_max_m', 3.0);
        $sillaMin = (float) $request->input('silla_min_m', 0.3);
        $sillaMax = (float) $request->input('silla_max_m', 0.7);

        // Convertir imagen a Base64 para visualización directa en SVG / Canvas
        $imageRaw = file_get_contents($image->getRealPath());
        $imageBase64 = 'data:' . $image->getMimeType() . ';base64,' . base64_encode($imageRaw);
        [$imgWidthPx, $imgHeightPx] = getimagesize($image->getRealPath()) ?: [800, 600];

        $configPayload = [
            'local_ancho_m' => $anchoM,
            'local_alto_m' => $altoM,
            'filtros_m' => [
                'MESA' => [
                    'min_lado' => $mesaMin,
                    'max_lado' => $mesaMax,
                ],
                'SILLA' => [
                    'min_lado' => $sillaMin,
                    'max_lado' => $sillaMax,
                ],
            ],
        ];

        $yoloUrl = config('services.organizacion_yolo.url', 'http://127.0.0.1:5001');
        $detectionResult = null;
        $engine = 'yolo_v8';

        // 1. Intentar llamar al microservicio Flask con YOLO (best.pt)
        try {
            $response = Http::timeout(20)
                ->attach('plano_imagen', $imageRaw, $image->getClientOriginalName())
                ->post("{$yoloUrl}/api/layout/detect", [
                    'config' => json_encode($configPayload),
                ]);

            if ($response->successful()) {
                $detectionResult = $response->json();
            }
        } catch (\Throwable $e) {
            Log::info("YOLO FloorPlan microservice unreachable, using smart geometric layout generator: " . $e->getMessage());
        }

        // 2. Fallback de detección geométrica si el microservicio está apagado
        if (!$detectionResult) {
            $detectionResult = $this->generateSimulatedYoloDetection($imgWidthPx, $imgHeightPx, $anchoM, $altoM);
            $engine = 'simulated_geometric';
        }

        // Asegurar que la imagen base64 esté disponible para el visor frontend
        $detectionResult['image_preview'] = $imageBase64;
        $detectionResult['engine'] = $engine;

        return response()->json([
            'success' => true,
            'layout' => $detectionResult,
        ]);
    }

    /**
     * Optimiza la distribución espacial de mesas y sillas.
     */
    public function optimize(Request $request): JsonResponse
    {
        $layoutData = $request->input('layout');
        $yoloUrl = config('services.organizacion_yolo.url', 'http://127.0.0.1:5001');

        try {
            $response = Http::timeout(20)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$yoloUrl}/api/layout/optimize", [
                    'layout' => $layoutData,
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'optimized_layout' => $response->json(),
                    'engine' => 'yolo_optimizer',
                ]);
            }
        } catch (\Throwable $e) {
            Log::info("YOLO optimizer service offline, applying local layout alignment: " . $e->getMessage());
        }

        // Fallback: Alineación y re-empaquetamiento geométrico
        $optimized = $this->alignLayoutObjects($layoutData);

        return response()->json([
            'success' => true,
            'optimized_layout' => $optimized,
            'engine' => 'local_aligner',
        ]);
    }

    /**
     * Sincroniza y guarda las mesas detectadas en la base de datos de una Zona específica.
     */
    public function syncTables(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'tables' => 'required|array|min:1',
            'tables.*.table_number' => 'required|string|max:50',
            'tables.*.capacity' => 'required|integer|min:1|max:50',
        ]);

        $area = Area::findOrFail($validated['area_id']);
        $createdCount = 0;
        $updatedCount = 0;

        DB::transaction(function () use ($validated, $area, &$createdCount, &$updatedCount) {
            foreach ($validated['tables'] as $tableData) {
                $table = Table::where('area_id', $area->id)
                    ->where('table_number', $tableData['table_number'])
                    ->first();

                if ($table) {
                    $table->update([
                        'capacity' => $tableData['capacity'],
                        'status' => 'available',
                    ]);
                    $updatedCount++;
                } else {
                    Table::create([
                        'area_id' => $area->id,
                        'table_number' => $tableData['table_number'],
                        'capacity' => $tableData['capacity'],
                        'status' => 'available',
                    ]);
                    $createdCount++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Sincronización exitosa en '{$area->name}': {$createdCount} mesas creadas y {$updatedCount} actualizadas.",
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * Generador geométrico inteligente para planos (Fallback si YOLO no está activo)
     */
    private function generateSimulatedYoloDetection(int $widthPx, int $heightPx, float $widthM, float $heightM): array
    {
        $scale = $widthPx / max($widthM, 1);
        $marginPx = 40;

        // Polígono del perímetro del salón
        $perimeterPoints = [
            [$marginPx, $marginPx],
            [$widthPx - $marginPx, $marginPx],
            [$widthPx - $marginPx, $heightPx - $marginPx],
            [$marginPx, $heightPx - $marginPx],
        ];

        // Generar grilla armónica de mesas y sillas
        $objects = [];
        $cols = 3;
        $rows = 2;
        $cellWidth = ($widthPx - ($marginPx * 2)) / $cols;
        $cellHeight = ($heightPx - ($marginPx * 2)) / $rows;

        $tableIndex = 1;
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                $cx = $marginPx + ($c * $cellWidth) + ($cellWidth / 2);
                $cy = $marginPx + ($r * $cellHeight) + ($cellHeight / 2);
                $tWidth = min(90, $cellWidth * 0.55);
                $tHeight = min(65, $cellHeight * 0.55);

                $mesaId = "mesa_{$tableIndex}";
                $capacity = ($tableIndex % 2 === 0) ? 6 : 4;

                // Generar sillas alrededor de la mesa
                $sillas = [];
                if ($capacity === 4) {
                    $sillas = [
                        ['x' => $cx - ($tWidth / 2) - 15, 'y' => $cy, 'radius' => 9],
                        ['x' => $cx + ($tWidth / 2) + 15, 'y' => $cy, 'radius' => 9],
                        ['x' => $cx, 'y' => $cy - ($tHeight / 2) - 15, 'radius' => 9],
                        ['x' => $cx, 'y' => $cy + ($tHeight / 2) + 15, 'radius' => 9],
                    ];
                } else {
                    $sillas = [
                        ['x' => $cx - ($tWidth / 3), 'y' => $cy - ($tHeight / 2) - 15, 'radius' => 9],
                        ['x' => $cx + ($tWidth / 3), 'y' => $cy - ($tHeight / 2) - 15, 'radius' => 9],
                        ['x' => $cx - ($tWidth / 3), 'y' => $cy + ($tHeight / 2) + 15, 'radius' => 9],
                        ['x' => $cx + ($tWidth / 3), 'y' => $cy + ($tHeight / 2) + 15, 'radius' => 9],
                        ['x' => $cx - ($tWidth / 2) - 15, 'y' => $cy, 'radius' => 9],
                        ['x' => $cx + ($tWidth / 2) + 15, 'y' => $cy, 'radius' => 9],
                    ];
                }

                $objects[] = [
                    'id' => $mesaId,
                    'label' => "Mesa {$tableIndex}",
                    'type' => 'mesa',
                    'x' => round($cx - ($tWidth / 2)),
                    'y' => round($cy - ($tHeight / 2)),
                    'width' => round($tWidth),
                    'height' => round($tHeight),
                    'capacity' => $capacity,
                    'sillas' => $sillas,
                ];

                $tableIndex++;
            }
        }

        return [
            'dimensions' => [
                'width_px' => $widthPx,
                'height_px' => $heightPx,
                'width_m' => $widthM,
                'height_m' => $heightM,
            ],
            'perimeter' => [
                'points' => $perimeterPoints,
            ],
            'objects' => $objects,
            'm_to_px' => $scale,
            'total_tables' => count($objects),
            'total_capacity' => array_sum(array_column($objects, 'capacity')),
        ];
    }

    /**
     * Re-empaquetamiento local de objetos
     */
    private function alignLayoutObjects(array $layout): array
    {
        $objects = $layout['objects'] ?? [];
        foreach ($objects as &$obj) {
            // Alinear a grilla de 10px
            $obj['x'] = round($obj['x'] / 10) * 10;
            $obj['y'] = round($obj['y'] / 10) * 10;
        }
        $layout['objects'] = $objects;
        return $layout;
    }
}
