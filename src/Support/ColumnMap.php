<?php

namespace Developerabod\LaravelContactExporter\Support;

/**
 * Value Object — represents the mapping between vCard fields and DB columns.
 *
 * Key   = fixed vCard field name (first_name, phone_mobile ...)
 * Value = column name in the DB table, or null if not used
 */
final class ColumnMap
{
    /** All supported vCard fields */
    public const FIELDS = [
        'first_name', 'last_name', 'middle_name',
        'phone_mobile', 'phone_work', 'phone_home',
        'email',
    ];

    /** @param array<string, string|null> $map */
    private function __construct(private array $map) {}

    /** Build from config — missing fields default to null */
    public static function fromConfig(array $config): self
    {
        $defaults = array_fill_keys(self::FIELDS, null);
        return new self(array_merge($defaults, $config));
    }

    /** Merge new column assignments on top of existing ones — returns a new instance */
    public function merge(array $overrides): self
    {
        return new self(array_merge($this->map, $overrides));
    }

    /** Enable specific fields by assigning their DB column — returns a new instance */
    public function with(string $field, string $column): self
    {
        return new self(array_merge($this->map, [$field => $column]));
    }

    /**
     * Unique DB columns needed for SELECT
     * (ignores null values and duplicates)
     *
     * @return string[]
     */
    public function dbColumns(): array
    {
        return array_values(array_unique(array_filter($this->map)));
    }

    /**
     * Extract field values from a DB row
     *
     * @return array<string, string|null>
     */
    public function extractFrom(object $row): array
    {
        $fields = [];
        foreach ($this->map as $vcardField => $dbColumn) {
            $fields[$vcardField] = ($dbColumn !== null)
                ? (isset($row->{$dbColumn}) ? (string) $row->{$dbColumn} : null)
                : null;
        }
        return $fields;
    }
}