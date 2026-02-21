<?php

namespace Developerabod\LaravelContactExporter;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Developerabod\LaravelContactExporter\Support\ExportConfig;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fluent Builder — collects export configuration only.
 *
 * Does not talk to DB, does not build an HTTP Response.
 * When download() is called, it delegates all work to VCardDownloader.
 *
 * Usage:
 *
 *   VCard::download();
 *
 *   VCard::fromQuery(Contact::active())
 *       ->withLastName()
 *       ->withEmail()
 *       ->download();
 */
class VCardExporter
{
    private ExportConfig $config;

    public function __construct()
    {
        $this->config = ExportConfig::fromConfig();
    }

    // -----------------------------------------------------------------------
    // Fluent API — every method returns $this for chaining
    // -----------------------------------------------------------------------

    /** Set the source table */
    public function from(string $table): static
    {
        $this->config = $this->config->with(['table' => $table]);
        return $this;
    }

    /**
     * Pass a ready-made query from Eloquent or DB::table()
     *
     * Bypasses from() and where() entirely — you control the query.
     *
     *   VCard::fromQuery(Contact::query())->download();
     *   VCard::fromQuery(Contact::active())->download();
     *   VCard::fromQuery(DB::table('contacts')->where('active', 1))->download();
     */
    public function fromQuery(EloquentBuilder|QueryBuilder $query): static
    {
        $this->config = $this->config->with(['query' => $query]);
        return $this;
    }

    /**
     * Override column mapping (merged on top of config)
     *
     *   ->map(['first_name' => 'fname', 'phone_mobile' => 'mobile'])
     */
    public function map(array $columns): static
    {
        $this->config = $this->config->with([
            'columnMap' => $this->config->columnMap->merge($columns),
        ]);
        return $this;
    }

    /**
     * Add WHERE conditions — only applies when using from(), not fromQuery()
     *
     *   ->where(['active' => 1, 'country' => 'SA'])
     */
    public function where(array $conditions): static
    {
        $this->config = $this->config->with([
            'conditions' => array_merge($this->config->conditions, $conditions),
        ]);
        return $this;
    }

    /**
     * Include last name in the exported file
     * Requires the column to be set in config or via map()
     */
    public function withLastName(): static
    {
        $column = config('vcard-exporter.columns.last_name', 'last_name');
        $this->config = $this->config->with([
            'columnMap' => $this->config->columnMap->with('last_name', $column),
        ]);
        return $this;
    }

    /**
     * Include email in the exported file
     * Requires the column to be set in config or via map()
     */
    public function withEmail(): static
    {
        $column = config('vcard-exporter.columns.email', 'email');
        $this->config = $this->config->with([
            'columnMap' => $this->config->columnMap->with('email', $column),
        ]);
        return $this;
    }

    /** Set a custom filename (without .vcf) */
    public function filename(string $name): static
    {
        $this->config = $this->config->with(['filename' => $name]);
        return $this;
    }

    /** Set the chunk size for reading records */
    public function chunkSize(int $size): static
    {
        $this->config = $this->config->with(['chunkSize' => $size]);
        return $this;
    }

    // -----------------------------------------------------------------------
    // Terminal — triggers the actual export
    // -----------------------------------------------------------------------

    /**
     * Stream and download the .vcf file
     *
     * @param string|null $filename Overrides the configured filename
     */
    public function download(?string $filename = null): StreamedResponse
    {
        if ($filename !== null) {
            $this->config = $this->config->with(['filename' => $filename]);
        }

        return (new VCardDownloader($this->config))->download();
    }
}