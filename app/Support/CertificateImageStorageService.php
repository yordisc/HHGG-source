<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateImageStorageService
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB
    private const STORAGE_DISK = 'public';
    private const STORAGE_PATH = 'certificates/images';

    /**
     * Almacena una imagen de certificación de manera segura.
     *
     * @param  UploadedFile  $file Archivo a guardar
     * @return string Ruta relativa del archivo guardado
     *
     * @throws \InvalidArgumentException Si el archivo no es válido
     */
    public function store(UploadedFile $file): string
    {
        $this->validateFile($file);

        $filename = $this->generateSafeFilename($file);
        $path = Storage::disk(self::STORAGE_DISK)->putFileAs(
            self::STORAGE_PATH,
            $file,
            $filename,
            'public'
        );

        if (!$path) {
            throw new \RuntimeException('No se pudo guardar la imagen de certificación.');
        }

        return $path;
    }

    /**
     * Obtiene la URL pública de la imagen.
     *
     * @param  string|null  $path Ruta relativa del archivo
     * @return string|null URL pública o null
     */
    public function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return Storage::disk(self::STORAGE_DISK)->url($path);
    }

    /**
     * Elimina una imagen de certificación.
     *
     * @param  string|null  $path Ruta relativa del archivo
     * @return bool True si se eliminó exitosamente
     */
    public function delete(?string $path): bool
    {
        if (!$path) {
            return true;
        }

        if (!Storage::disk(self::STORAGE_DISK)->exists($path)) {
            return true;
        }

        return Storage::disk(self::STORAGE_DISK)->delete($path);
    }

    /**
     * Valida que el archivo cumpla los requisitos de seguridad.
     *
     * @param  UploadedFile  $file Archivo a validar
     * @throws \InvalidArgumentException Si la validación falla
     */
    private function validateFile(UploadedFile $file): void
    {
        // Validar MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException(
                'Tipo de archivo no permitido. Solo se aceptan: JPEG, PNG, WebP, GIF.'
            );
        }

        // Validar por extensión también
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array(mb_strtolower($file->getClientOriginalExtension()), $allowedExtensions, true)) {
            throw new \InvalidArgumentException(
                'Extensión de archivo no permitida.'
            );
        }

        // Validar tamaño
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(
                sprintf('El archivo excede el tamaño máximo de %d MB.', self::MAX_FILE_SIZE / (1024 * 1024))
            );
        }

        // Validar que es realmente una imagen
        if (!$this->isValidImage($file->getRealPath())) {
            throw new \InvalidArgumentException(
                'El archivo no es una imagen válida.'
            );
        }
    }

    /**
     * Verifica que el archivo sea una imagen válida.
     *
     * @param  string  $filePath Ruta al archivo
     * @return bool
     */
    private function isValidImage(string $filePath): bool
    {
        $imageInfo = @getimagesize($filePath);

        return $imageInfo !== false && is_array($imageInfo);
    }

    /**
     * Genera un nombre seguro para el archivo.
     *
     * @param  UploadedFile  $file Archivo original
     * @return string Nombre seguro del archivo
     */
    private function generateSafeFilename(UploadedFile $file): string
    {
        $hash = Str::random(16);
        $extension = mb_strtolower($file->getClientOriginalExtension());
        $timestamp = now()->format('YmdHis');

        return sprintf('%s_%s.%s', $timestamp, $hash, $extension);
    }

    /**
     * Retorna la ruta de almacenamiento.
     *
     * @return string
     */
    public static function storagePath(): string
    {
        return self::STORAGE_PATH;
    }

    /**
     * Retorna el disco de almacenamiento.
     *
     * @return string
     */
    public static function storageDisk(): string
    {
        return self::STORAGE_DISK;
    }
}
