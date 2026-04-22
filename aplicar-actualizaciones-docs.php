<?php

// FILE: aplicar-actualizaciones-docs.php | V6
$baseDir = __DIR__.DIRECTORY_SEPARATOR.'documentos';
$backupDir = $baseDir.DIRECTORY_SEPARATOR.'baks';
$logPath = $baseDir.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR.'docs-updates.log';

$input = trim(stream_get_contents(STDIN));

if ($input === '') {
    fwrite(STDERR, "No se recibió contenido por stdin.\n");
    exit(1);
}

$documents = indexDocumentsBySlug($baseDir);

if (empty($documents)) {
    fwrite(STDERR, "No se encontraron documentos indexables.\n");
    exit(1);
}

$operations = parseOperations($input);

if (empty($operations)) {
    fwrite(STDERR, "No se encontraron bloques válidos de reemplazo o inserción.\n");
    exit(1);
}

$operationsByDocument = [];

foreach ($operations as $operation) {
    $documentSlug = $operation['document_slug'];

    if (! isset($documents[$documentSlug])) {
        fwrite(STDERR, "Documento no reconocido por slug: {$documentSlug}\n");

        continue;
    }

    $operationsByDocument[$documentSlug][] = $operation;
}

if (empty($operationsByDocument)) {
    fwrite(STDERR, "No hay cambios aplicables.\n");
    exit(1);
}

if (! is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

foreach ($operationsByDocument as $documentSlug => $operationsForDocument) {
    $fileName = $documents[$documentSlug];
    $filePath = $baseDir.DIRECTORY_SEPARATOR.$fileName;

    if (! file_exists($filePath)) {
        fwrite(STDERR, "No existe el archivo del documento: {$filePath}\n");

        continue;
    }

    $content = file_get_contents($filePath);

    if ($content === false) {
        fwrite(STDERR, "No se pudo leer el archivo: {$filePath}\n");

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
                fwrite(STDERR, "[{$documentSlug}] No se encontró la sección: {$sectionName}\n");
                writeClosestSectionHint($documentSlug, $sectionName, $availableSections);

                continue;
            }

            if ($matchCount > 1) {
                fwrite(STDERR, "[{$documentSlug}] Se encontraron múltiples secciones con el mismo nombre: {$sectionName}\n");

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
            }

            continue;
        }

        if ($operation['type'] === 'insert_after') {
            $anchorSectionName = $operation['anchor_section_name'];
            $newSectionName = $operation['section_name'];
            $block = $operation['block'];

            $existingNewSections = findSectionBlocksByName($content, $newSectionName);

            if (count($existingNewSections) > 0) {
                fwrite(STDERR, "[{$documentSlug}] Ya existe la sección a insertar: {$newSectionName}\n");

                continue;
            }

            $anchorMatches = findSectionBlocksByName($content, $anchorSectionName);
            $anchorCount = count($anchorMatches);

            if ($anchorCount === 0) {
                fwrite(STDERR, "[{$documentSlug}] No se encontró la sección ancla: {$anchorSectionName}\n");
                writeClosestSectionHint($documentSlug, $anchorSectionName, $availableSections);

                continue;
            }

            if ($anchorCount > 1) {
                fwrite(STDERR, "[{$documentSlug}] Se encontraron múltiples secciones ancla con el mismo nombre: {$anchorSectionName}\n");

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
            }

            continue;
        }
    }

    if ($applied === 0) {
        fwrite(STDERR, "[{$documentSlug}] Sin cambios aplicados.\n");

        continue;
    }

    $timestamp = date('Ymd_His');
    $backupPath = $backupDir.DIRECTORY_SEPARATOR.$timestamp.'_'.$fileName;

    file_put_contents($backupPath, $originalContent);

    if (file_put_contents($filePath, $content) === false) {
        fwrite(STDERR, "[{$documentSlug}] Error al escribir archivo.\n");

        continue;
    }

    fwrite(STDERR, "[{$documentSlug}] OK. Secciones aplicadas: {$applied}\n");

    appendDocsLog($logPath, $documentSlug, $applied);
}

function appendDocsLog(string $logPath, string $documentSlug, int $sections): void
{
    $logDir = dirname($logPath);

    if (! is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    $isNewFile = ! file_exists($logPath);

    $user = get_current_user();
    $host = php_uname('n');
    $date = date('Y-m-d H:i:s');

    $lines = '';

    if ($isNewFile) {
        $lines .= "DOCUMENTO | FECHA | SECCIONES | USUARIO | HOST\n";
    }

    $lines .= "{$documentSlug} | {$date} | {$sections} | {$user} | {$host}\n";

    @file_put_contents($logPath, $lines, FILE_APPEND);
}

function parseOperations(string $input): array
{
    $operations = [];

    preg_match_all(
        '/REEMPLAZAR EN:\s*\[([a-z0-9_]+)\]\s*(<<SECTION:\s*.*?>>.*?<<END SECTION>>)/su',
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
            'document_slug' => trim($match[1]),
            'section_name' => $sectionName,
            'block' => trim($match[2]),
        ];
    }

    preg_match_all(
        '/NUEVA SECCIÓN PROPUESTA EN:\s*\[([a-z0-9_]+)\]\s*UBICAR DESPUÉS DE:\s*(<<SECTION:\s*.*?>>)\s*(<<SECTION:\s*.*?>>.*?<<END SECTION>>)/su',
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
            'document_slug' => trim($match[1]),
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

function writeClosestSectionHint(string $documentSlug, string $target, array $availableSections): void
{
    $closest = findClosestSections($target, $availableSections, 3);

    if (empty($closest)) {
        return;
    }

    fwrite(
        STDERR,
        "[{$documentSlug}] Quizás quiso decir: ".implode(' | ', $closest)."\n"
    );
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
