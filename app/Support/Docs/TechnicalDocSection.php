<?php

// FILE: app/Support/Docs/TechnicalDocSection.php | V1

namespace App\Support\Docs;

class TechnicalDocSection
{
    public function __construct(
        public string $name,
        public string $anchor,
        public string $html,
    ) {}
}
