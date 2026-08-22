<?php

namespace App\Services;

class UnitConversionService
{
    public const UNIT_KG = 'kg';
    public const UNIT_GM = 'gm';
    public const UNIT_PCS = 'pcs';
    public const UNIT_L = 'l';
    public const UNIT_ML = 'ml';

    public function normalizeUnitType(?string $unitType): string
    {
        $unitType = strtolower(trim((string) $unitType));

        return match ($unitType) {
            self::UNIT_KG, 'kilogram', 'kilo' => self::UNIT_KG,
            self::UNIT_GM, 'gram', 'grams' => self::UNIT_GM,
            self::UNIT_PCS, 'piece', 'pieces' => self::UNIT_PCS,
            self::UNIT_L, 'liter', 'litre', 'liters', 'litres' => self::UNIT_L,
            self::UNIT_ML, 'milliliter', 'millilitre', 'milliliters', 'millilitres' => self::UNIT_ML,
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
            self::UNIT_L => (int) round($quantity * 1000),
            self::UNIT_GM => (int) round($quantity),
            self::UNIT_ML => (int) round($quantity),
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

        if ($unitType === self::UNIT_L) {
            $litres = $baseQuantity / 1000;
            return rtrim(rtrim(number_format($litres, 3, '.', ''), '0'), '.') . ' L';
        }

        if ($unitType === self::UNIT_ML) {
            return rtrim(rtrim(number_format($baseQuantity, 0, '.', ''), '0'), '.') . ' ML';
        }

        return rtrim(rtrim(number_format($baseQuantity, 0, '.', ''), '0'), '.') . ' GM';
    }

    public function formatPriceLabel(int|float|string $price, ?string $unitType): string
    {
        $unitType = $this->normalizeUnitType($unitType);
        $suffix = match ($unitType) {
            self::UNIT_PCS => 'PCS',
            self::UNIT_GM => 'GM',
            self::UNIT_L => 'L',
            self::UNIT_ML => 'ML',
            default => 'KG',
        };

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
            self::UNIT_L => round($baseQuantity / 1000, 3),
            self::UNIT_GM, self::UNIT_ML, self::UNIT_PCS => round($baseQuantity, 3),
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

        if ($unitType === self::UNIT_L) {
            return [
                'quantity' => round($baseQuantity / 1000, 3),
                'unit' => self::UNIT_L,
            ];
        }

        if ($unitType === self::UNIT_ML) {
            return [
                'quantity' => (int) round($baseQuantity),
                'unit' => self::UNIT_ML,
            ];
        }

        return [
            'quantity' => (int) round($baseQuantity),
            'unit' => self::UNIT_GM,
        ];
    }
}
