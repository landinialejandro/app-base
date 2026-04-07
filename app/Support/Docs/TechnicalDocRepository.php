<?php

// FILE: app/Support/Docs/TechnicalDocRepository.php | V2

namespace App\Support\Docs;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class TechnicalDocRepository
{
    protected ?array $cache = null;

    public function __construct(
        protected ?string $basePath = null,
        protected ?TechnicalDocParser $parser = null,
    ) {
        $this->basePath = $this->basePath ?: base_path('documentos');
        $this->parser = $this->parser ?: app(TechnicalDocParser::class);
    }

    /**
     * @return array<int, TechnicalDoc>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! File::exists($this->basePath)) {
            return $this->cache = [];
        }

        $documentsBySlug = [];

        foreach (File::allFiles($this->basePath) as $file) {
            $realPath = $file->getRealPath();

            if (! $realPath) {
                continue;
            }

            if (! $this->isActiveDocument($realPath)) {
                continue;
            }

            $document = $this->parser->parse(
                contents: File::get($realPath),
                sourcePath: $realPath,
            );

            if (isset($documentsBySlug[$document->slug])) {
                throw new RuntimeException("DOC_SLUG duplicado detectado: [{$document->slug}]");
            }

            $documentsBySlug[$document->slug] = $document;
        }

        $documents = array_values($documentsBySlug);

        usort($documents, fn (TechnicalDoc $a, TechnicalDoc $b) => strcasecmp($a->title, $b->title));

        return $this->cache = $documents;
    }

    /**
     * @return array<int, TechnicalDoc>
     */
    public function search(string $term = ''): array
    {
        $term = trim($term);

        if ($term === '') {
            return $this->all();
        }

        $needle = Str::lower($term);

        return array_values(array_filter($this->all(), function (TechnicalDoc $document) use ($needle) {
            if (Str::contains(Str::lower($document->title), $needle)) {
                return true;
            }

            if (Str::contains(Str::lower($document->slug), $needle)) {
                return true;
            }

            foreach ($document->sections as $section) {
                if (Str::contains(Str::lower($section->name), $needle)) {
                    return true;
                }

                if (Str::contains(Str::lower(strip_tags($section->html)), $needle)) {
                    return true;
                }
            }

            return false;
        }));
    }

    public function findBySlug(string $slug): ?TechnicalDoc
    {
        foreach ($this->all() as $document) {
            if ($document->slug === $slug) {
                return $document;
            }
        }

        return null;
    }

    protected function isActiveDocument(string $path): bool
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $normalizedBase = str_replace('\\', '/', $this->basePath);

        if (! str_starts_with($normalizedPath, $normalizedBase)) {
            return false;
        }

        $relativePath = ltrim(substr($normalizedPath, strlen($normalizedBase)), '/');

        if ($relativePath === '') {
            return false;
        }

        if (str_contains($relativePath, '/baks/') || str_starts_with($relativePath, 'baks/')) {
            return false;
        }

        if (str_contains($relativePath, '/auditoria/') || str_starts_with($relativePath, 'auditoria/')) {
            return false;
        }

        return strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) === 'txt';
    }
}
