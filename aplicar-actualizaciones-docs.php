<?php

$documents = [
    'CONTEXTO-FIJO-DEL-PROYECTO – app-base' => 'CONTEXTO-FIJO-DEL-PROYECTO-–-app-base.txt',
    'CONVENCIONES-DEL-PROYECTO – app-base' => 'CONVENCIONES-DEL-PROYECTO-–-app-base.txt',
    'CHECKLIST OFICIAL PARA CREAR MÓDULOS NUEVOS' => 'Checklist-oficial-para-crear-módulos-nuevos.txt',
    'MAPA-DE-ENTIDADES – app-base' => 'MAPA-DE-ENTIDADES – app-base.txt',
    'MAPA-DE-NAVEGACION-Y-RELACIONES – app-base' => 'MAPA-DE-NAVEGACION-Y-RELACIONES – app-base.txt',
    'BASE-VISUAL-DEL-PROYECTO – app-base' => 'BASE-VISUAL-DEL-PROYECTO – app-base.txt',
    'TODO-DEL-PROYECTO – app-base' => 'TODO-DEL-PROYECTO – app-base.txt',
    'DEPLOY-Y-COMPATIBILIDAD-DEL-PROYECTO – app-base' => 'DEPLOY-Y-COMPATIBILIDAD-DEL-PROYECTO – app-base.txt',
    'PLANTILLA MÍNIMA PARA INICIAR NUEVOS CHATS DEL PROYECTO – app-base' => 'Plantilla-mínima-para-iniciar-nuevos-chats-del-proyecto.txt',
];

$baseDir = __DIR__.DIRECTORY_SEPARATOR.'documentos';
// Definimos la subcarpeta de backups
$backupDir = $baseDir.DIRECTORY_SEPARATOR.'baks';

$input = stream_get_contents(STDIN);
$input = trim($input);

if ($input === '') {
    fwrite(STDERR, "No se recibió contenido por stdin.\n");
    exit(1);
}

$pattern = '/REEMPLAZAR EN:\s*(.+?)\R\R(<<SECTION:\s*.*?>>.*?<<END SECTION>>)/su';

if (! preg_match_all($pattern, $input, $matches, PREG_SET_ORDER)) {
    fwrite(STDERR, "No se encontraron bloques válidos con formato 'REEMPLAZAR EN: ...'.\n");
    exit(1);
}

$changesByDocument = [];

foreach ($matches as $match) {
    $documentName = trim($match[1]);
    $sectionBlock = trim($match[2]);

    if (! isset($documents[$documentName])) {
        fwrite(STDERR, "Documento no reconocido: {$documentName}\n");

        continue;
    }

    if (! preg_match('/<<SECTION:\s*(.*?)>>/u', $sectionBlock, $sectionMatch)) {
        fwrite(STDERR, "Bloque sin nombre de sección válido en documento {$documentName}\n");

        continue;
    }

    $sectionName = trim($sectionMatch[1]);

    $changesByDocument[$documentName][] = [
        'section_name' => $sectionName,
        'block' => $sectionBlock,
    ];
}

if (empty($changesByDocument)) {
    fwrite(STDERR, "No hay cambios aplicables.\n");
    exit(1);
}

// Nos aseguramos de que la carpeta de backups exista
if (! is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

foreach ($changesByDocument as $documentName => $changes) {
    $fileName = $documents[$documentName];
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
            fwrite(STDERR, "[{$documentName}] No se encontró la sección: {$sectionName}\n");

            continue;
        }

        $content = preg_replace($sectionPattern, $block, $content, 1);
        $applied++;
    }

    if ($content !== $originalContent) {
        // Modificado para guardar en la subcarpeta baks
        $backupPath = $backupDir.DIRECTORY_SEPARATOR.$fileName.'.bak';

        file_put_contents($backupPath, $originalContent);

        if (file_put_contents($filePath, $content) === false) {
            fwrite(STDERR, "No se pudo guardar el archivo: {$filePath}\n");

            continue;
        }

        echo "[OK] {$documentName}: {$applied} sección(es) actualizada(s)\n";
        echo "     Backup: {$backupPath}\n";
    } else {
        echo "[SIN CAMBIOS] {$documentName}\n";
    }
}
