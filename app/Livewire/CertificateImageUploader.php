<?php

namespace App\Livewire;

use App\Models\Certificate;
use App\Support\RemoteImageUrlValidator;
use Livewire\Component;

class CertificateImageUploader extends Component
{
    public ?Certificate $certificate = null;
    public string $imageUrlInput = '';
    public $imageUrl = null;
    public $isLoading = false;
    public $message = null;
    public $isError = false;

    public function mount(string $serial): void
    {
        $this->certificate = Certificate::where('serial', $serial)->firstOrFail();
        $this->imageUrl = $this->certificate->getImageUrl();
        $this->imageUrlInput = (string) ($this->imageUrl ?? '');
    }

    public function saveImageUrl(): void
    {
        if (!$this->certificate) {
            return;
        }

        try {
            $this->validate([
                'imageUrlInput' => 'required|url|max:2048',
            ], [
                'imageUrlInput.required' => 'Se requiere una URL.',
                'imageUrlInput.url' => 'Debes ingresar una URL valida.',
                'imageUrlInput.max' => 'La URL no debe exceder 2048 caracteres.',
            ]);

            $this->isLoading = true;
            $normalizedUrl = app(RemoteImageUrlValidator::class)->validate($this->imageUrlInput);

            $this->certificate->update([
                'certificate_image_path' => $normalizedUrl,
                'image_updated_at' => now(),
            ]);

            $this->imageUrl = $normalizedUrl;
            $this->imageUrlInput = $normalizedUrl;
            $this->isError = false;
            $this->message = '✓ Imagen por URL guardada exitosamente';

            $this->dispatch('certificate-image-uploaded');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->isError = true;
            $this->message = $e->errors()['imageUrlInput'][0] ?? 'Error de validacion';
        } catch (\InvalidArgumentException $e) {
            $this->isError = true;
            $this->message = $e->getMessage();
        } catch (\Exception $e) {
            $this->isError = true;
            $this->message = 'Error al validar o guardar la URL de imagen.';
        } finally {
            $this->isLoading = false;
        }
    }

    public function deleteImage(): void
    {
        if (!$this->certificate || !$this->certificate->certificate_image_path) {
            return;
        }

        try {
            $this->certificate->update([
                'certificate_image_path' => null,
                'image_updated_at' => null,
            ]);

            $this->imageUrl = null;
            $this->imageUrlInput = '';
            $this->isError = false;
            $this->message = '✓ Imagen eliminada exitosamente';

            $this->dispatch('certificate-image-deleted');
        } catch (\Exception $e) {
            $this->isError = true;
            $this->message = 'Error al eliminar la imagen.';
        }
    }

    public function render()
    {
        return view('livewire.certificate-image-uploader');
    }
}
