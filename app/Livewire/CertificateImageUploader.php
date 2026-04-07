<?php

namespace App\Livewire;

use App\Models\Certificate;
use App\Support\CertificateImageStorageService;
use Livewire\Component;
use Livewire\WithFileUploads;

class CertificateImageUploader extends Component
{
    use WithFileUploads;

    public ?Certificate $certificate = null;
    public $image = null;
    public $imageUrl = null;
    public $isLoading = false;
    public $message = null;
    public $isError = false;

    public function mount(string $serial): void
    {
        $this->certificate = Certificate::where('serial', $serial)->firstOrFail();
        $this->imageUrl = $this->certificate->getImageUrl();
    }

    public function updatedImage(): void
    {
        try {
            $this->validate([
                'image' => 'required|file|image|max:5120|mimes:jpeg,png,webp,gif',
            ], [
                'image.required' => 'Se requiere una imagen.',
                'image.file' => 'El archivo debe ser válido.',
                'image.image' => 'El archivo debe ser una imagen válida.',
                'image.max' => 'La imagen no debe exceder 5 MB.',
                'image.mimes' => 'Solo se aceptan JPEG, PNG, WebP o GIF.',
            ]);

            $this->uploadImage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->isError = true;
            $this->message = $e->errors()['image'][0] ?? 'Error de validación';
        }
    }

    public function uploadImage(): void
    {
        if (!$this->image || !$this->certificate) {
            return;
        }

        try {
            $this->isLoading = true;

            $storageService = new CertificateImageStorageService();

            // Eliminar imagen anterior si existe
            if ($this->certificate->certificate_image_path) {
                $storageService->delete($this->certificate->certificate_image_path);
            }

            // Guardar nueva imagen
            $imagePath = $storageService->store($this->image);
            $imageUrl = $storageService->url($imagePath);

            // Actualizar registro
            $this->certificate->update([
                'certificate_image_path' => $imagePath,
                'image_updated_at' => now(),
            ]);

            $this->imageUrl = $imageUrl;
            $this->image = null;
            $this->isError = false;
            $this->message = '✓ Imagen cargada exitosamente';

            $this->dispatch('certificate-image-uploaded');
        } catch (\InvalidArgumentException $e) {
            $this->isError = true;
            $this->message = $e->getMessage();
        } catch (\Exception $e) {
            $this->isError = true;
            $this->message = 'Error al guardar la imagen. Intenta de nuevo.';
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
            $storageService = new CertificateImageStorageService();
            $storageService->delete($this->certificate->certificate_image_path);

            $this->certificate->update([
                'certificate_image_path' => null,
                'image_updated_at' => null,
            ]);

            $this->imageUrl = null;
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
