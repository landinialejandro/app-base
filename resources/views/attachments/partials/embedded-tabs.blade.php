{{-- FILE: resources/views/attachments/partials/embedded-tabs.blade.php | V3 --}}

@php
    use App\Support\Attachments\AttachmentCategory;
    use Illuminate\Support\Str;

    $attachments = ($attachments ?? collect())->values();
    $attachable = $attachable ?? null;
    $returnTo = $returnTo ?? url()->current();

    $attachableSlug = $attachable ? Str::kebab(class_basename($attachable)) . '-' . $attachable->getKey() : 'x';

    $tabsId = $tabsId ?? 'attachments-tabs-' . $attachableSlug;
    $viewerModalId = $viewerModalId ?? 'attachments-viewer-' . $attachableSlug;
    $createModalId = $tabsId . '-create-modal';

    $oldMode = old('attachment_form_mode');
    $oldKey = old('attachment_form_key');
    $restoreModalId = null;

    if (
        $oldMode === 'create' &&
        $oldKey ===
            'attachment-create-' . ($attachable ? get_class($attachable) : 'x') . '-' . ($attachable?->getKey() ?? 'x')
    ) {
        $restoreModalId = $createModalId;
    }

    if ($oldMode === 'edit' && is_string($oldKey) && Str::startsWith($oldKey, 'attachment-edit-')) {
        $restoreModalId = 'attachment-edit-modal-' . Str::after($oldKey, 'attachment-edit-');
    }

    $groups = [
        'all' => [
            'label' => 'Todos',
            'items' => $attachments,
        ],
        'photos' => [
            'label' => 'Fotos',
            'items' => $attachments->filter(fn($item) => (bool) $item->is_image)->values(),
        ],
        'manuals' => [
            'label' => 'Manuales',
            'items' => $attachments->filter(fn($item) => $item->category === AttachmentCategory::MANUAL)->values(),
        ],
        'evidences' => [
            'label' => 'Evidencias',
            'items' => $attachments->filter(fn($item) => $item->category === AttachmentCategory::EVIDENCE)->values(),
        ],
        'support' => [
            'label' => 'Soporte',
            'items' => $attachments->filter(fn($item) => $item->category === AttachmentCategory::SUPPORT)->values(),
        ],
        'other' => [
            'label' => 'Otros',
            'items' => $attachments
                ->filter(function ($item) {
                    return !$item->is_image &&
                        !in_array(
                            $item->category,
                            [AttachmentCategory::MANUAL, AttachmentCategory::EVIDENCE, AttachmentCategory::SUPPORT],
                            true,
                        );
                })
                ->values(),
        ],
    ];
@endphp

<div data-tabs>
    <x-tab-toolbar label="Adjuntos">
        <x-slot:tabs>
            @foreach ($groups as $key => $group)
                <button type="button" class="tabs-link {{ $loop->first ? 'is-active' : '' }}"
                    data-tab-link="{{ $tabsId }}-{{ $key }}" role="tab"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    {{ $group['label'] }}
                    @if ($group['items']->count())
                        ({{ $group['items']->count() }})
                    @endif
                </button>
            @endforeach
        </x-slot:tabs>

        <x-slot:actions>
            <button type="button" class="btn btn-success btn-sm" data-action="app-modal-open"
                data-modal-target="#{{ $createModalId }}">
                <x-icons.plus />
                <span>Agregar adjunto</span>
            </button>
        </x-slot:actions>
    </x-tab-toolbar>

    @foreach ($groups as $key => $group)
        <section class="tab-panel {{ $loop->first ? 'is-active' : '' }}"
            data-tab-panel="{{ $tabsId }}-{{ $key }}" @if (!$loop->first) hidden @endif>
            <div class="tab-panel-stack attachment-tab-panel-stack">
                <x-card class="list-card">
                    @include('attachments.partials.table', [
                        'attachments' => $group['items'],
                        'attachable' => $attachable,
                        'emptyMessage' => 'No hay adjuntos para esta pestaña.',
                        'viewerModalId' => $viewerModalId,
                        'viewerIds' => $group['items']->pluck('id')->all(),
                        'returnTo' => $returnTo,
                        'renderEditModals' => false,
                    ])
                </x-card>
            </div>
        </section>
    @endforeach

    @if ($restoreModalId)
        <div hidden data-attachment-form-restore data-parent-tab-link="attachments"
            data-modal-target="#{{ $restoreModalId }}">
        </div>
    @endif
</div>

@foreach ($attachments as $attachment)
    @include('attachments.partials.edit-modal', [
        'attachment' => $attachment,
        'attachable' => $attachable,
        'modalId' => 'attachment-edit-modal-' . $attachment->id,
        'returnTo' => $returnTo,
    ])
@endforeach

@include('attachments.partials.create-modal', [
    'attachable' => $attachable,
    'modalId' => $createModalId,
    'returnTo' => $returnTo,
])

@include('attachments.partials.viewer-modal', [
    'attachments' => $attachments,
    'modalId' => $viewerModalId,
])
