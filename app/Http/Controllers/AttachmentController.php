<?php

// FILE: app/Http/Controllers/AttachmentController.php | V7

namespace App\Http\Controllers;

use App\Http\Requests\Attachments\StoreAttachmentRequest;
use App\Http\Requests\Attachments\UpdateAttachmentRequest;
use App\Models\Attachment;
use App\Support\Attachments\AttachmentAllowedParents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        ]);
    }

    public function store(StoreAttachmentRequest $request)
    {
        $attachableType = (string) $request->validated('attachable_type');
        $attachableId = (string) $request->validated('attachable_id');

        $attachable = $this->resolveAttachable($attachableType, $attachableId);

        $this->authorize('update', $attachable);

        $file = $request->file('file');
        $directory = 'attachments';
        $path = $file->store($directory);
        $storedName = basename($path);

        Attachment::create([
            'tenant_id' => app('tenant')->id,
            'attachable_type' => $attachable::class,
            'attachable_id' => $attachable->id,
            'uploaded_by_user_id' => auth()->id(),
            'directory' => $directory,
            'stored_name' => $storedName,
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'is_image' => str_starts_with((string) $file->getMimeType(), 'image/'),
            'description' => $request->validated('description'),
        ]);

        return redirect($request->validated('return_to') ?: $this->parentShowUrl($attachableType, $attachable))
            ->with('success', 'Archivo adjuntado correctamente.');
    }

    public function edit(Request $request, Attachment $attachment)
    {
        $this->authorize('update', $attachment);

        return view('attachments.edit', [
            'attachment' => $attachment,
            'attachableType' => $this->typeAliasFromStoredType($attachment->attachable_type),
            'attachableId' => $attachment->attachable_id,
            'returnTo' => $request->query('return_to') ?: $this->parentShowUrl($attachment->attachable_type, $attachment->attachable),
        ]);
    }

    public function update(UpdateAttachmentRequest $request, Attachment $attachment)
    {
        $this->authorize('update', $attachment);

        $attachment->update([
            'description' => $request->validated('description'),
        ]);

        return redirect($request->validated('return_to') ?: $this->parentShowUrl($attachment->attachable_type, $attachment->attachable))
            ->with('success', 'Adjunto actualizado.');
    }

    public function destroy(Request $request, Attachment $attachment)
    {
        $this->authorize('delete', $attachment);

        Storage::delete($attachment->full_path);
        $attachment->delete();

        $returnTo = $request->input('return_to');

        return redirect($returnTo ?: $this->parentShowUrl($attachment->attachable_type, $attachment->attachable))
            ->with('success', 'Adjunto eliminado.');
    }

    public function download(Attachment $attachment)
    {
        $this->authorize('view', $attachment);

        return Storage::download(
            $attachment->full_path,
            $attachment->original_name
        );
    }

    public function preview(Attachment $attachment)
    {
        $this->authorize('view', $attachment);

        return response()->file(
            storage_path('app/'.$attachment->full_path)
        );
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
            'order', \App\Models\Order::class => 'order',
            'document', \App\Models\Document::class => 'document',
            'asset', \App\Models\Asset::class => 'asset',
            'project', \App\Models\Project::class => 'project',
            'task', \App\Models\Task::class => 'task',
            'product', \App\Models\Product::class => 'product',
            default => 'document',
        };
    }

    private function parentShowUrl(string $attachableType, object $attachable): string
    {
        return match ($attachableType) {
            'order', \App\Models\Order::class => route('orders.show', $attachable),
            'document', \App\Models\Document::class => route('documents.show', $attachable),
            'asset', \App\Models\Asset::class => route('assets.show', $attachable),
            'project', \App\Models\Project::class => route('projects.show', $attachable),
            'task', \App\Models\Task::class => route('tasks.show', $attachable),
            'product', \App\Models\Product::class => route('products.show', $attachable),
            default => route('dashboard'),
        };
    }
}
