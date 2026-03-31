<?php

// FILE: app/Http/Controllers/AttachmentController.php | V10

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
use App\Support\Navigation\AssetNavigationTrail;
use App\Support\Navigation\DocumentNavigationTrail;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\OrderNavigationTrail;
use App\Support\Navigation\ProductNavigationTrail;
use App\Support\Navigation\ProjectNavigationTrail;
use App\Support\Navigation\TaskNavigationTrail;
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

        $parentTrail = $this->parentTrailFromRequest($request, $attachableType, $attachable);
        $trailQuery = NavigationTrail::toQuery($parentTrail);

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $parentTrail,
            NavigationTrail::makeNode(
                'attachments.create',
                $attachableType.'-'.$attachableId,
                'Agregar adjunto',
                route('attachments.create', [
                    'attachable_type' => $attachableType,
                    'attachable_id' => $attachableId,
                ] + $trailQuery)
            )
        );

        return view('attachments.create', [
            'attachableType' => $attachableType,
            'attachableId' => $attachableId,
            'returnTo' => $request->query('return_to') ?: $this->parentShowUrlWithTrail($attachableType, $attachable, $parentTrail),
            'breadcrumbItems' => NavigationTrail::toBreadcrumbItems($navigationTrail),
            'navigationTrail' => $navigationTrail,
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

        $parentTrail = $this->parentTrailFromRequest($request, $attachableType, $attachable);

        return redirect($request->validated('return_to') ?: $this->parentShowUrlWithTrail($attachableType, $attachable, $parentTrail))
            ->with('success', 'Archivo adjuntado correctamente.');
    }

    public function edit(Request $request, Attachment $attachment)
    {
        $this->authorize('update', $attachment);

        $attachableType = $this->typeAliasFromStoredType($attachment->attachable_type);
        $attachable = $attachment->attachable;

        $parentTrail = $this->parentTrailFromRequest($request, $attachableType, $attachable);
        $trailQuery = NavigationTrail::toQuery($parentTrail);

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $parentTrail,
            NavigationTrail::makeNode(
                'attachments.edit',
                $attachment->id,
                'Editar adjunto',
                route('attachments.edit', ['attachment' => $attachment] + $trailQuery)
            )
        );

        return view('attachments.edit', [
            'attachment' => $attachment,
            'attachableType' => $attachableType,
            'attachableId' => $attachment->attachable_id,
            'returnTo' => $request->query('return_to') ?: $this->parentShowUrlWithTrail($attachableType, $attachable, $parentTrail),
            'breadcrumbItems' => NavigationTrail::toBreadcrumbItems($navigationTrail),
            'navigationTrail' => $navigationTrail,
        ]);
    }

    public function update(UpdateAttachmentRequest $request, Attachment $attachment)
    {
        $this->authorize('update', $attachment);

        $attachment->update([
            'kind' => $request->validated('kind'),
            'description' => $request->validated('description'),
        ]);

        $attachableType = $this->typeAliasFromStoredType($attachment->attachable_type);
        $parentTrail = $this->parentTrailFromRequest($request, $attachableType, $attachment->attachable);

        return redirect($request->validated('return_to') ?: $this->parentShowUrlWithTrail($attachableType, $attachment->attachable, $parentTrail))
            ->with('success', 'Adjunto actualizado.');
    }

    public function destroy(Request $request, Attachment $attachment)
    {
        $this->authorize('delete', $attachment);

        $disk = $this->disk($attachment);

        if ($disk->exists($attachment->full_path)) {
            $disk->delete($attachment->full_path);
        }

        $attachableType = $this->typeAliasFromStoredType($attachment->attachable_type);
        $attachable = $attachment->attachable;
        $attachment->delete();

        $parentTrail = $this->parentTrailFromRequest($request, $attachableType, $attachable);
        $returnTo = $request->input('return_to');

        return redirect($returnTo ?: $this->parentShowUrlWithTrail($attachableType, $attachable, $parentTrail))
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

    private function parentShowUrlWithTrail(string $attachableType, object $attachable, array $parentTrail): string
    {
        return match ($attachableType) {
            'order', Order::class => route('orders.show', ['order' => $attachable] + NavigationTrail::toQuery($parentTrail)),
            'document', Document::class => route('documents.show', ['document' => $attachable] + NavigationTrail::toQuery($parentTrail)),
            'asset', Asset::class => route('assets.show', ['asset' => $attachable] + NavigationTrail::toQuery($parentTrail)),
            'project', Project::class => route('projects.show', ['project' => $attachable] + NavigationTrail::toQuery($parentTrail)),
            'task', Task::class => route('tasks.show', ['task' => $attachable] + NavigationTrail::toQuery($parentTrail)),
            'product', Product::class => route('products.show', ['product' => $attachable] + NavigationTrail::toQuery($parentTrail)),
            default => route('dashboard'),
        };
    }

    private function parentTrailFromRequest(Request $request, string $attachableType, object $attachable): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = NavigationTrail::decode((string) $request->input('trail', ''));
        }

        if (empty($trail)) {
            return $this->parentBaseTrail($attachableType, $attachable);
        }

        return $trail;
    }

    private function parentBaseTrail(string $attachableType, object $attachable): array
    {
        return match ($attachableType) {
            'order', Order::class => OrderNavigationTrail::base($attachable),
            'document', Document::class => DocumentNavigationTrail::base($attachable),
            'asset', Asset::class => AssetNavigationTrail::base($attachable),
            'project', Project::class => ProjectNavigationTrail::base($attachable),
            'task', Task::class => TaskNavigationTrail::base($attachable),
            'product', Product::class => ProductNavigationTrail::base($attachable),
            default => NavigationTrail::base([
                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            ]),
        };
    }
}
