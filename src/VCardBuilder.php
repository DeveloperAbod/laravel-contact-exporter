<?php

namespace Developerabod\LaravelContactExporter;

use Developerabod\LaravelContactExporter\Support\ExportConfig;

/**
 * Single responsibility: convert a fields array into a vCard 3.0 string.
 * Knows nothing about DB or HTTP.
 */
final class VCardBuilder
{
    public function __construct(private readonly ExportConfig $config) {}

    /**
     * Build a single vCard entry
     *
     * @param array<string, string|null> $fields
     */
    public function build(array $fields): string
    {
        $lines = [];

        $lines[] = 'BEGIN:VCARD';
        $lines[] = 'VERSION:3.0';

        $lines[] = $this->buildN($fields);
        $lines[] = $this->buildFN($fields);

        $this->addPhone($lines, 'CELL', $fields['phone_mobile'] ?? null);
        $this->addPhone($lines, 'WORK', $fields['phone_work']   ?? null);
        $this->addPhone($lines, 'HOME', $fields['phone_home']   ?? null);

        $this->addEmail($lines, $fields['email'] ?? null);

        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines) . "\r\n";
    }

    // -----------------------------------------------------------------------
    // Private builders
    // -----------------------------------------------------------------------

    /** N:Family;Given;Additional;; */
    private function buildN(array $fields): string
    {
        $value = implode(';', [
            $this->esc($fields['last_name']   ?? ''),
            $this->esc($fields['first_name']  ?? ''),
            $this->esc($fields['middle_name'] ?? ''),
            '',
            '',
        ]);

        return $this->line('N', $value);
    }

    /** FN: full display name */
    private function buildFN(array $fields): string
    {
        $parts = array_filter([
            $fields['first_name']  ?? '',
            $fields['middle_name'] ?? '',
            $fields['last_name']   ?? '',
        ]);

        return $this->line('FN', $this->esc(trim(implode(' ', $parts)) ?: 'Unknown'));
    }

    private function addPhone(array &$lines, string $type, ?string $number): void
    {
        if (!empty($number)) {
            $lines[] = "TEL;TYPE={$type}:{$number}";
        }
    }

    private function addEmail(array &$lines, ?string $email): void
    {
        if (!empty($email)) {
            $lines[] = "EMAIL;TYPE=INTERNET:{$email}";
        }
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Build a vCard line, adding CHARSET=UTF-8 for non-ASCII text (vCard 3.0)
     */
    private function line(string $property, string $value): string
    {
        $needsCharset = $this->config->charsetUtf8
            && !mb_detect_encoding($value, 'ASCII', true);

        return $needsCharset
            ? "{$property};CHARSET=UTF-8:{$value}"
            : "{$property}:{$value}";
    }

    /** Escape commas, semicolons, backslashes, and newlines */
    private function esc(string $value): string
    {
        return str_replace(
            ['\\',    ',',    ';',    "\n"],
            ['\\\\', '\\,', '\\;', '\\n'],
            $value
        );
    }
}