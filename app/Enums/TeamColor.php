<?php

namespace App\Enums;

enum TeamColor: string
{
    case Red = '#EF4444';
    case Orange = '#F97316';
    case Amber = '#F59E0B';
    case Lime = '#84CC16';
    case Emerald = '#10B981';
    case Teal = '#14B8A6';
    case Sky = '#0EA5E9';
    case Blue = '#2563EB';
    case Indigo = '#6366F1';
    case Violet = '#8B5CF6';
    case Pink = '#EC4899';
    case Rose = '#F43F5E';

    public function label(): string
    {
        return $this->name;
    }

    /**
     * Palette entries for client-facing rendering.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $color): array => [
                'value' => $color->value,
                'label' => $color->label(),
            ],
            self::cases(),
        );
    }
}
