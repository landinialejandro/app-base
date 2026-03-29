<?php

// FILE: app/Support/Navigation/DocumentNavigationTrail.php | V4

namespace App\Support\Navigation;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Order;
use Illuminate\Http\Request;

class DocumentNavigationTrail
{
    public static function documentsBase(): array
    {
        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('documents.index', null, 'Documentos', route('documents.index')),
        ]);
    }

    public static function base(Document $document): array
    {
        $trail = self::documentsBase();

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'documents.show',
                $document->id,
                $document->number ?: 'Documento #'.$document->id,
                route('documents.show', ['document' => $document])
            )
        );
    }

    public static function create(Request $request, ?Order $order = null): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = $order
                ? OrderNavigationTrail::base($order)
                : self::documentsBase();
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'documents.create',
                'new',
                'Nuevo documento',
                route('documents.create')
            )
        );
    }

    public static function show(Request $request, Document $document): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::documentsBase();
        }

        $normalized = NavigationTrail::normalize($trail);

        $filtered = array_values(array_filter($normalized, function (array $node) use ($document) {
            $key = (string) ($node['key'] ?? '');
            $id = $node['id'] ?? null;

            if ($key === 'documents.create' && $id === 'new') {
                return false;
            }

            if ($key === 'documents.edit' && (string) $id === (string) $document->id) {
                return false;
            }

            if ($key === 'documents.items.create' && (string) $id === (string) $document->id) {
                return false;
            }

            if ($key === 'documents.items.edit') {
                return false;
            }

            return true;
        }));

        return NavigationTrail::appendOrCollapse(
            $filtered,
            NavigationTrail::makeNode(
                'documents.show',
                $document->id,
                $document->number ?: 'Documento #'.$document->id,
                route('documents.show', ['document' => $document])
            )
        );
    }

    public static function edit(Request $request, Document $document): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'documents.show', $document->id)) {
            $trail = self::show($request, $document);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'documents.edit',
                $document->id,
                'Editar',
                route('documents.edit', ['document' => $document])
            )
        );
    }

    public static function itemCreate(Request $request, Document $document): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'documents.show', $document->id)) {
            $trail = self::base($document);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'documents.items.create',
                $document->id,
                'Agregar ítem',
                route('documents.items.create', ['document' => $document])
            )
        );
    }

    public static function itemEdit(Request $request, Document $document, DocumentItem $item): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'documents.show', $document->id)) {
            $trail = self::base($document);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'documents.items.edit',
                $item->id,
                'Editar ítem',
                route('documents.items.edit', ['document' => $document, 'item' => $item])
            )
        );
    }
}
