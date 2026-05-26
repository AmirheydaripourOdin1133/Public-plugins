=== Apple Warranty Inquiry ===
Contributors: Payam Ava - Heydaripour
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: warranty, apple, serial, inquiry, persian

سیستم استعلام گارانتی محصولات اپل بر اساس شماره سریال — پنل فارسی، درون‌ریزی CSV و فرم Ajax.

== Description ==

* استعلام عمومی با شورت‌کد `[apple_warranty_inquiry]`
* مدیریت سریال‌ها، تصاویر محصول، درون‌ریزی CSV و تنظیمات HTML
* داده در جداول اختصاصی (بدون post type)
* کپچا، محدودیت نرخ درخواست و امنیت nonce

مستندات کامل: HANDOFF.md و README.md در پوشه پلاگین.

== Installation ==

1. پوشه `apple-warranty-inquiry` را در `wp-content/plugins/` قرار دهید.
2. پلاگین را از پیشخوان فعال کنید.
3. گارانتی اپل → تصاویر محصول → سپس درون‌ریزی یا افزودن دستی.
4. شورت‌کد را در برگه قرار دهید: `[apple_warranty_inquiry]`
5. برگه را از Full Page Cache مستثنی کنید (LiteSpeed و مشابه).

== Frequently Asked Questions ==

= شورت‌کد چیست؟ =

`[apple_warranty_inquiry]`

= ترتیب راه‌اندازی؟ =

ابتدا image_key در تصاویر محصول، بعد CSV یا سریال دستی، بعد شورت‌کد در برگه.

== Changelog ==

= 1.1.3 =
* رفع لود نشدن JS ادمین در صفحه تصاویر
* حفظ آیکون SVG دکمه «بررسی شناسه» هنگام لودینگ
* لینک‌های مستندات و سازنده در اطلاعات افزونه

= 1.1.2 =
* افزایش نسخه برای هم‌گام‌سازی cache فرانت و ادمین

= 1.1.1 =
* فایل نمونه CSV ثابت در assets/samples
* دانلود نمونه قبل از خروجی HTML (رفع فایل HTML در CSV)
* مستندات README.md و HANDOFF.md

= 1.1.0 =
* صفحه درون‌ریزی فارسی + UI مرحله‌ای
* بارگذاری JS/CSS ادمین فقط در صفحات مربوط
* آیکون سفارشی منوی پیشخوان

= 1.0.4 =
* باکس شورت‌کد در لیست سریال‌ها

== Upgrade Notice ==

= 1.1.3 =
به‌روزرسانی JS/CSS ادمین و فرانت — پس از آپلود کش را پاک کنید.

= 1.1.2 =
نسخه ادمین و فرانت را هم‌گام کنید و کش‌ها را پاک کنید.
