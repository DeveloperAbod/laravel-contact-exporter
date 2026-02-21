<?php

namespace Developerabod\LaravelContactExporter\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Data object — holds all export configuration.
 * Contains no logic, only data.
 */
final class ExportConfig
{
    public function __construct(
        public readonly string $table,
        public readonly ColumnMap $columnMap,
        public readonly array $conditions,
        public readonly string $filename,
        public readonly bool $appendCount,
        public readonly bool $appendDate,
        public readonly bool $skipEmptyPhone,
        public readonly bool $normalizePhone,
        public readonly bool $charsetUtf8,
        public readonly int $chunkSize,

        /**
         * A ready-made query from Eloquent or DB::table().
         * When provided, it replaces $table + $conditions entirely.
         */
        public readonly EloquentBuilder|QueryBuilder|null $query = null,
    ) {}

    /**
     * Build an ExportConfig from config() with optional overrides.
     *
     * @param array<string, mixed> $overrides
     */
    public static function fromConfig(array $overrides = []): self
    {
        $cfg = config('vcard-exporter', []);
        $opt = $cfg['options'] ?? [];

        return new self(
            table:          $overrides['table']          ?? $cfg['table']                       ?? 'contacts',
            columnMap:      $overrides['columnMap']      ?? ColumnMap::fromConfig($cfg['columns'] ?? []),
            conditions:     $overrides['conditions']     ?? [],
            filename:       $overrides['filename']       ?? $opt['filename']                    ?? 'contacts',
            appendCount:    $overrides['appendCount']    ?? (bool) ($opt['append_count']         ?? true),
            appendDate:     $overrides['appendDate']     ?? (bool) ($opt['append_date']          ?? false),
            skipEmptyPhone: $overrides['skipEmptyPhone'] ?? (bool) ($opt['skip_empty_phone']     ?? true),
            normalizePhone: $overrides['normalizePhone'] ?? (bool) ($opt['normalize_phone']      ?? true),
            charsetUtf8:    $overrides['charsetUtf8']    ?? (bool) ($opt['charset_utf8']         ?? true),
            chunkSize:      $overrides['chunkSize']      ?? (int)  ($opt['chunk_size']           ?? 500),
            query:          $overrides['query']          ?? null,
        );
    }

    /** Return a modified copy with new values — immutable */
    public function with(array $overrides): self
    {
        return new self(
            table:          $overrides['table']          ?? $this->table,
            columnMap:      $overrides['columnMap']      ?? $this->columnMap,
            conditions:     $overrides['conditions']     ?? $this->conditions,
            filename:       $overrides['filename']       ?? $this->filename,
            appendCount:    $overrides['appendCount']    ?? $this->appendCount,
            appendDate:     $overrides['appendDate']     ?? $this->appendDate,
            skipEmptyPhone: $overrides['skipEmptyPhone'] ?? $this->skipEmptyPhone,
            normalizePhone: $overrides['normalizePhone'] ?? $this->normalizePhone,
            charsetUtf8:    $overrides['charsetUtf8']    ?? $this->charsetUtf8,
            chunkSize:      $overrides['chunkSize']      ?? $this->chunkSize,
            query:          $overrides['query']          ?? $this->query,
        );
    }

    /** Resolve the final filename with optional count and date suffixes */
    public function resolveFilename(int $count): string
    {
        $name = $this->filename;

        if ($this->appendDate) {
            $name .= '_' . date('Y-m-d');
        }

        if ($this->appendCount) {
            $name .= "_{$count}";
        }

        return $name;
    }
}