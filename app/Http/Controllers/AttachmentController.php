<?php

// FILE: app/Http/Controllers/AttachmentController.php | V9

namespace App\Http\Controllers;

use App\Http\Requests\Attachments\StoreAttachmentRequest;
use App\Http\Requests\Attachments\UpdateAttachmentRequest;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Document;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Task;
use App\Support\Attachments\AttachmentAllowedParents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttachmentController extends Controller
{
    public function create(Request $request)
    {
        $attachableType = (string) $request->query('attachable_type');
        $attachableId = (string) $request->query('attachable_id');

        $attachable = $this->resolveAttachable($attachableType, $attachableId);

        $this->authorize('update', $attachable);

        return view('attachments.create', [
            'attachableType' => $attachableType,
            'attachableId' => $attachableId,
            'returnTo' => $request->query('return_to') ?: $this->parentShowUrl($attachableType, $attachable),
            'breadcrumbItems' => $this->breadcrumbItemsForCreate($attachableType, $attachable),
        ]);
    }

    public function store(StoreAttachmentRequest $request)
    {
        $attachableType = (string) $request->validated('attachable_type');
        $attachableId = (string) $request->validated('attachable_id');

        $attachable = $this->resolveAttachable($attachableType, $attachableId);

        $this->authorize('update', $attachable);

        $file = $request->file('file');
        $disk = 'local';
        $directory = 'attachments';

        $path = $file->store($directory, $disk);
        $storedName = basename($path);

        Attachment::create([
            'tenant_id' => app('tenant')->id,
            'attachable_type' => $attachable::class,
            'attachable_id' => $attachable->id,
            'uploaded_by_user_id' => auth()->id(),
            'disk' => $disk,
            'directory' => $directory,
            'stored_name' => $storedName,
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'kind' => $request->validated('kind'),
            'is_image' => str_starts_with((string) $file->getMimeType(), 'image/'),
            'description' => $request->validated('description'),
        ]);

        return redirect($request->validated('return_to') ?: $this->parentShowUrl($attachableType, $attachable))
            ->with('success', 'Archivo adjuntado correctamente.');
    }

    public function edit(Request $request, Attachment $attachment)
    {
        $this->authorize('update', $attachment);

        $attachableType = $this->typeAliasFromStoredType($attachment->attachable_type);
        $attachable = $attachment->attachable;

        return view('attachments.edit', [
            'attachment' => $attachment,
            'attachableType' => $attachableType,
            'attachableId' => $attachment->attachable_id,
            'returnTo' => $request->query('return_to') ?: $this->parentShowUrl($attachment->attachable_type, $attachable),
            'breadcrumbItems' => $this->breadcrumbItemsForEdit($attachableType, $attachable),
        ]);
    }

    public function update(UpdateAttachmentRequest $request, Attachment $attachment)
    {
        $this->authorize('update', $attachment);

        $attachment->update([
            'kind' => $request->validated('kind'),
            'description' => $request->validated('description'),
        ]);

        return redirect($request->validated('return_to') ?: $this->parentShowUrl($attachment->attachable_type, $attachment->attachable))
            ->with('success', 'Adjunto actualizado.');
    }

    public function destroy(Request $request, Attachment $attachment)
    {
        $this->authorize('delete', $attachment);

        $disk = $this->disk($attachment);

        if ($disk->exists($attachment->full_path)) {
            $disk->delete($attachment->full_path);
        }

        $attachment->delete();

        $returnTo = $request->input('return_to');

        return redirect($returnTo ?: $this->parentShowUrl($attachment->attachable_type, $attachment->attachable))
            ->with('success', 'Adjunto eliminado.');
    }

    public function download(Attachment $attachment)
    {
        $this->authorize('view', $attachment);

        $disk = $this->disk($attachment);

        if (! $disk->exists($attachment->full_path)) {
            throw new NotFoundHttpException('El archivo adjunto no existe en el almacenamiento configurado.');
        }

        return $disk->download($attachment->full_path, $attachment->original_name);
    }

    public function preview(Attachment $attachment)
    {
        $this->authorize('view', $attachment);

        $disk = $this->disk($attachment);

        if (! $disk->exists($attachment->full_path)) {
            throw new NotFoundHttpException('El archivo adjunto no existe en el almacenamiento configurado.');
        }

        return response()->file(
            $disk->path($attachment->full_path),
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
            ]
        );
    }

    private function disk(Attachment $attachment)
    {
        return Storage::disk($attachment->disk ?: config('filesystems.default'));
    }

    private function resolveAttachable(string $attachableType, string $attachableId): object
    {
        if (! in_array($attachableType, AttachmentAllowedParents::types(), true)) {
            abort(404);
        }

        $modelClass = AttachmentAllowedParents::resolve($attachableType);

        return $modelClass::query()->findOrFail($attachableId);
    }

    private function typeAliasFromStoredType(string $storedType): string
    {
        return match ($storedType) {
            'order', Order::class => 'order',
            'document', Document::class => 'document',
            'asset', Asset::class => 'asset',
            'project', Project::class => 'project',
            'task', Task::class => 'task',
            'product', Product::class => 'product',
            default => 'document',
        };
    }

    private function parentShowUrl(string $attachableType, object $attachable): string
    {
        return match ($attachableType) {
            'order', Order::class => route('orders.show', $attachable),
            'document', Document::class => route('documents.show', $attachable),
            'asset', Asset::class => route('assets.show', $attachable),
            'project', Project::class => route('projects.show', $attachable),
            'task', Task::class => route('tasks.show', $attachable),
            'product', Product::class => route('products.show', $attachable),
            default => route('dashboard'),
        };
    }

    private function parentLabel(string $attachableType): string
    {
        return match ($attachableType) {
            'order', Order::class => 'Orden',
            'document', Document::class => 'Documento',
            'asset', Asset::class => 'Activo',
            'project', Project::class => 'Proyecto',
            'task', Task::class => 'Tarea',
            'product', Product::class => 'Producto',
            default => 'Registro',
        };
    }

    private function breadcrumbItemsForCreate(string $attachableType, object $attachable): array
    {
        return [
            [
                'label' => $this->parentLabel($attachableType),
                'url' => $this->parentShowUrl($attachableType, $attachable),
            ],
            [
                'label' => 'Agregar adjunto',
                'url' => null,
            ],
        ];
    }

    private function breadcrumbItemsForEdit(string $attachableType, object $attachable): array
    {
        return [
            [
                'label' => $this->parentLabel($attachableType),
                'url' => $this->parentShowUrl($attachableType, $attachable),
            ],
            [
                'label' => 'Editar adjunto',
                'url' => null,
            ],
        ];
    }
}
