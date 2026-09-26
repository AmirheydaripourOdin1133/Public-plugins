# قوانین نسخه انکود / لایسنس راست‌چین

## قانون طلایی
- فایل اصلی `advanced-live-fast-search-wp.php` هرگز انکود نشود.
- فقط این‌ها انکود شوند: فایل‌های PHP داخل `includes/` و `skins/*/template.php`
- `RTL_License_*.php` از قبل انکود است؛ دوباره انکود نشود.
- `uninstall.php` خام بماند.

## مسیر کار
1. سورس خام را ادیت کنید (ریشه پروژه).
2. فایل‌های لازم را در راست‌چین انکود کنید.
3. خروجی انکود را داخل `rtl-release/encoded-input/` بگذارید.
4. اسکریپت `rtl-release/build-and-verify.ps1` را اجرا کنید.
5. فقط اگر گزارش OK بود، ZIP داخل `rtl-release/package/Plugin/` را نصب/آپلود کنید.

## نصب تست
فقط از این فایل استفاده کنید:
`rtl-release/package/Plugin/advanced-live-fast-search-wp.zip`