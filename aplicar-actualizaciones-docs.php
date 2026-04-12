<?php

$baseDir = __DIR__.DIRECTORY_SEPARATOR.'documentos';
$backupDir = $baseDir.DIRECTORY_SEPARATOR.'baks';

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
                fwrite(STDERR, "[{$documentSlug}] No se encontró punto de inserción nueva después de la sección: {$anchorSectionName}\n");
                writeClosestSectionHint($documentSlug, $anchorSectionName, $availableSections);

                continue;
            }

            if ($anchorCount > 1) {
                fwrite(STDERR, "[{$documentSlug}] La sección ancla aparece múltiples veces y la inserción sería ambigua: {$anchorSectionName}\n");

                continue;
            }

            $anchorBlock = $anchorMatches[0];
            $replacement = $anchorBlock."\n\n".$block;

            $content = str_replace($anchorBlock, $replacement, $content, $count);

            if ($count > 0) {
                $applied++;
            }

            continue;
        }
    }

    if ($content !== $originalContent) {
        $backupPath = $backupDir.DIRECTORY_SEPARATOR.$fileName.'.bak';

        file_put_contents($backupPath, $originalContent);

        if (file_put_contents($filePath, $content) === false) {
            fwrite(STDERR, "No se pudo guardar el archivo: {$filePath}\n");

            continue;
        }

        echo "[OK] {$documentSlug}: {$applied} operación(es) aplicada(s)\n";
        echo "     Archivo: {$fileName}\n";
        echo "     Backup: {$backupPath}\n";
    } else {
        echo "[SIN CAMBIOS] {$documentSlug}\n";
    }
}

function parseOperations(string $input): array
{
    $operations = [];

    $input = str_replace("\r\n", "\n", $input);
    $input = str_replace("\r", "\n", $input);

    preg_match_all(
        '/^(REEMPLAZAR EN:|NUEVA SECCIÓN PROPUESTA EN:)\s*[^\n]*$/mu',
        $input,
        $matches,
        PREG_OFFSET_CAPTURE
    );

    if (empty($matches[0])) {
        return [];
    }

    $headers = $matches[0];
    $total = count($headers);

    for ($i = 0; $i < $total; $i++) {
        $start = $headers[$i][1];
        $end = ($i + 1 < $total) ? $headers[$i + 1][1] : strlen($input);
        $chunk = trim(substr($input, $start, $end - $start));

        if (str_starts_with($chunk, 'REEMPLAZAR EN:')) {
            $operation = parseReplaceChunk($chunk);

            if ($operation === null) {
                continue;
            }

            $operations[] = $operation;

            continue;
        }

        if (str_starts_with($chunk, 'NUEVA SECCIÓN PROPUESTA EN:')) {
            $operation = parseInsertAfterChunk($chunk);

            if ($operation === null) {
                continue;
            }

            $operations[] = $operation;
        }
    }

    return $operations;
}

function parseReplaceChunk(string $chunk): ?array
{
    if (! preg_match(
        '/^REEMPLAZAR EN:\s*([a-z0-9_]+)\n\n(<<SECTION:\s*.*?>>.*?<<END SECTION>>)\s*$/su',
        $chunk,
        $match
    )) {
        fwrite(STDERR, "Bloque de reemplazo inválido: estructura general no reconocida.\n");

        return null;
    }

    $documentSlug = trim($match[1]);
    $block = trim($match[2]);

    if (! hasSectionVersionLine($block)) {
        fwrite(STDERR, "Bloque de reemplazo inválido en {$documentSlug}: falta SECTION_VERSION.\n");

        return null;
    }

    $sectionName = extractSectionNameFromBlock($block);

    if ($sectionName === null) {
        fwrite(STDERR, "Bloque de reemplazo sin nombre de sección válido en {$documentSlug}\n");

        return null;
    }

    return [
        'type' => 'replace',
        'document_slug' => $documentSlug,
        'section_name' => $sectionName,
        'block' => $block,
    ];
}

function parseInsertAfterChunk(string $chunk): ?array
{
    if (! preg_match('/^NUEVA SECCIÓN PROPUESTA EN:\s*([a-z0-9_]+)\n/su', $chunk, $documentMatch)) {
        fwrite(STDERR, "Bloque de inserción inválido: falta DOC_SLUG válido.\n");

        return null;
    }

    $documentSlug = trim($documentMatch[1]);

    if (! str_contains($chunk, "\n\nUBICAR DESPUÉS DE:\n")) {
        fwrite(STDERR, "Bloque de inserción inválido en {$documentSlug}: falta 'UBICAR DESPUÉS DE:'.\n");

        return null;
    }

    if (! preg_match(
        '/^NUEVA SECCIÓN PROPUESTA EN:\s*([a-z0-9_]+)\n\nUBICAR DESPUÉS DE:\n(<<SECTION:\s*.*?>>)\n\n(<<SECTION:\s*.*?>>.*?<<END SECTION>>)\s*$/su',
        $chunk,
        $match
    )) {
        fwrite(STDERR, "Bloque de inserción inválido en {$documentSlug}: estructura general no reconocida.\n");

        return null;
    }

    $anchorHeader = trim($match[2]);
    $block = trim($match[3]);

    if (! hasSectionVersionLine($block)) {
        fwrite(STDERR, "Bloque de inserción inválido en {$documentSlug}: falta SECTION_VERSION.\n");

        return null;
    }

    $anchorSectionName = extractSectionNameFromHeader($anchorHeader);
    $sectionName = extractSectionNameFromBlock($block);

    if ($anchorSectionName === null) {
        fwrite(STDERR, "Bloque de inserción sin sección ancla válida en {$documentSlug}\n");

        return null;
    }

    if ($sectionName === null) {
        fwrite(STDERR, "Bloque de inserción sin nombre de sección válido en {$documentSlug}\n");

        return null;
    }

    return [
        'type' => 'insert_after',
        'document_slug' => $documentSlug,
        'anchor_section_name' => $anchorSectionName,
        'section_name' => $sectionName,
        'block' => $block,
    ];
}

function hasSectionVersionLine(string $block): bool
{
    return preg_match('/^SECTION_VERSION:\s*\d{5}\s*$/mu', $block) === 1;
}

function extractSectionNameFromBlock(string $block): ?string
{
    if (! preg_match('/<<SECTION:\s*(.*?)>>/u', $block, $match)) {
        return null;
    }

    return trim($match[1]);
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
