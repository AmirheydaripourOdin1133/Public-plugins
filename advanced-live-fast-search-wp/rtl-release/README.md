# پوشه مدیریت نسخه انکود و لایسنس راست‌چین

## ساختار

```
rtl-release/
├── encoded-input/     ← فایل‌های PHP انکودشده (بدون فایل اصلی)
├── build/             ← مونتاژ نهایی افزونه قبل از ZIP
├── package/
│   ├── Plugin/        ← advanced-live-fast-search-wp.zip  (برای نصب)
│   └── help/          ← help.pdf + readme.txt
├── docs/              ← قوانین و یادداشت‌ها
├── reports/           ← خروجی بررسی verify-latest.txt
└── build-and-verify.ps1
```

## قانون مهم

فایل اصلی `advanced-live-fast-search-wp.php` **انکود نشود**.

## نصب تست

1. افزونه قبلی را از پیشخوان **حذف** کنید (نه فقط غیرفعال).
2. فقط این ZIP را نصب کنید:

`rtl-release/package/Plugin/advanced-live-fast-search-wp.zip`

یا نسخه اصلاح‌شده مسیرها:

`rtl-release/package/Plugin/advanced-live-fast-search-wp-v142.zip`

قبل از نصب، گزارش را ببینید:

`rtl-release/reports/verify-latest.txt`

باید `STATUS=PASS` باشد.

## نکته مهم ZIP

ZIP باید با مسیر `/` ساخته شود (نه `\`). در غیر این صورت روی هاست لینوکس خطای «پرونده افزونه پیدا نشد» می‌دهد.
