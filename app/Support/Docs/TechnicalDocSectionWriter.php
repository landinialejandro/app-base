<?php

// FILE: app/Support/Docs/TechnicalDocSectionWriter.php | V1

namespace App\Support\Docs;

use Illuminate\Support\Facades\File;
use RuntimeException;

class TechnicalDocSectionWriter
{
    public function __construct(
        protected TechnicalDocRepository $documents
    ) {}

    public function replaceSection(string $slug, string $sectionName, string $newBody): void
    {
        $document = $this->documents->findBySlug($slug);

        if (! $document) {
            throw new RuntimeException("No se encontró el documento [$slug].");
        }

        $path = $document->sourcePath;

        if (! File::exists($path)) {
            throw new RuntimeException("No existe el archivo fuente del documento [$slug].");
        }

        $contents = File::get($path);

        $pattern = '/<<SECTION:\s*'.preg_quote($sectionName, '/').'\s*>>\R(.*?)<<END SECTION>>/su';

        $replacement = "<<SECTION: {$sectionName}>>\n".trim($newBody)."\n<<END SECTION>>";

        $updated = preg_replace($pattern, $replacement, $contents, 1, $count);

        if ($updated === null || $count !== 1) {
            throw new RuntimeException("No se pudo reemplazar la sección [{$sectionName}] del documento [{$slug}].");
        }

        File::put($path, $updated);
    }
}
