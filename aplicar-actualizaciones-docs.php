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

$pattern = '/REEMPLAZAR EN:\s*([a-z0-9_]+)\R\R(<<SECTION:\s*.*?>>.*?<<END SECTION>>)/su';

if (! preg_match_all($pattern, $input, $matches, PREG_SET_ORDER)) {
    fwrite(STDERR, "No se encontraron bloques válidos con formato 'REEMPLAZAR EN: slug'.\n");
    exit(1);
}

$changesByDocument = [];

foreach ($matches as $match) {
    $documentSlug = trim($match[1]);
    $sectionBlock = trim($match[2]);

    if (! isset($documents[$documentSlug])) {
        fwrite(STDERR, "Documento no reconocido por slug: {$documentSlug}\n");

        continue;
    }

    if (! preg_match('/<<SECTION:\s*(.*?)>>/u', $sectionBlock, $sectionMatch)) {
        fwrite(STDERR, "Bloque sin nombre de sección válido en documento {$documentSlug}\n");

        continue;
    }

    $sectionName = trim($sectionMatch[1]);

    $changesByDocument[$documentSlug][] = [
        'section_name' => $sectionName,
        'block' => $sectionBlock,
    ];
}

if (empty($changesByDocument)) {
    fwrite(STDERR, "No hay cambios aplicables.\n");
    exit(1);
}

if (! is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

foreach ($changesByDocument as $documentSlug => $changes) {
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

    foreach ($changes as $change) {
        $sectionName = $change['section_name'];
        $block = $change['block'];

        $sectionPattern = '/<<SECTION:\s*'.preg_quote($sectionName, '/').'\s*>>.*?<<END SECTION>>/su';

        if (! preg_match($sectionPattern, $content)) {
            fwrite(STDERR, "[{$documentSlug}] No se encontró la sección: {$sectionName}\n");

            continue;
        }

        $content = preg_replace($sectionPattern, $block, $content, 1);
        $applied++;
    }

    if ($content !== $originalContent) {
        $backupPath = $backupDir.DIRECTORY_SEPARATOR.$fileName.'.bak';

        file_put_contents($backupPath, $originalContent);

        if (file_put_contents($filePath, $content) === false) {
            fwrite(STDERR, "No se pudo guardar el archivo: {$filePath}\n");

            continue;
        }

        echo "[OK] {$documentSlug}: {$applied} sección(es) actualizada(s)\n";
        echo "     Archivo: {$fileName}\n";
        echo "     Backup: {$backupPath}\n";
    } else {
        echo "[SIN CAMBIOS] {$documentSlug}\n";
    }
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
