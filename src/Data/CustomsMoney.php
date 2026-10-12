<?php

namespace Booni3\DynamicWms\Data;

use InvalidArgumentException;

final class CustomsMoney
{
    private $data;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function fromArray(array $data): self
    {
        self::keys($data, ['schema_version', 'currency', 'source', 'sales', 'delivery']);
        if ($data['schema_version'] !== 1 || !in_array($data['currency'], ['GBP', 'EUR'], true)) {
            throw new InvalidArgumentException('Customs money requires schema version 1 and GBP or EUR.');
        }
        self::object($data['source'], ['system', 'reference', 'integrity_hash']);
        self::text($data['source']['system']);
        self::text($data['source']['reference']);
        if (!is_string($data['source']['integrity_hash']) ||
            !preg_match('/\Asha256:[0-9a-f]{64}\z/', $data['source']['integrity_hash'])) {
            throw new InvalidArgumentException('Customs money source requires a SHA-256 integrity hash.');
        }
        self::object($data['delivery'], ['net_minor', 'vat_minor', 'gross_minor']);
        self::amounts($data['delivery'], false);
        self::listOf($data['sales']);
        $saleIds = [];
        $lineIds = [];
        foreach ($data['sales'] as &$sale) {
            self::object($sale, ['sale_line_id', 'net_minor', 'vat_minor', 'gross_minor', 'physical_lines']);
            self::uniqueId($sale['sale_line_id'], $saleIds);
            self::amounts($sale, true);
            self::listOf($sale['physical_lines']);
            foreach ($sale['physical_lines'] as $line) {
                self::object($line, ['line_id', 'sku', 'quantity']);
                self::uniqueId($line['line_id'], $lineIds);
                self::text($line['sku']);
                if (!is_int($line['quantity']) || $line['quantity'] < 1) {
                    throw new InvalidArgumentException('Customs money physical quantity must be a positive integer.');
                }
            }
            usort($sale['physical_lines'], function (array $left, array $right): int {
                return strcmp($left['line_id'], $right['line_id']);
            });
        }
        unset($sale);
        usort($data['sales'], function (array $left, array $right): int {
            return strcmp($left['sale_line_id'], $right['sale_line_id']);
        });

        return new self($data);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    private static function keys(array $value, array $expected): void
    {
        $actual = array_keys($value);
        sort($actual);
        sort($expected);
        if ($actual !== $expected) {
            throw new InvalidArgumentException('Customs money object has missing or unknown fields.');
        }
    }

    private static function object($value, array $expected): void
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Customs money object must be an array.');
        }
        self::keys($value, $expected);
    }

    private static function listOf($value): void
    {
        if (!is_array($value) || !$value || array_keys($value) !== range(0, count($value) - 1)) {
            throw new InvalidArgumentException('Customs money requires a nonempty list.');
        }
    }

    private static function text($value): void
    {
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException('Customs money identity must be a nonempty string.');
        }
    }

    private static function uniqueId($value, array &$ids): void
    {
        self::text($value);
        if (in_array($value, $ids, true)) {
            throw new InvalidArgumentException('Customs money identity must be unique.');
        }
        $ids[] = $value;
    }

    private static function amounts(array $value, bool $positiveNet): void
    {
        foreach (['net_minor', 'vat_minor', 'gross_minor'] as $field) {
            if (!is_int($value[$field]) || $value[$field] < 0) {
                throw new InvalidArgumentException('Customs money amounts must be nonnegative integer minor units.');
            }
        }
        if (($positiveNet && $value['net_minor'] === 0) ||
            $value['net_minor'] > PHP_INT_MAX - $value['vat_minor'] ||
            $value['net_minor'] + $value['vat_minor'] !== $value['gross_minor']) {
            throw new InvalidArgumentException('Customs money requires exact net plus VAT equals gross and positive goods net.');
        }
    }
}
