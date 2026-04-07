<?php

// FILE: app/Support/Docs/TechnicalDoc.php | V1

namespace App\Support\Docs;

class TechnicalDoc
{
    /**
     * @param  array<int, TechnicalDocSection>  $sections
     */
    public function __construct(
        public string $title,
        public string $slug,
        public string $version,
        public string $sourcePath,
        public array $sections = [],
    ) {}
}
