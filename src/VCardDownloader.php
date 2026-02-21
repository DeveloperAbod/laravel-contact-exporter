<?php

namespace Developerabod\LaravelContactExporter;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Developerabod\LaravelContactExporter\Support\ExportConfig;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Single responsibility: read records from DB and stream them as a .vcf file.
 *
 * Knows nothing about vCard format — delegates that to VCardBuilder.
 * Knows nothing about configuration — receives it ready-made from VCardExporter.
 */
final class VCardDownloader
{
    private VCardBuilder $builder;

    public function __construct(private readonly ExportConfig $config)
    {
        $this->builder = new VCardBuilder($config);
    }

    public function download(): StreamedResponse
    {
        $columnMap = $this->config->columnMap;
        $query     = $this->resolveQuery();

        $count    = $query->count();
        $filename = $this->config->resolveFilename($count);

        return response()->streamDownload(
            function () use ($query, $columnMap) {
                $query->chunk(
                    $this->config->chunkSize,
                    function ($rows) use ($columnMap) {
                        foreach ($rows as $row) {
                            // Eloquent returns a Model — cast to stdClass for consistency
                            $rawRow = $row instanceof \Illuminate\Database\Eloquent\Model
                                ? (object) $row->getAttributes()
                                : $row;

                            $fields = $columnMap->extractFrom($rawRow);

                            if ($this->config->normalizePhone) {
                                $fields = $this->normalizePhones($fields);
                            }

                            if ($this->config->skipEmptyPhone && !$this->hasPhone($fields)) {
                                continue;
                            }

                            echo $this->builder->build($fields);
                        }
                    }
                );
            },
            "{$filename}.vcf",
            [
                'Content-Type'        => 'text/vcard; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}.vcf\"",
            ]
        );
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Resolve the data source:
     * - If a ready-made query was provided → use it directly
     * - Otherwise → build a query from table + conditions
     */
    private function resolveQuery(): EloquentBuilder|QueryBuilder
    {
        if ($this->config->query !== null) {
            return $this->config->query;
        }

        $query = DB::table($this->config->table)
            ->select($this->config->columnMap->dbColumns());

        foreach ($this->config->conditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query;
    }

    /** Strip spaces and symbols from phone numbers, preserving a leading + */
    private function normalizePhones(array $fields): array
    {
        foreach (['phone_mobile', 'phone_work', 'phone_home'] as $key) {
            if (!empty($fields[$key])) {
                $fields[$key] = $this->cleanPhone($fields[$key]);
            }
        }
        return $fields;
    }

    private function cleanPhone(string $phone): string
    {
        $hasPlus = str_starts_with(ltrim($phone), '+');
        $digits  = preg_replace('/\D/', '', $phone);
        return $hasPlus ? '+' . $digits : $digits;
    }

    private function hasPhone(array $fields): bool
    {
        return !empty($fields['phone_mobile'])
            || !empty($fields['phone_work'])
            || !empty($fields['phone_home']);
    }
}