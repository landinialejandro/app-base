<?php

// FILE: app/Support/Docs/TechnicalDocParser.php | V1

namespace App\Support\Docs;

use Illuminate\Support\Str;

class TechnicalDocParser
{
    public function parse(string $contents, string $sourcePath): TechnicalDoc
    {
        $sections = $this->extractSections($contents);

        $metadataBody = $sections['METADATOS'] ?? '';

        $title = $this->extractMetadataValue($metadataBody, 'TÍTULO') ?: $this->fallbackTitleFromPath($sourcePath);
        $slug = $this->extractMetadataValue($metadataBody, 'DOC_SLUG') ?: $this->fallbackSlugFromPath($sourcePath);
        $version = $this->extractMetadataValue($metadataBody, 'DOC_VERSION') ?: '00000';

        $parsedSections = [];

        foreach ($sections as $sectionName => $sectionBody) {
            $parsedSections[] = new TechnicalDocSection(
                name: $sectionName,
                anchor: Str::slug($sectionName),
                html: $this->renderSectionBody($sectionBody),
            );
        }

        return new TechnicalDoc(
            title: $title,
            slug: $slug,
            version: $version,
            sourcePath: $sourcePath,
            sections: $parsedSections,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function extractSections(string $contents): array
    {
        $sections = [];

        preg_match_all('/<<SECTION:\s*(.*?)>>\R(.*?)<<END SECTION>>/su', $contents, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $name = trim((string) ($match[1] ?? ''));
            $body = trim((string) ($match[2] ?? ''));

            if ($name === '') {
                continue;
            }

            $sections[$name] = $body;
        }

        return $sections;
    }

    protected function extractMetadataValue(string $metadataBody, string $key): ?string
    {
        if ($metadataBody === '') {
            return null;
        }

        $pattern = '/^'.preg_quote($key, '/').':\s*(.+)$/mu';

        if (! preg_match($pattern, $metadataBody, $matches)) {
            return null;
        }

        $value = trim((string) ($matches[1] ?? ''));

        return $value !== '' ? $value : null;
    }

    protected function fallbackTitleFromPath(string $sourcePath): string
    {
        return (string) Str::of(pathinfo($sourcePath, PATHINFO_FILENAME))
            ->replace(['_', '-'], ' ')
            ->title();
    }

    protected function fallbackSlugFromPath(string $sourcePath): string
    {
        return (string) Str::of(pathinfo($sourcePath, PATHINFO_FILENAME))
            ->lower()
            ->replace('-', '_');
    }

    protected function renderSectionBody(string $body): string
    {
        if (trim($body) === '') {
            return '';
        }

        $links = [];
        $bodyWithPlaceholders = preg_replace_callback(
            '/<DOC_REF\s+slug="([^"]+)">(.*?)<\/DOC_REF>/su',
            function (array $matches) use (&$links) {
                $slug = trim((string) ($matches[1] ?? ''));
                $label = trim((string) ($matches[2] ?? $slug));

                $placeholder = '__DOC_REF_'.count($links).'__';

                $links[$placeholder] = '<a href="'.e(route('docs.show', ['slug' => $slug])).'">'.e($label).'</a>';

                return $placeholder;
            },
            $body
        );

        $paragraphs = preg_split('/\R{2,}/u', trim((string) $bodyWithPlaceholders)) ?: [];
        $htmlParts = [];

        foreach ($paragraphs as $paragraph) {
            $escaped = e(trim($paragraph));
            $escaped = nl2br($escaped, false);
            $escaped = str_replace(array_keys($links), array_values($links), $escaped);

            $htmlParts[] = '<p>'.$escaped.'</p>';
        }

        return implode("\n", $htmlParts);
    }
}
