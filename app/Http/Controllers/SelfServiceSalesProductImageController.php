<?php

// FILE: app/Http/Controllers/SelfServiceSalesProductImageController.php | V1

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Product;
use App\Models\Tenant;
use App\Support\Shops\ShopPublishedCatalogReader;
use Illuminate\Support\Facades\Storage;

class SelfServiceSalesProductImageController extends Controller
{
    public function show(
        Tenant $tenant,
        string $product,
        string $attachment,
        ShopPublishedCatalogReader $catalogReader
    ) {
        $productModel = Product::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($product)
            ->where('is_active', true)
            ->first();

        if (! $productModel) {
            abort(404);
        }

        $attachmentModel = Attachment::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($attachment)
            ->where('attachable_type', Product::class)
            ->where('attachable_id', $productModel->id)
            ->where('kind', 'shop')
            ->where('is_image', true)
            ->first();

        if (! $attachmentModel) {
            abort(404);
        }

        if (! $catalogReader->visibleItemForProductInActiveShop($tenant, $productModel)) {
            abort(404);
        }

        $diskName = $attachmentModel->disk ?: config('filesystems.default');
        $path = $attachmentModel->full_path;

        if (! $path) {
            abort(404);
        }

        $disk = Storage::disk($diskName);

        if (! $disk->exists($path)) {
            abort(404);
        }

        return response()->file($disk->path($path), [
            'Content-Type' => $attachmentModel->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}