<?php

// FILE: app/Http/Controllers/TechnicalDocController.php | V4

namespace App\Http\Controllers;

use App\Support\Docs\TechnicalDocRepository;
use App\Support\Docs\TechnicalDocSectionWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TechnicalDocController extends Controller
{
    public function __construct(
        protected TechnicalDocRepository $documents
    ) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        return view('docs.index', [
            'documents' => $this->documents->search($search),
            'search' => $search,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $document = $this->documents->findBySlug($slug);

        abort_unless($document !== null, 404);

        $sectionSearch = trim((string) $request->query('section_q', ''));

        $visibleSections = $document->sections;

        if ($sectionSearch !== '') {
            $needle = Str::lower($sectionSearch);

            $visibleSections = array_values(array_filter(
                $document->sections,
                fn ($section) => Str::contains(Str::lower($section->name), $needle)
                    || Str::contains(Str::lower(strip_tags($section->html)), $needle)
            ));
        }

        return view('docs.show', [
            'document' => $document,
            'documents' => $this->documents->all(),
            'visibleSections' => $visibleSections,
            'sectionSearch' => $sectionSearch,
            'docsEditorEnabled' => $this->docsEditorEnabled(),
        ]);
    }

    public function updateSection(
        Request $request,
        TechnicalDocSectionWriter $writer,
        string $slug,
        string $section
    ) {
        abort_unless($this->docsEditorEnabled(), 404);

        $document = $this->documents->findBySlug($slug);

        abort_unless($document !== null, 404);

        $targetSection = collect($document->sections)
            ->first(fn ($item) => $item->anchor === $section);

        abort_unless($targetSection !== null, 404);
        abort_if($targetSection->name === 'METADATOS', 422, 'La sección METADATOS no se puede editar desde esta interfaz.');

        $data = $request->validate([
            'section_body' => ['required', 'string'],
            'section_q' => ['nullable', 'string'],
        ]);

        $body = trim((string) $data['section_body']);

        if (str_contains($body, '<<SECTION:') || str_contains($body, '<<END SECTION>>')) {
            return redirect()
                ->route('docs.show', [
                    'slug' => $slug,
                    'section_q' => $request->input('section_q'),
                ])
                ->withErrors([
                    'section_body' => 'No incluyas delimitadores de sección dentro del contenido.',
                ])
                ->withInput();
        }

        try {
            $writer->replaceSection($slug, $targetSection->name, $body);
        } catch (\Throwable $e) {
            return redirect()
                ->route('docs.show', [
                    'slug' => $slug,
                    'section_q' => $request->input('section_q'),
                    'focus_section' => $targetSection->anchor,
                ])
                ->withErrors([
                    'section_body' => 'No se pudo guardar la sección. Verificá permisos de escritura sobre la carpeta documentos.',
                ])
                ->withInput();
        }

        $query = [
            'slug' => $slug,
            'focus_section' => $targetSection->anchor,
        ];

        if ($request->filled('section_q')) {
            $query['section_q'] = $request->string('section_q')->toString();
        }

        return redirect()
            ->route('docs.show', $query)
            ->with('success', 'Sección actualizada correctamente.');
    }

    protected function docsEditorEnabled(): bool
    {
        return filter_var(env('DOCS_EDITOR_ENABLED', false), FILTER_VALIDATE_BOOL);
    }
}
