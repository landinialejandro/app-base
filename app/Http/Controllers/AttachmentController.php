<?php

// FILE: app/Http/Controllers/AttachmentController.php | V5

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
        $attachableType = $request->query('attachable_type');
        $attachableId = $request->query('attachable_id');

        if (! in_array($attachableType, AttachmentAllowedParents::types(), true)) {
            abort(404);
        }

        $modelClass = AttachmentAllowedParents::resolve($attachableType);
        $attachable = $modelClass::query()->findOrFail($attachableId);

        return view('attachments.create', [
            'attachableType' => $attachableType,
            'attachableId' => $attachableId,
            'returnTo' => $request->query('return_to') ?: $this->parentShowUrl($attachableType, $attachable),
        ]);
    }

    public function store(StoreAttachmentRequest $request)
    {
        $modelClass = AttachmentAllowedParents::resolve($request->attachable_type);
        $attachable = $modelClass::query()->where('id', $request->attachable_id)->firstOrFail();

        $file = $request->file('file');
        $directory = 'attachments';
        $path = $file->store($directory); // Retorna "attachments/nombre_random.ext"
        $storedName = basename($path);

        Attachment::create([
            'tenant_id' => auth()->user()->tenant_id,
            'attachable_type' => $request->attachable_type,
            'attachable_id' => $attachable->id,
            'uploaded_by_user_id' => auth()->id(),
            'directory' => $directory,
            'stored_name' => $storedName,
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'is_image' => str_starts_with($file->getMimeType(), 'image/'),
            'description' => $request->description,
        ]);

        return redirect($request->validated('return_to') ?: $this->parentShowUrl($request->attachable_type, $attachable))
            ->with('success', 'Archivo adjuntado correctamente.');
    }

    public function edit(Request $request, Attachment $attachment)
    {
        return view('attachments.edit', [
            'attachment' => $attachment,
            'attachableType' => $attachment->attachable_type,
            'attachableId' => $attachment->attachable_id,
            'returnTo' => $request->query('return_to') ?: $this->parentShowUrl($attachment->attachable_type, $attachment->attachable),
        ]);
    }

    public function update(UpdateAttachmentRequest $request, Attachment $attachment)
    {
        $attachment->update([
            'description' => $request->description,
        ]);

        return redirect($request->validated('return_to') ?: $this->parentShowUrl($attachment->attachable_type, $attachment->attachable))
            ->with('success', 'Adjunto actualizado.');
    }

    public function destroy(Attachment $attachment)
    {
        Storage::delete($attachment->full_path);
        $attachment->delete();

        return redirect()->back()->with('success', 'Adjunto eliminado.');
    }

    public function download(Attachment $attachment)
    {
        return Storage::download(
            $attachment->full_path,
            $attachment->original_name
        );
    }

    public function preview(Attachment $attachment)
    {
        return response()->file(
            storage_path('app/'.$attachment->full_path)
        );
    }

    private function parentShowUrl(string $attachableType, object $attachable): string
    {
        return match ($attachableType) {
            'order' => route('orders.show', $attachable),
            'document' => route('documents.show', $attachable),
            'asset' => route('assets.show', $attachable),
            'project' => route('projects.show', $attachable),
            'task' => route('tasks.show', $attachable),
            'product' => route('products.show', $attachable),
            default => route('dashboard'),
        };
    }
}
