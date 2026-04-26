<?php

// FILE: app/Support/Catalogs/DocumentItemCatalog.php | V1

namespace App\Support\Catalogs;

use App\Support\Catalogs\Concerns\HasLineItemStatuses;

class DocumentItemCatalog extends BaseCatalog
{
    use HasLineItemStatuses;
}