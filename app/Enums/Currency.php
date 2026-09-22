<?php

namespace App\Enums;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case COP = 'COP';
    case ARS = 'ARS';
    case MXN = 'MXN';
    case CLP = 'CLP';
    case BRL = 'BRL';

    public function label(): string
    {
        return match ($this) {
            self::USD => 'US Dollar',
            self::EUR => 'Euro',
            self::COP => 'Colombian Peso',
            self::ARS => 'Argentine Peso',
            self::MXN => 'Mexican Peso',
            self::CLP => 'Chilean Peso',
            self::BRL => 'Brazilian Real',
        };
    }

    public function format(int $cents): string
    {
        $amount = $cents / 100;

        return match ($this) {
            self::USD => '$'.number_format($amount, 2),
            self::EUR => '€'.number_format($amount, 2, ',', '.'),
            self::COP => 'COL$'.number_format($amount, 0, '.', '.'),
            self::ARS => 'ARS$'.number_format($amount, 0, '.', '.'),
            self::MXN => '$'.number_format($amount, 2),
            self::CLP => 'CLP$'.number_format($amount, 0, '.', '.'),
            self::BRL => 'R$'.number_format($amount, 2, ',', '.'),
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::EUR => '€',
            self::COP => 'COL$',
            self::ARS => 'ARS$',
            self::MXN => '$',
            self::CLP => 'CLP$',
            self::BRL => 'R$',
        };
    }
}
