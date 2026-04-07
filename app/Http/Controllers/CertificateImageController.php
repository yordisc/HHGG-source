<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Support\CertificateImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateImageController extends Controller
{
    /**
     * Carga una imagen para el certificado.
     *
     * @param  Request  $request
     * @param  string  $serial Serial del certificado
     * @return JsonResponse
     */
    public function store(Request $request, string $serial): JsonResponse
    {
        $certificate = Certificate::where('serial', $serial)->firstOrFail();

        $validated = $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'max:5120', // 5 MB en kilobytes
                'mimes:jpeg,png,webp,gif',
            ],
        ], [
            'image.required' => 'Se requiere una imagen.',
            'image.file' => 'El archivo debe ser un archivo válido.',
            'image.image' => 'El archivo debe ser una imagen válida.',
            'image.max' => 'La imagen no debe exceder 5 MB.',
            'image.mimes' => 'La imagen debe ser JPEG, PNG, WebP o GIF.',
        ]);

        try {
            $storageService = new CertificateImageStorageService();

            // Eliminar imagen anterior si existe
            if ($certificate->certificate_image_path) {
                $storageService->delete($certificate->certificate_image_path);
            }

            // Guardar nueva imagen
            $imagePath = $storageService->store($validated['image']);
            $imageUrl = $storageService->url($imagePath);

            // Actualizar registro
            $certificate->update([
                'certificate_image_path' => $imagePath,
                'image_updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen cargada exitosamente.',
                'image_url' => $imageUrl,
                'image_path' => $imagePath,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la imagen. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Elimina la imagen del certificado.
     *
     * @param  string  $serial Serial del certificado
     * @return JsonResponse
     */
    public function delete(string $serial): JsonResponse
    {
        $certificate = Certificate::where('serial', $serial)->firstOrFail();

        if (!$certificate->certificate_image_path) {
            return response()->json([
                'success' => false,
                'message' => 'Este certificado no tiene imagen asignada.',
            ], 404);
        }

        try {
            $storageService = new CertificateImageStorageService();
            $storageService->delete($certificate->certificate_image_path);

            $certificate->update([
                'certificate_image_path' => null,
                'image_updated_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada exitosamente.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen.',
            ], 500);
        }
    }
}
