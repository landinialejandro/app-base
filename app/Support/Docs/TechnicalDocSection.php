<?php

// FILE: app/Support/Docs/TechnicalDocSection.php | V2

namespace App\Support\Docs;

class TechnicalDocSection
{
    public function __construct(
        public string $name,
        public string $anchor,
        public string $html,
        public string $rawBody,
    ) {}
}
