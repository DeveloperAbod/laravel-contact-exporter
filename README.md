<div align="center">

# 📇 Laravel Contact Exporter

**A Laravel package to export database contacts as vCard (`.vcf`) files — clean, professional, and effortless.**

[![PHP](https://img.shields.io/badge/PHP-%5E8.3-blue?style=flat-square&logo=php)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-red?style=flat-square&logo=laravel)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

```bash
composer require developerabod/laravel-contact-exporter
```

</div>

---

## ✨ Features

- 📱 Export contacts as a `.vcf` file ready to import on any smartphone
- 🌍 Full Arabic name support with UTF-8 charset
- 🚀 Chunked database reading — memory-safe with millions of records
- 🔌 Works with both **Eloquent Models** and **`DB::table`** queries
- 🧩 Clean **Fluent API** — intuitive and expressive
- ⚙️ Fully configurable without touching the package source code

---

## 📦 Installation

### Step 1 — Install via Composer

```bash
composer require developerabod/laravel-contact-exporter
```

### Step 2 — Publish the Config File

```bash
php artisan vendor:publish --tag=vcard-exporter-config
```

This creates the file: `config/vcard-exporter.php`

---

## ⚙️ Configuration

Open `config/vcard-exporter.php` and map each config key to the actual column name in your database table:

```php
return [

    'table' => 'users', // Your database table name

    'columns' => [
        'first_name'   => 'first_name',  // The column name in your table
        'last_name'    => null,           // null = disabled by default
        'middle_name'  => null,

        'phone_mobile' => 'phone',        // Primary mobile number — required
        'phone_work'   => null,         // Optional work phone number (disabled by default)
        'phone_home'   => null,        // Optional home phone number (disabled by default)

        'email'        => null,           // null = disabled by default
    ],

    'filename'         => 'contacts',     // Base name of the exported file (e.g. contacts.csv)
    'append_count'     => true, // If true: appends total contact count to filename, Example:contacts_250.vcf
    'append_date'      => false, // If true: appends export date to filename Example: contacts_2026-02-22.vcf
    'skip_empty_phone' => true, 
    'normalize_phone'  => true,
    'charset_utf8'     => true, // Enables UTF-8 encoding to support arabic
    'chunk_size'       => 500,  // Chunk size for optimized processing

];
```

> **Note:** `last_name` and `email` are disabled by default. Enable them at runtime using `withLastName()` and `withEmail()` on the `VCard` facade — no config changes needed.

---

## 🚀 Usage

### Simplest Case — Relies entirely on config

```php
use VCard;

public function export()
{
    return VCard::download();
    // Output: contacts_250.vcf
}
```

---

### With Last Name

```php
return VCard::withLastName()->download();
```

---

### With Email

```php
return VCard::withEmail()->download();
```

---

### With Both Last Name and Email

```php
return VCard::withLastName()->withEmail()->download();
```

---

### Different Table (overrides config)

```php
return VCard::from('users')->download();
```

---

### Override Column Mapping (without changing config)

```php
return VCard::map([
    'first_name'   => 'name',
    'phone_mobile' => 'mobile_number',
])->download();
```

---

### With WHERE Conditions

```php
return VCard::where(['active' => 1])->download();

// Multiple conditions
return VCard::where(['active' => 1, 'country' => 'SA'])->download();
```

> **Note:** `where()` only works when using `from()`. Do **not** combine it with `fromQuery()`.

---

### From an Eloquent Model

```php
// All records
return VCard::fromQuery(Contact::query())->download();

// Using an existing scope on the model
return VCard::fromQuery(Contact::active())->download();

// With custom constraints
return VCard::fromQuery(
    Contact::where('country', 'SA')->orderBy('first_name')
)->download();
```

> **Important:** When using `fromQuery()`, apply all conditions directly to the query **before** passing it in. Do not chain `where()` on the VCard facade alongside `fromQuery()`.

---

### From a `DB::table` Query

```php
return VCard::fromQuery(
    DB::table('contacts')->where('active', 1)
)->download();
```

---

### Custom Filename

```php
return VCard::download('company_employees');
// Output: company_employees_150.vcf
```

---

### Full Control — All Options Combined

```php
return VCard::fromQuery(Contact::active())
    ->map([
        'first_name'   => 'fname',
        'last_name'    => 'lname',
        'phone_mobile' => 'mobile',
        'phone_work'   => 'work_phone',
        'email'        => 'email',
    ])
    ->withLastName()
    ->withEmail()
    ->filename('contacts_export')
    ->chunkSize(1000)
    ->download();
```

---

## 📋 Configuration Reference

| Option | Description | Default |
|--------|-------------|---------|
| `table` | Database table name | `contacts` |
| `filename` | Output filename without `.vcf` extension | `contacts` |
| `append_count` | Append the total record count to the filename | `true` |
| `append_date` | Append today's date to the filename | `false` |
| `skip_empty_phone` | Skip records that have no phone number | `true` |
| `normalize_phone` | Strip symbols and formatting from phone numbers | `true` |
| `charset_utf8` | Enable UTF-8 charset for Arabic name support | `true` |
| `chunk_size` | Number of records to process per chunk | `500` |

---

## 📖 API Reference

| Method | Description |
|--------|-------------|
| `from(string $table)` | Set the database table to query |
| `fromQuery($query)` | Pass in a ready-made Eloquent or `DB::table` query builder |
| `map(array $columns)` | Override the column mapping at runtime |
| `where(array $conditions)` | Add WHERE conditions (only works with `from()`) |
| `withLastName()` | Enable the last name field |
| `withEmail()` | Enable the email field |
| `filename(string $name)` | Set a custom output filename |
| `chunkSize(int $size)` | Override the chunk size for this export |
| `download(?string $filename)` | Execute the export and stream the `.vcf` file |

---

## 🏗️ Package Structure

```
src/
├── Support/
│   ├── ExportConfig.php       ← Data object holding all resolved settings
│   └── ColumnMap.php          ← Column mapping between config keys and DB columns
├── VCardBuilder.php           ← Builds vCard 3.0 formatted strings
├── VCardDownloader.php        ← Reads from DB in chunks and streams the file
├── VCardExporter.php          ← The fluent API builder
├── Facades/
│   └── VCard.php              ← Laravel Facade
└── Providers/
    └── VCardServiceProvider.php

config/
└── vcard-exporter.php
```

---

## 📋 Requirements

| Requirement | Version |
|-------------|---------|
| PHP | `^8.3` |
| Laravel | `^10 \| ^11 \| ^12` |

---

## 📄 License

Open source, released under the [MIT License](LICENSE).

---

<div align="center">

Made with ❤️ by [Developer Abod](https://github.com/developerabod)

</div>
