<?php

// FILE: tools/aplicar-actualizaciones-docs.php | V9

declare(strict_types=1);

require_once __DIR__.'/lib/ConsoleOutput.php';
require_once __DIR__.'/lib/ProjectPaths.php';
require_once __DIR__.'/lib/ToolLog.php';

ProjectPaths::chdirRoot();

$baseDir = ProjectPaths::documentos();
$backupDir = ProjectPaths::baks();
$logPath = ProjectPaths::logPath('docs-updates.log');

$console = new ConsoleOutput;

$input = trim(stream_get_contents(STDIN));

if ($input === '') {
    $console->error('No se recibió contenido por STDIN.');
    exit(1);
}

$documents = indexDocumentsBySlug($baseDir);

if (empty($documents)) {
    $console->error('No se encontraron documentos indexables.');
    exit(1);
}

$operations = parseOperations($input);

if (empty($operations)) {
    $console->error('No se encontraron bloques válidos de reemplazo o inserción.');
    exit(1);
}

$operationsByDocument = [];

foreach ($operations as $operation) {
    $documentSlug = $operation['document_slug'];

    if (! isset($documents[$documentSlug])) {
        $console->error("Documento no reconocido por slug: {$documentSlug}");

        continue;
    }

    $operationsByDocument[$documentSlug][] = $operation;
}

if (empty($operationsByDocument)) {
    $console->warn('No hay cambios aplicables.');
    exit(1);
}

ProjectPaths::ensureDirectory($backupDir);

$totalApplied = 0;

foreach ($operationsByDocument as $documentSlug => $operationsForDocument) {
    $fileName = $documents[$documentSlug];
    $filePath = $baseDir.DIRECTORY_SEPARATOR.$fileName;

    if (! file_exists($filePath)) {
        $console->error("[{$documentSlug}] No existe el archivo del documento: {$filePath}");

        continue;
    }

    $content = file_get_contents($filePath);

    if ($content === false) {
        $console->error("[{$documentSlug}] No se pudo leer el archivo: {$filePath}");

        continue;
    }

    $originalContent = $content;
    $applied = 0;

    foreach ($operationsForDocument as $operation) {
        $availableSections = listSectionNames($content);

        if ($operation['type'] === 'replace') {
            $sectionName = $operation['section_name'];
            $block = $operation['block'];

            $matches = findSectionBlocksByName($content, $sectionName);
            $matchCount = count($matches);

            if ($matchCount === 0) {
                $console->error("[{$documentSlug}] No se encontró la sección: {$sectionName}");
                writeClosestSectionHint($console, $documentSlug, $sectionName, $availableSections);

                continue;
            }

            if ($matchCount > 1) {
                $console->error("[{$documentSlug}] Se encontraron múltiples secciones con el mismo nombre: {$sectionName}");

                continue;
            }

            $content = preg_replace(
                '/<<SECTION:\s*'.preg_quote($sectionName, '/').'\s*>>.*?<<END SECTION>>/su',
                $block,
                $content,
                1,
                $count
            );

            if ($count > 0) {
                $applied++;
                $console->ok("[{$documentSlug}] Sección reemplazada: {$sectionName}");
            }

            continue;
        }

        if ($operation['type'] === 'insert_after') {
            $anchorSectionName = $operation['anchor_section_name'];
            $newSectionName = $operation['section_name'];
            $block = $operation['block'];

            $existingNewSections = findSectionBlocksByName($content, $newSectionName);

            if (count($existingNewSections) > 0) {
                $console->warn("[{$documentSlug}] Ya existe la sección a insertar: {$newSectionName}");

                continue;
            }

            $anchorMatches = findSectionBlocksByName($content, $anchorSectionName);
            $anchorCount = count($anchorMatches);

            if ($anchorCount === 0) {
                $console->error("[{$documentSlug}] No se encontró la sección ancla: {$anchorSectionName}");
                writeClosestSectionHint($console, $documentSlug, $anchorSectionName, $availableSections);

                continue;
            }

            if ($anchorCount > 1) {
                $console->error("[{$documentSlug}] Se encontraron múltiples secciones ancla con el mismo nombre: {$anchorSectionName}");

                continue;
            }

            $anchorBlock = $anchorMatches[0];
            $replacement = $anchorBlock."\n\n".$block;

            $content = preg_replace(
                '/'.preg_quote($anchorBlock, '/').'/su',
                $replacement,
                $content,
                1,
                $count
            );

            if ($count > 0) {
                $applied++;
                $console->ok("[{$documentSlug}] Sección insertada: {$newSectionName}");
            }

            continue;
        }
    }

    if ($applied === 0) {
        $console->warn("[{$documentSlug}] Sin cambios aplicados.");

        continue;
    }

    $backupPath = $backupDir.DIRECTORY_SEPARATOR.$fileName.'.bak';

    file_put_contents($backupPath, $originalContent);

    if (file_put_contents($filePath, $content) === false) {
        $console->error("[{$documentSlug}] Error al escribir archivo.");

        continue;
    }

    $totalApplied += $applied;

    $console->ok("[{$documentSlug}] OK. Secciones aplicadas: {$applied}");
    appendDocsLog($logPath, $documentSlug, $applied);
}

if ($totalApplied === 0) {
    $console->warn('Proceso finalizado sin cambios aplicados.');
    exit(1);
}

$console->ok("Proceso finalizado. Total de secciones aplicadas: {$totalApplied}");
exit(0);

function appendDocsLog(string $logPath, string $documentSlug, int $sections): void
{
    ToolLog::append(
        $logPath,
        ['DOCUMENTO', 'FECHA', 'SECCIONES', 'USUARIO', 'HOST'],
        [
            $documentSlug,
            ToolLog::now(),
            (string) $sections,
            ToolLog::currentUser(),
            ToolLog::currentHost(),
        ]
    );
}

function parseOperations(string $input): array
{
    $operations = [];

    preg_match_all(
        '/REEMPLAZAR EN:\s*\[?([a-z0-9_]+)\]?\s*(<<SECTION:\s*.*?>>.*?<<END SECTION>>)/su',
        $input,
        $replaceMatches,
        PREG_SET_ORDER
    );

    foreach ($replaceMatches as $match) {
        $sectionName = extractSectionNameFromHeader($match[2]);

        if ($sectionName === null) {
            continue;
        }

        $operations[] = [
            'type' => 'replace',
            'document_slug' => strtolower(trim($match[1])),
            'section_name' => $sectionName,
            'block' => trim($match[2]),
        ];
    }

    preg_match_all(
        '/NUEVA SECCIÓN PROPUESTA EN:\s*\[?([a-z0-9_]+)\]?\s*UBICAR DESPUÉS DE:\s*(<<SECTION:\s*.*?>>)\s*(<<SECTION:\s*.*?>>.*?<<END SECTION>>)/su',
        $input,
        $insertMatches,
        PREG_SET_ORDER
    );

    foreach ($insertMatches as $match) {
        $anchorName = extractSectionNameFromHeader($match[2]);
        $newSectionName = extractSectionNameFromHeader($match[3]);

        if ($anchorName === null || $newSectionName === null) {
            continue;
        }

        $operations[] = [
            'type' => 'insert_after',
            'document_slug' => strtolower(trim($match[1])),
            'anchor_section_name' => $anchorName,
            'section_name' => $newSectionName,
            'block' => trim($match[3]),
        ];
    }

    return $operations;
}

function extractSectionNameFromHeader(string $header): ?string
{
    if (! preg_match('/<<SECTION:\s*(.*?)>>/u', $header, $match)) {
        return null;
    }

    return trim($match[1]);
}

function listSectionNames(string $content): array
{
    if (! preg_match_all('/<<SECTION:\s*(.*?)>>/u', $content, $matches)) {
        return [];
    }

    $sections = array_map(static fn ($name) => trim($name), $matches[1] ?? []);

    return array_values(array_unique($sections));
}

function findSectionBlocksByName(string $content, string $sectionName): array
{
    $pattern = '/<<SECTION:\s*'.preg_quote($sectionName, '/').'\s*>>.*?<<END SECTION>>/su';

    if (! preg_match_all($pattern, $content, $matches)) {
        return [];
    }

    return $matches[0] ?? [];
}

function writeClosestSectionHint(ConsoleOutput $console, string $documentSlug, string $target, array $availableSections): void
{
    $closest = findClosestSections($target, $availableSections, 3);

    if (empty($closest)) {
        return;
    }

    $console->info("[{$documentSlug}] Quizás quiso decir: ".implode(' | ', $closest));
}

function findClosestSections(string $target, array $sections, int $limit = 3): array
{
    $targetNorm = normalizeSectionName($target);
    $scored = [];

    foreach ($sections as $section) {
        $sectionNorm = normalizeSectionName($section);

        similar_text($targetNorm, $sectionNorm, $percent);
        $distance = levenshtein($targetNorm, $sectionNorm);

        if (
            str_contains($sectionNorm, $targetNorm) ||
            str_contains($targetNorm, $sectionNorm) ||
            $percent >= 45 ||
            $distance <= 12
        ) {
            $scored[] = [
                'name' => $section,
                'percent' => $percent,
                'distance' => $distance,
            ];
        }
    }

    usort($scored, static function ($a, $b) {
        if ($a['distance'] === $b['distance']) {
            return $b['percent'] <=> $a['percent'];
        }

        return $a['distance'] <=> $b['distance'];
    });

    $names = array_map(static fn ($row) => $row['name'], array_slice($scored, 0, $limit));

    return array_values(array_unique($names));
}

function normalizeSectionName(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = preg_replace('/[🆕•·▪️\-–—_:()]/u', ' ', $value);
    $value = preg_replace('/\s+/u', ' ', $value);

    return trim($value);
}

function indexDocumentsBySlug(string $baseDir): array
{
    $documents = [];

    $files = scandir($baseDir);

    if ($files === false) {
        return $documents;
    }

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $fullPath = $baseDir.DIRECTORY_SEPARATOR.$file;

        if (is_dir($fullPath)) {
            continue;
        }

        if (! str_ends_with($file, '.txt')) {
            continue;
        }

        $content = file_get_contents($fullPath);

        if ($content === false) {
            continue;
        }

        if (! preg_match('/<<SECTION:\s*METADATOS\s*>>(.*?)<<END SECTION>>/su', $content, $metaMatch)) {
            continue;
        }

        $metadataBlock = $metaMatch[1];

        if (! preg_match('/^DOC_SLUG:\s*([a-z0-9_]+)$/mu', $metadataBlock, $slugMatch)) {
            continue;
        }

        $slug = trim($slugMatch[1]);

        $documents[$slug] = $file;
    }

    return $documents;
}
