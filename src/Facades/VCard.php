<?php

namespace Developerabod\LaravelContactExporter\Facades;

use Illuminate\Support\Facades\Facade;
use Developerabod\LaravelContactExporter\VCardExporter;

/**
 * @method static VCardExporter from(string $table)
 * @method static VCardExporter fromQuery(\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query)
 * @method static VCardExporter map(array $columns)
 * @method static VCardExporter where(array $conditions)
 * @method static VCardExporter withLastName()
 * @method static VCardExporter withEmail()
 * @method static VCardExporter filename(string $name)
 * @method static VCardExporter chunkSize(int $size)
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse download(?string $filename = null)
 *
 * @see \Developerabod\LaravelContactExporter\VCardExporter
 */
class VCard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VCardExporter::class;
    }
}