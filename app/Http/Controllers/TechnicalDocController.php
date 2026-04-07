<?php

// FILE: app/Http/Controllers/TechnicalDocController.php | V3

namespace App\Http\Controllers;

use App\Support\Docs\TechnicalDocRepository;
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
        ]);
    }
}
