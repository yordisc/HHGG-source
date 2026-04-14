<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class RemoteImageUrlValidator
{
    public function validate(string $url): string
    {
        $normalizedUrl = trim($url);

        if (!filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Debes ingresar una URL valida.');
        }

        $scheme = strtolower((string) parse_url($normalizedUrl, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('La URL debe usar http o https.');
        }

        $head = Http::timeout(8)->head($normalizedUrl);
        if ($head->successful() && $this->isImageContentType($head->header('Content-Type'))) {
            return $normalizedUrl;
        }

        $get = Http::timeout(8)
            ->withHeaders(['Range' => 'bytes=0-1024'])
            ->get($normalizedUrl);

        if (!$get->successful()) {
            throw new \InvalidArgumentException('No se pudo acceder a la URL de imagen.');
        }

        if (!$this->isImageContentType($get->header('Content-Type'))) {
            throw new \InvalidArgumentException('La URL no apunta a una imagen valida.');
        }

        return $normalizedUrl;
    }

    private function isImageContentType(?string $contentType): bool
    {
        if (!$contentType) {
            return false;
        }

        return str_starts_with(strtolower(trim($contentType)), 'image/');
    }
}
