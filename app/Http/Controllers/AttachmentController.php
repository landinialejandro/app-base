<?php

// FILE: app/Http/Controllers/AttachmentController.php | V2

namespace App\Http\Controllers;

use App\Http\Requests\Attachments\StoreAttachmentRequest;
use App\Http\Requests\Attachments\UpdateAttachmentRequest;
use App\Models\Attachment;
use App\Support\Attachments\AttachmentAllowedParents;
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

        Attachment::create([
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

        return $this->redirectAfterMutation(
            $request,
            $attachable,
            'Adjunto cargado correctamente.'
        );
    }

    public function edit(Attachment $attachment)
    {
        $attachable = $attachment->attachable;
        abort_unless($attachable, 404);

        $this->authorizeAttachableUpdate($attachable);

        return view('attachments.edit', [
            'attachment' => $attachment,
            'attachable' => $attachable,
            'cancelUrl' => $this->attachableShowUrl($attachable),
        ]);
    }

    public function update(UpdateAttachmentRequest $request, Attachment $attachment): RedirectResponse
    {
        $attachable = $attachment->attachable;
        abort_unless($attachable, 404);

        $this->authorizeAttachableUpdate($attachable);

        $data = $request->normalizedData();

        $attachment->update([
            'kind' => $data['kind'],
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description'],
        ]);

        return $this->redirectAfterMutation(
            $request,
            $attachable,
            'Adjunto actualizado correctamente.'
        );
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

        return Storage::disk($disk)->download($path, $attachment->original_name);
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

        return $this->redirectAfterMutation(
            $request,
            $attachable,
            'Adjunto eliminado correctamente.'
        );
    }

    protected function resolveAttachable(string $attachableType, int $attachableId, string $tenantId): Model
    {
        abort_unless(AttachmentAllowedParents::isAllowed($attachableType), 404);

        $query = $attachableType::query()
            ->where('id', $attachableId)
            ->where('tenant_id', $tenantId);

        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($attachableType), true)) {
            $query->whereNull('deleted_at');
        }

        $attachable = $query->first();

        abort_unless($attachable, 404);

        return $attachable;
    }

    protected function buildStorageDirectory(string $tenantId, Model $attachable): string
    {
        return implode('/', [
            'tenants',
            $tenantId,
            'attachments',
            AttachmentAllowedParents::directoryNameFor($attachable),
            $attachable->getKey(),
        ]);
    }

    protected function authorizeAttachableView(Model $attachable): void
    {
        $this->authorize('view', $attachable);
    }

    protected function authorizeAttachableUpdate(Model $attachable): void
    {
        $this->authorize('update', $attachable);
    }

    protected function redirectAfterMutation(Request $request, Model $attachable, string $message): RedirectResponse
    {
        $returnTo = trim((string) $request->input('return_to', ''));

        if ($returnTo !== '') {
            return redirect()->to($returnTo)->with('status', $message);
        }

        $showUrl = $this->attachableShowUrl($attachable);

        if ($showUrl) {
            return redirect()->to($showUrl)->with('status', $message);
        }

        return back()->with('status', $message);
    }

    protected function attachableShowUrl(Model $attachable): ?string
    {
        $routeName = AttachmentAllowedParents::showRouteNameFor($attachable);

        if (! $routeName) {
            return null;
        }

        return route($routeName, $attachable);
    }
}
