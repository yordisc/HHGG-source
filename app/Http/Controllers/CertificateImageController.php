<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Support\CertificateImageStorageService;
use App\Support\RemoteImageUrlValidator;
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
            'image_url' => [
                'nullable',
                'url',
                'max:2048',
                'required_without:image',
            ],
            'image' => [
                'nullable',
                'file',
                'image',
                'max:5120', // 5 MB en kilobytes
                'mimes:jpeg,png,webp,gif',
                'required_without:image_url',
            ],
        ], [
            'image_url.required_without' => 'Debes enviar una URL de imagen o un archivo.',
            'image_url.url' => 'La URL de imagen no es valida.',
            'image.required_without' => 'Debes enviar una URL de imagen o un archivo.',
            'image.file' => 'El archivo debe ser un archivo válido.',
            'image.image' => 'El archivo debe ser una imagen válida.',
            'image.max' => 'La imagen no debe exceder 5 MB.',
            'image.mimes' => 'La imagen debe ser JPEG, PNG, WebP o GIF.',
        ]);

        try {
            if (!empty($validated['image_url'])) {
                $imagePath = app(RemoteImageUrlValidator::class)->validate((string) $validated['image_url']);
                $imageUrl = $imagePath;
            } else {
                $storageService = new CertificateImageStorageService();

                if ($certificate->certificate_image_path && !filter_var($certificate->certificate_image_path, FILTER_VALIDATE_URL)) {
                    $storageService->delete($certificate->certificate_image_path);
                }

                $imagePath = $storageService->store($validated['image']);
                $imageUrl = $storageService->url($imagePath);
            }

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
            if (!filter_var($certificate->certificate_image_path, FILTER_VALIDATE_URL)) {
                $storageService = new CertificateImageStorageService();
                $storageService->delete($certificate->certificate_image_path);
            }

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
