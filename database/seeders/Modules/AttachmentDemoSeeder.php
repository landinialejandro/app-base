<?php

// FILE: database/seeders/Modules/AttachmentDemoSeeder.php | V1

namespace Database\Seeders\Modules;

use App\Models\Document;
use App\Models\Membership;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentDemoSeeder extends Seeder
{
    private const DISK = 'local';

    /**
     * Ajustar estos slugs si AttachmentCatalog del proyecto usa otros valores.
     */
    private const ORDER_KINDS = [
        'evidence',
        'support',
    ];

    private const DOCUMENT_KINDS = [
        'support',
        'report',
    ];

    public function run(): void
    {
        $this->cleanupPreviousSeed();

        Tenant::query()
            ->orderBy('slug')
            ->get()
            ->each(function (Tenant $tenant): void {
                $uploaderUserId = Membership::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->orderBy('is_owner', 'desc')
                    ->orderBy('id')
                    ->value('user_id');

                if (! $uploaderUserId) {
                    return;
                }

                $orders = Order::query()
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('id')
                    ->limit(2)
                    ->get();

                $documents = Document::query()
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('id')
                    ->limit(2)
                    ->get();

                foreach ($orders as $index => $order) {
                    $this->createTextAttachment(
                        tenant: $tenant,
                        attachableType: Order::class,
                        attachableId: $order->id,
                        uploadedByUserId: $uploaderUserId,
                        kind: self::ORDER_KINDS[0],
                        title: '[seed] Orden - evidencia',
                        description: 'Adjunto de prueba generado por AttachmentDemoSeeder para orden.',
                        sortOrder: 1,
                        contextLabel: 'order-'.$order->id.'-a'
                    );

                    $this->createTextAttachment(
                        tenant: $tenant,
                        attachableType: Order::class,
                        attachableId: $order->id,
                        uploadedByUserId: $uploaderUserId,
                        kind: self::ORDER_KINDS[1] ?? self::ORDER_KINDS[0],
                        title: '[seed] Orden - soporte',
                        description: 'Segundo adjunto de prueba generado por AttachmentDemoSeeder para orden.',
                        sortOrder: 2,
                        contextLabel: 'order-'.$order->id.'-b'
                    );
                }

                foreach ($documents as $index => $document) {
                    $this->createTextAttachment(
                        tenant: $tenant,
                        attachableType: Document::class,
                        attachableId: $document->id,
                        uploadedByUserId: $uploaderUserId,
                        kind: self::DOCUMENT_KINDS[0],
                        title: '[seed] Documento - soporte',
                        description: 'Adjunto de prueba generado por AttachmentDemoSeeder para documento.',
                        sortOrder: 1,
                        contextLabel: 'document-'.$document->id.'-a'
                    );

                    $this->createTextAttachment(
                        tenant: $tenant,
                        attachableType: Document::class,
                        attachableId: $document->id,
                        uploadedByUserId: $uploaderUserId,
                        kind: self::DOCUMENT_KINDS[1] ?? self::DOCUMENT_KINDS[0],
                        title: '[seed] Documento - informe',
                        description: 'Segundo adjunto de prueba generado por AttachmentDemoSeeder para documento.',
                        sortOrder: 2,
                        contextLabel: 'document-'.$document->id.'-b'
                    );
                }
            });
    }

    private function cleanupPreviousSeed(): void
    {
        $rows = DB::table('attachments')
            ->select('id', 'disk', 'directory', 'stored_name')
            ->where('title', 'like', '[seed]%')
            ->get();

        foreach ($rows as $row) {
            $path = trim(($row->directory ?? ''), '/').'/'.$row->stored_name;
            $path = ltrim($path, '/');

            if (! empty($row->disk) && ! empty($row->stored_name)) {
                Storage::disk($row->disk)->delete($path);
            }
        }

        DB::table('attachments')
            ->where('title', 'like', '[seed]%')
            ->delete();
    }

    private function createTextAttachment(
        Tenant $tenant,
        string $attachableType,
        int|string $attachableId,
        int $uploadedByUserId,
        string $kind,
        string $title,
        string $description,
        int $sortOrder,
        string $contextLabel
    ): void {
        $directory = 'testing/attachments/'.$tenant->slug;
        $storedName = Str::uuid()->toString().'.txt';
        $originalName = $contextLabel.'.txt';

        $content = $this->buildTextContent(
            tenant: $tenant,
            attachableType: $attachableType,
            attachableId: $attachableId,
            kind: $kind,
            title: $title,
            description: $description,
            contextLabel: $contextLabel
        );

        Storage::disk(self::DISK)->put(
            $directory.'/'.$storedName,
            $content
        );

        DB::table('attachments')->insert([
            'tenant_id' => $tenant->id,
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId,
            'uploaded_by_user_id' => $uploadedByUserId,
            'disk' => self::DISK,
            'directory' => $directory,
            'stored_name' => $storedName,
            'original_name' => $originalName,
            'extension' => 'txt',
            'mime_type' => 'text/plain',
            'size_bytes' => strlen($content),
            'checksum_sha256' => hash('sha256', $content),
            'kind' => $kind,
            'category' => null,
            'is_image' => false,
            'sort_order' => $sortOrder,
            'title' => $title,
            'description' => $description,
            'tags_json' => json_encode([
                'seed',
                'demo',
                'attachments',
                class_basename($attachableType),
            ], JSON_UNESCAPED_UNICODE),
            'visibility' => 'private',
            'meta_json' => json_encode([
                'seed' => 'AttachmentDemoSeeder',
                'context' => $contextLabel,
            ], JSON_UNESCAPED_UNICODE),
            'extracted_text' => null,
            'analysis_status' => null,
            'analyzed_at' => null,
            'analysis_version' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buildTextContent(
        Tenant $tenant,
        string $attachableType,
        int|string $attachableId,
        string $kind,
        string $title,
        string $description,
        string $contextLabel
    ): string {
        return implode(PHP_EOL, [
            'Seeder: AttachmentDemoSeeder',
            'Tenant: '.$tenant->slug,
            'Attachable: '.$attachableType,
            'Attachable ID: '.$attachableId,
            'Kind: '.$kind,
            'Title: '.$title,
            'Description: '.$description,
            'Context: '.$contextLabel,
            'Generated at: '.now()->toDateTimeString(),
            '',
            'Contenido de prueba para validar carga, listado, preview y descarga de adjuntos.',
        ]);
    }
}
