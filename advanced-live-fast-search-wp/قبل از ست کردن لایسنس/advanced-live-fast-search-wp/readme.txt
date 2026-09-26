=== Advanced Live Fast Search ===
Contributors: amirheydaripur
Tags: search, live search, ajax search, woocommerce, fast search
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.0
Stable tag: 1.1.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

جستجوی زنده و فوق‌سریع وردپرس با کش JSON سمت سرور و جستجوی سمت کاربر. Live, ultra-fast WordPress search with a server-side JSON cache and client-side matching.

== Description ==

* جستجوی زنده بدون هیچ درخواست AJAX هنگام تایپ (داده‌ها یک‌بار از REST گرفته می‌شوند)
* کش JSON سمت سرور با بازسازی خودکار پس از تغییر محتوا (بدون نیاز به ذخیرهٔ پیوندهای یکتا)
* انتخاب منابع جستجو: هر پست‌تایپ و هر تکسونومی عمومی؛ نمایش تفکیک‌شدهٔ نتایج
* دو اسکین آماده: مودال تمام‌صفحه و نوار جستجوی خطی هدر
* رنگ اکسنت، متن Placeholder با شمارندهٔ زنده ({products} / {posts})
* پشتیبانی از قیمت و درصد تخفیف ووکامرس در نتایج
* نرمال‌سازی حروف فارسی (ی/ي، ک/ك، ا/آ) و جستجوهای پرطرفدار از طریق منوی وردپرس

Usage: place `[mfs_search]` in your header (or `<?php echo mfs_search(); ?>` in PHP). Legacy theme functions `payamava_fast_search_field()` and `websima_fast_search_field()` keep working.

== Changelog ==

= 1.1.2 =
* رفع عدم نمایش نتایج: سازگاری زنجیرهٔ فچ با jQuery 3 (متدهای done/fail روی Deferred خالص)

= 1.1.1 =
* رفع Fatal «Cannot redeclare payamava_fast_search_field» هنگام هم‌زمانی با فیچر قدیمی قالب — تعریف توابع legacy به بعد از لود قالب منتقل شد

= 1.1.0 =
* اندپوینت search-data.json (سازگار با رفرنس) به‌همراه REST به‌عنوان پشتیبان
* کانفیگ JS به‌صورت inline داخل خروجی ویجت (سازگار با افزونه‌های بهینه‌ساز)
* فچ دو مرحله‌ای با fallback خودکار
* اصلاح تولید URL اسکین‌ها و ساختار دقیق رفرنس هر دو اسکین

= 1.0.0 =
* Initial release.
