{{-- FILE: resources/views/documents/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalle del documento')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Documentos', 'url' => route('documents.index')],
            ['label' => $document->number ?: 'Sin número'],
        ]" />

        <x-page-header title="Detalle del documento">
            <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary">
                Editar
            </a>

            <form method="POST" action="{{ route('documents.destroy', $document) }}" class="d-inline" onsubmit="return confirm(@js(
                $document->items->count()
                ? 'Este documento tiene ítems cargados. Si lo eliminas, también se eliminarán sus ítems. ¿Deseas continuar?'
                : '¿Deseas eliminar este documento?'
            ))">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    Eliminar
                </button>
            </form>

            <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="detail-list">
                <div>
                    <div class="detail-label">Número</div>
                    <div class="detail-value">{{ $document->number ?: 'Sin número' }}</div>
                </div>

                <div>
                    <div class="detail-label">Tipo</div>
                    <div class="detail-value">{{ $document->kind }}</div>
                </div>

                <div>
                    <div class="detail-label">Estado</div>
                    <div class="detail-value">{{ $document->status }}</div>
                </div>

                <div>
                    <div class="detail-label">Contacto</div>
                    <div class="detail-value">{{ $document->party?->name ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Orden asociada</div>
                    <div class="detail-value">
                        @if ($document->order)
                            <a href="{{ route('orders.show', $document->order) }}">
                                {{ $document->order->number ?: 'Sin número' }}
                            </a>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <div>
                    <div class="detail-label">Fecha de emisión</div>
                    <div class="detail-value">{{ $document->issued_at?->format('d/m/Y') ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Fecha de vencimiento</div>
                    <div class="detail-value">{{ $document->due_at?->format('d/m/Y') ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Moneda</div>
                    <div class="detail-value">{{ $document->currency_code ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Creado por</div>
                    <div class="detail-value">{{ $document->creator?->name ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Actualizado por</div>
                    <div class="detail-value">{{ $document->updater?->name ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Subtotal</div>
                    <div class="detail-value">${{ number_format($document->subtotal, 2, ',', '.') }}</div>
                </div>

                <div>
                    <div class="detail-label">Impuestos</div>
                    <div class="detail-value">${{ number_format($document->tax_total, 2, ',', '.') }}</div>
                </div>

                <div>
                    <div class="detail-label">Total</div>
                    <div class="detail-value">${{ number_format($document->total, 2, ',', '.') }}</div>
                </div>

                <div>
                    <div class="detail-label">Notas</div>
                    <div class="detail-value">{{ $document->notes ?: '—' }}</div>
                </div>
            </div>
        </x-card>

        <x-page-header title="Ítems del documento">
            <a href="{{ route('documents.items.create', $document) }}" class="btn btn-primary">
                Agregar ítem
            </a>
        </x-page-header>

        <x-card class="list-card">
            @if ($document->items->count())
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Posición</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <th>Precio unitario</th>
                                <th>Total línea</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($document->items->sortBy('position') as $item)
                                <tr>
                                    <td>{{ $item->position }}</td>
                                    <td>{{ $item->kind }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
                                    <td>${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                    <td>${{ number_format($item->line_total, 2, ',', '.') }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">

                                            <a href="{{ route('documents.items.edit', [$document, $item]) }}"
                                                class="btn btn-secondary btn-sm" title="Editar ítem" aria-label="Editar ítem">
                                                <x-icons.pencil />
                                            </a>

                                            <form method="POST" action="{{ route('documents.items.destroy', [$document, $item]) }}"
                                                class="d-inline" onsubmit="return confirm('¿Deseas eliminar este ítem?')">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-danger btn-sm" title="Eliminar ítem"
                                                    aria-label="Eliminar ítem">
                                                    <x-icons.trash />
                                                </button>

                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mb-0">No hay ítems cargados en este documento.</p>
            @endif
        </x-card>

    </x-page>
@endsection