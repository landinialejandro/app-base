<?php

// FILE: app/Http/Controllers/AttachmentController.php | V1

namespace App\Http\Controllers;

use App\Http\Requests\Attachments\StoreAttachmentRequest;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request): RedirectResponse
    {
        $tenant = app('tenant');
        abort_unless($tenant, 404);

        $data = $request->normalizedData();

        $attachable = $this->resolveAttachable(
            $data['attachable_type'],
            $data['attachable_id'],
            $tenant->id
        );

        $this->authorizeAttachableUpdate($attachable);

        $uploadedFile = $request->file('file');
        abort_unless($uploadedFile, 422);

        $originalName = $uploadedFile->getClientOriginalName();
        $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
        $mimeType = $uploadedFile->getMimeType() ?: $uploadedFile->getClientMimeType();
        $sizeBytes = (int) $uploadedFile->getSize();
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);

        $disk = 'local';
        $directory = $this->buildStorageDirectory($tenant->id, $attachable);
        $storedName = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');

        $path = $uploadedFile->storeAs($directory, $storedName, $disk);

        abort_unless($path !== false, 500, 'No se pudo almacenar el archivo.');

        $attachment = Attachment::create([
            'tenant_id' => $tenant->id,
            'attachable_type' => $data['attachable_type'],
            'attachable_id' => $data['attachable_id'],
            'uploaded_by_user_id' => auth()->id(),
            'disk' => $disk,
            'directory' => $directory,
            'stored_name' => $storedName,
            'original_name' => $originalName,
            'extension' => $extension !== '' ? $extension : null,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'checksum_sha256' => hash_file('sha256', $uploadedFile->getRealPath()),
            'kind' => $data['kind'],
            'category' => $data['category'],
            'is_image' => $isImage,
            'sort_order' => $data['sort_order'],
            'title' => $data['title'],
            'description' => $data['description'],
            'analysis_status' => null,
            'meta_json' => [
                'uploaded_via' => 'ui',
            ],
        ]);

        return back()->with('status', 'Adjunto cargado correctamente.');
    }

    public function preview(Attachment $attachment): Response|StreamedResponse
    {
        $attachable = $attachment->attachable;
        abort_unless($attachable, 404);

        $this->authorizeAttachableView($attachable);

        $disk = $attachment->disk;
        $path = $attachment->storage_path;

        abort_unless(Storage::disk($disk)->exists($path), 404);

        $mimeType = $attachment->mime_type ?: Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';

        return Storage::disk($disk)->response(
            $path,
            $attachment->original_name,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
            ]
        );
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $attachable = $attachment->attachable;
        abort_unless($attachable, 404);

        $this->authorizeAttachableView($attachable);

        $disk = $attachment->disk;
        $path = $attachment->storage_path;

        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download(
            $path,
            $attachment->original_name
        );
    }

    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        $attachable = $attachment->attachable;
        abort_unless($attachable, 404);

        $this->authorizeAttachableUpdate($attachable);

        $disk = $attachment->disk;
        $path = $attachment->storage_path;

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }

        $attachment->delete();

        return back()->with('status', 'Adjunto eliminado correctamente.');
    }

    protected function resolveAttachable(string $attachableType, int $attachableId, string $tenantId): Model
    {
        $modelClass = $this->allowedAttachableModel($attachableType);

        $query = $modelClass::query()
            ->where('id', $attachableId)
            ->where('tenant_id', $tenantId);

        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass), true)) {
            $query->whereNull('deleted_at');
        }

        $attachable = $query->first();

        abort_unless($attachable, 404);

        return $attachable;
    }

    protected function allowedAttachableModel(string $attachableType): string
    {
        $allowed = [
            Asset::class,
            Product::class,
            Project::class,
            Task::class,
            Order::class,
        ];

        abort_unless(in_array($attachableType, $allowed, true), 404);

        return $attachableType;
    }

    protected function buildStorageDirectory(string $tenantId, Model $attachable): string
    {
        return implode('/', [
            'tenants',
            $tenantId,
            'attachments',
            $this->attachableDirectoryName($attachable),
            $attachable->getKey(),
        ]);
    }

    protected function attachableDirectoryName(Model $attachable): string
    {
        return match ($attachable::class) {
            Asset::class => 'assets',
            Product::class => 'products',
            Project::class => 'projects',
            Task::class => 'tasks',
            Order::class => 'orders',
            default => 'other',
        };
    }

    protected function authorizeAttachableView(Model $attachable): void
    {
        $this->authorize('view', $attachable);
    }

    protected function authorizeAttachableUpdate(Model $attachable): void
    {
        $this->authorize('update', $attachable);
    }
}
