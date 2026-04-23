<?php

// FILE: app/Support/Ui/ButtonToolStyle.php | V1

namespace App\Support\Ui;

class ButtonToolStyle
{
    public static function classes(?string $variant = 'secondary'): string
    {
        return match ($variant) {
            'danger' => 'btn btn-danger btn-icon btn-tool',
            'primary' => 'btn btn-primary btn-icon btn-tool',
            default => 'btn btn-secondary btn-icon btn-tool',
        };
    }
}