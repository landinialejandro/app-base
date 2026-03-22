<?php

// FILE: app/Support/Navigation/DocumentNavigationTrail.php

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

        $trail = NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'documents.show',
                $document->id,
                $document->number ?: 'Documento #'.$document->id,
                route('documents.show', ['document' => $document])
            )
        );

        return NavigationTrail::replaceCurrentUrl(
            $trail,
            route('documents.show', ['document' => $document] + NavigationTrail::toQuery($trail))
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

        $trail = NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'documents.create',
                'new',
                'Nuevo documento',
                route('documents.create')
            )
        );

        return NavigationTrail::replaceCurrentUrl(
            $trail,
            route('documents.create', NavigationTrail::toQuery($trail))
        );
    }

    public static function show(Request $request, Document $document): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::documentsBase();
        }

        $trail = NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'documents.show',
                $document->id,
                $document->number ?: 'Documento #'.$document->id,
                route('documents.show', ['document' => $document])
            )
        );

        return NavigationTrail::replaceCurrentUrl(
            $trail,
            route('documents.show', ['document' => $document] + NavigationTrail::toQuery($trail))
        );
    }

    public static function edit(Request $request, Document $document): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'documents.show', $document->id)) {
            $trail = self::show($request, $document);
        }

        $trail = NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'documents.edit',
                $document->id,
                'Editar',
                route('documents.edit', ['document' => $document])
            )
        );

        return NavigationTrail::replaceCurrentUrl(
            $trail,
            route('documents.edit', ['document' => $document] + NavigationTrail::toQuery($trail))
        );
    }

    public static function itemCreate(Request $request, Document $document): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'documents.show', $document->id)) {
            $trail = self::base($document);
        }

        $trail = NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'documents.items.create',
                $document->id,
                'Agregar ítem',
                route('documents.items.create', ['document' => $document])
            )
        );

        return NavigationTrail::replaceCurrentUrl(
            $trail,
            route('documents.items.create', ['document' => $document] + NavigationTrail::toQuery($trail))
        );
    }

    public static function itemEdit(Request $request, Document $document, DocumentItem $item): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'documents.show', $document->id)) {
            $trail = self::base($document);
        }

        $trail = NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'documents.items.edit',
                $item->id,
                'Editar ítem',
                route('documents.items.edit', ['document' => $document, 'item' => $item])
            )
        );

        return NavigationTrail::replaceCurrentUrl(
            $trail,
            route('documents.items.edit', ['document' => $document, 'item' => $item] + NavigationTrail::toQuery($trail))
        );
    }
}
