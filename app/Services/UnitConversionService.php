<?php

namespace App\Services;

class UnitConversionService
{
    public const UNIT_KG = 'kg';
    public const UNIT_GM = 'gm';
    public const UNIT_PCS = 'pcs';

    public function normalizeUnitType(?string $unitType): string
    {
        $unitType = strtolower(trim((string) $unitType));

        return match ($unitType) {
            self::UNIT_KG, 'kilogram', 'kilo' => self::UNIT_KG,
            self::UNIT_GM, 'gram', 'grams' => self::UNIT_GM,
            self::UNIT_PCS, 'piece', 'pieces' => self::UNIT_PCS,
            default => self::UNIT_PCS,
        };
    }

    public function isWeightUnit(?string $unitType): bool
    {
        return in_array($this->normalizeUnitType($unitType), [self::UNIT_KG, self::UNIT_GM], true);
    }

    public function isPieceUnit(?string $unitType): bool
    {
        return $this->normalizeUnitType($unitType) === self::UNIT_PCS;
    }

    public function convertToBaseQuantity(float|int|string $quantity, ?string $unitType): int
    {
        $unitType = $this->normalizeUnitType($unitType);
        $quantity = (float) $quantity;

        return match ($unitType) {
            self::UNIT_KG => (int) round($quantity * 1000),
            self::UNIT_GM => (int) round($quantity),
            self::UNIT_PCS => (int) round($quantity),
            default => (int) round($quantity),
        };
    }

    public function isValidQuantity(float|int|string $quantity, ?string $unitType): bool
    {
        $unitType = $this->normalizeUnitType($unitType);
        $quantity = (string) $quantity;

        if (!is_numeric($quantity)) {
            return false;
        }

        $value = (float) $quantity;

        if ($value <= 0) {
            return false;
        }

        if ($unitType === self::UNIT_PCS) {
            return filter_var($quantity, FILTER_VALIDATE_INT) !== false;
        }

        return true;
    }

    public function formatStock(int|float|string $baseQuantity, ?string $unitType): string
    {
        $unitType = $this->normalizeUnitType($unitType);
        $baseQuantity = (float) $baseQuantity;

        if ($unitType === self::UNIT_PCS) {
            return rtrim(rtrim(number_format($baseQuantity, 0, '.', ''), '0'), '.') . ' PCS';
        }

        if ($unitType === self::UNIT_KG) {
            $kg = $baseQuantity / 1000;
            return rtrim(rtrim(number_format($kg, 3, '.', ''), '0'), '.') . ' KG';
        }

        return rtrim(rtrim(number_format($baseQuantity, 0, '.', ''), '0'), '.') . ' GM';
    }

    public function formatPriceLabel(int|float|string $price, ?string $unitType): string
    {
        $unitType = $this->normalizeUnitType($unitType);
        $suffix = $unitType === self::UNIT_PCS ? 'PCS' : ($unitType === self::UNIT_GM ? 'GM' : 'KG');

        return '৳' . number_format((float) $price, 2) . ' / ' . $suffix;
    }

    public function calculateSubtotal(float|int|string $unitPrice, float|int|string $quantity, ?string $unitType): float
    {
        $quantity = (float) $quantity;
        $unitPrice = (float) $unitPrice;

        if ($this->normalizeUnitType($unitType) === self::UNIT_PCS) {
            return round($unitPrice * $quantity, 2);
        }

        return round($unitPrice * $quantity, 2);
    }

    public function convertBaseToQuantity(int|float|string $baseQuantity, ?string $unitType): float
    {
        $unitType = $this->normalizeUnitType($unitType);
        $baseQuantity = (float) $baseQuantity;

        return match ($unitType) {
            self::UNIT_KG => round($baseQuantity / 1000, 3),
            self::UNIT_GM, self::UNIT_PCS => round($baseQuantity, 3),
            default => round($baseQuantity, 3),
        };
    }

    public function displayQuantity(float|int|string $baseQuantity, ?string $unitType): array
    {
        $unitType = $this->normalizeUnitType($unitType);
        $baseQuantity = (float) $baseQuantity;

        if ($unitType === self::UNIT_PCS) {
            return [
                'quantity' => (int) round($baseQuantity),
                'unit' => self::UNIT_PCS,
            ];
        }

        if ($unitType === self::UNIT_KG) {
            return [
                'quantity' => round($baseQuantity / 1000, 3),
                'unit' => self::UNIT_KG,
            ];
        }

        return [
            'quantity' => (int) round($baseQuantity),
            'unit' => self::UNIT_GM,
        ];
    }
}
