<div dir="rtl">

# Laravel Contact Exporter

باكج Laravel لتصدير جهات الاتصال من قاعدة البيانات بصيغة vCard (`.vcf`) بشكل احترافي وسهل.

---

## المميزات

- تصدير جهات الاتصال كملف `.vcf` جاهز للاستيراد في أي هاتف
- دعم كامل للأسماء العربية
- قراءة البيانات بـ chunk لتوفير الذاكرة مع الملايين من السجلات
- دعم Eloquent Model و DB::table بشكل مباشر
- Fluent API سهل وسريع
- قابل للتخصيص الكامل بدون تعديل الكود

---

## التثبيت

```bash
composer require developerabod/laravel-contact-exporter
```

نشر ملف الإعدادات:

```bash
php artisan vendor:publish --tag=vcard-exporter-config
```

---

## الإعداد

بعد النشر ستجد الملف في `config/vcard-exporter.php`، افتحه وربط أعمدة جدولك:

```php
'table' => 'contacts', // اسم جدولك في قاعدة البيانات

'columns' => [
    'first_name'   => 'first_name', // اسم العمود في جدولك
    'last_name'    => null,         // null = مخفي بالافتراضي
    'middle_name'  => null,

    'phone_mobile' => 'phone',      // رقم الجوال — الأساسي
    'phone_work'   => null,
    'phone_home'   => null,

    'email'        => null,         // null = مخفي بالافتراضي
],
```

> **ملاحظة:** `last_name` و `email` مخفيان بالافتراضي. المستخدم يفعّلهم عند الحاجة بـ `withLastName()` و `withEmail()`.

---

## الاستخدام

### أبسط حالة — يعتمد على config بالكامل

```php
use VCard;

public function export()
{
    return VCard::download();
    // ينتج: contacts_250.vcf
}
```

---

### مع اسم العائلة

```php
return VCard::withLastName()->download();
```

---

### مع البريد الإلكتروني

```php
return VCard::withEmail()->download();
```

---

### مع اسم العائلة والبريد معاً

```php
return VCard::withLastName()->withEmail()->download();
```

---

### جدول مختلف عن الـ config

```php
return VCard::from('users')->download();
```

---

### تعديل أعمدة بدون تغيير config

```php
return VCard::map([
    'first_name'   => 'name',
    'phone_mobile' => 'mobile_number',
])->download();
```

---

### مع شروط WHERE

```php
return VCard::where(['active' => 1])->download();

// شروط متعددة
return VCard::where(['active' => 1, 'country' => 'SA'])->download();
```

---

### من Eloquent Model مباشرة

```php
// كل السجلات
return VCard::fromQuery(Contact::query())->download();

// مع scope موجود في الـ Model
return VCard::fromQuery(Contact::active())->download();

// مع شروط
return VCard::fromQuery(
    Contact::where('country', 'SA')->orderBy('first_name')
)->download();
```

> **مهم:** عند استخدام `fromQuery` لا تستخدم `where()` — أضف الشروط مباشرة في الـ query قبل التمرير.

---

### من DB::table

```php
return VCard::fromQuery(
    DB::table('contacts')->where('active', 1)
)->download();
```

---

### اسم ملف مخصص

```php
return VCard::download('موظفي_الشركة');
// ينتج: موظفي_الشركة_150.vcf
```

---

### التحكم الكامل

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

## الـ Route

```php
// routes/web.php
Route::get('/export-contacts', [ContactController::class, 'export']);
```

المستخدم يفتح الرابط ويبدأ تحميل الملف مباشرة.

---

## خيارات config كاملة

| الخيار | الوصف | الافتراضي |
|--------|-------|-----------|
| `table` | اسم الجدول | `contacts` |
| `filename` | اسم الملف بدون `.vcf` | `contacts` |
| `append_count` | يضيف عدد السجلات في الاسم | `true` |
| `append_date` | يضيف التاريخ في الاسم | `false` |
| `skip_empty_phone` | يتجاهل السجلات بدون هاتف | `true` |
| `normalize_phone` | ينظف أرقام الهاتف من الرموز | `true` |
| `charset_utf8` | دعم الأسماء العربية | `true` |
| `chunk_size` | عدد السجلات لكل دفعة | `500` |

---

## الـ API كاملاً

| الدالة | الوصف |
|--------|-------|
| `from(string $table)` | تحديد الجدول |
| `fromQuery($query)` | تمرير Eloquent أو DB query جاهز |
| `map(array $columns)` | تعديل خريطة الأعمدة |
| `where(array $conditions)` | إضافة شروط (مع `from` فقط) |
| `withLastName()` | تفعيل اسم العائلة |
| `withEmail()` | تفعيل البريد الإلكتروني |
| `filename(string $name)` | اسم ملف مخصص |
| `chunkSize(int $size)` | تعديل حجم الـ chunk |
| `download(?string $filename)` | تنفيذ التصدير |

---

## هيكل الباكج

```
src/
├── Support/
│   ├── ExportConfig.php    ← data object يحمل كل الإعدادات
│   └── ColumnMap.php       ← خريطة ربط الأعمدة
├── VCardBuilder.php        ← يبني صيغة vCard 3.0
├── VCardDownloader.php     ← يقرأ DB ويرسل الملف
├── VCardExporter.php       ← fluent API
├── Facades/VCard.php
└── Providers/VCardServiceProvider.php

config/
└── vcard-exporter.php
```

---

## المتطلبات

| المتطلب | الإصدار |
|---------|---------|
| PHP | ^8.3 |
| Laravel | ^10 \| ^11 \| ^12 |

---

## الترخيص

مفتوح المصدر تحت رخصة [MIT](LICENSE).

---

<div align="center">
صُنع بـ ❤️ بواسطة <a href="https://github.com/developerabod">Developer Abod</a>
</div>

</div>