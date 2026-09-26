# Advanced Live Fast Search — راهنمای توسعه

جستجوی زندهٔ وردپرس: کش JSON سمت سرور + جستجوی کامل سمت کاربر (بدون AJAX در هر کلید).

## ساختار

```
advanced-live-fast-search-wp/
├── advanced-live-fast-search-wp.php   ← بوت‌استرپ + ثابت‌ها + activate/deactivate
├── includes/
│   ├── class-mfs-plugin.php   ← singleton، تنظیمات، رجیستری اسکین‌ها، نوتیف‌ها
│   ├── class-mfs-cache.php    ← ساخت/سرو کش (قفل + اتمیک‌رایت + کرون + stale-serve)
│   ├── class-mfs-rest.php     ← GET mfs/v1/data ، POST rebuild ، POST clear
│   ├── class-mfs-settings.php ← صفحهٔ مدیریت فارسی با UI نیتیو وردپرس
│   ├── class-mfs-render.php   ← شورت‌کد + توابع قالب + سازگاری با قالب‌های قدیمی
│   └── class-mfs-assets.php   ← enqueue شرطی + config داینامیک برای JS
├── skins/
│   ├── modal/   (مودال تمام‌صفحه — مبنا: نسخهٔ lst)
│   └── inline/  (نوار هدر — مبنا: websima Style4)
├── assets/      (admin.css / admin.js — فقط صفحهٔ مدیریت)
└── uninstall.php
```

## نصب و استفاده

1. پوشه را در `wp-content/plugins/` بگذارید و فعال کنید.
2. منوی «جستجوی زنده» → تب «منابع»: پست‌تایپ‌ها را انتخاب → ذخیره → «بازسازی کش».
3. اسکین مودال / تاپ‌پنل: فقط `[mfs_search]` را در هدر بگذارید (پنل با «نصب خودکار» در فوتر می‌نشیند). اسکین خطی: شورت‌کد را دقیقاً جای اینپوت بگذارید.
4. Elementor: ویجت «جستجوی زنده» در دستهٔ همین نام.
5. حالت‌ها: `mode="trigger"|field|panel|full` — پیش‌فرض `auto`.
6. هر المان با کلاس `search-js` هم پنل overlay را باز می‌کند.
7. «جستجوهای پرطرفدار»: منو به جایگاه «جستجوهای پرطرفدار (جستجوی زنده)».

### سازگاری با قالب‌های قدیمی
توابع قدیمی `payamava_fast_search_field()` و `websima_fast_search_field()` همچنان کار می‌کنند. `mfs_trigger()` فقط آیکون را چاپ می‌کند.

### ⚠️ مهاجرت: حذف فیچر قدیمی از قالب (الزامی)
اگر قالب قبلاً نسخهٔ قدیمی (my-fast-search / my-fast-search-lst / websima-fast-search) را داشته، حتماً `require` مربوط به `init.php` قدیمی را از `functions.php` حذف کن. در غیر این صورت JS قدیمی (که از `search-data.json` می‌خواند) هم‌زمان با افزونه اجرا می‌شود، نتایج را خالی نشان می‌دهد و دکمه‌ها را می‌دزدد. نشانهٔ تداخل: باز شدن `is-search` با لیست‌های خالی.

### کلاس‌ها و شناسه‌ها (مطابق رفرنس)
CSS و JS اسکین‌ها با همان سلکتورهای نسخه‌های اصلی کار می‌کنند تا ظاهر و رفتار حفظ شود؛ کلاس‌های کمکی جدید فقط **اضافه** شده‌اند:

- مشترک: `#search-by-json-form` ، `#search-by-json` ، `#fast-search-input` ، `#fast-search-body` ، `.search-by-json` ، `.input` ، `.empty` ، `.default` ، `.popular` ، `.menu-popular` ، `.not-found` ، `.is-search` ، `.fast-show/.fast-hide`
- اسکین مودال: `.search-body-box` (+ `.open`) ، `.close` ، `.search-js` (المان بازکننده) و برای بخش‌ها: `products games` / `list-products list-games` / `title-games` ، `posts` / `list-posts` / `title-posts` ، `category` / `list-category` / `title-category`
- اسکین خطی: `.icon` (دکمهٔ جستجو) ، `.searchOverlay` (+ `.showOverlay`) ، `products` / `title-products` / `list-products` ، `posts` / `title-posts` / `list-posts`
- کلاس‌های کمکی افزونه (برای شخصی‌سازی): `.mfs` ، `data-mfs="skin"` ، `.mfs-section[data-section]` ، `.mfs-list` ، `.mfs-title` ، `.mfs-terms` ، `.mfs-trigger` ، `.mfs-input` ، `.mfs-overlay`

برای پست‌تایپ‌های دلخواه (غیر از product/post) بخش‌ها با کلاس‌های `mfs-type-{slug}` و همان استایل عمومی رندر می‌شوند.

## نکات فنی

- **بدون rewrite rule**: داده از `REST /wp-json/mfs/v1/data` سرو می‌شود؛ با هر تنظیم پیوند یکتا کار می‌کند و نیازی به flush نیست.
- **کش**: فایل با نام تصادفی در `uploads/advanced-live-fast-search/` (با htaccess deny)؛ نوشتن اتمیک tmp+rename؛ قفل transient؛ بازسازی خودکار با WP-Cron روی save_post و تغییر ترم‌ها؛ الگوی stale-while-revalidate.
- **داده**: `{"generated":ts,"sections":{post_type:[{id,title,link,img,price_html?,sale?,stock?}]},"terms":{taxonomy:[{id,title,link}]}}` — فیلدهای قیمت فقط برای product با ووکامرس فعال.
- **JS**: لود تنبل (فقط با اولین تایپ)، dedup درخواست، escape کاراکترهای Regex، نرمال‌سازی سراسری ی/ک/ا، `loading="lazy"`، ESC برای بستن، رندر config-محور مطابق منابع انتخابی.
- **فیلترهای توسعه**:
  - `mfs_skins` — افزودن اسکین جدید (آرایه: label/type + پوشه در `skins/`)
  - `mfs_skin_dir` — مسیر دلخواه برای اسکین
  - `mfs_inline_default_banner` — بنر بالای اسکین خطی (HTML برگردانید)
  - override قالب: `your-theme/advanced-live-fast-search/{skin}/template.php`
- **Placeholder**: توکن‌های `{products}` و `{posts}` با شمارندهٔ زنده جایگزین می‌شوند.
- **مقیاس**: تا ~۲-۳ هزار آیتم کاملاً روان است؛ برای کاتالوگ‌های بزرگ‌تر TTL کش را بالا ببرید.

## چک‌لیست تست

1. تایپ `(` یا `*` → بدون خطای کنسول، پیام «یافت نشد»
2. کلمه با دو «ا/ی» → نتیجهٔ کامل
3. Network: قبل از اولین تایپ هیچ درخواستی به `/mfs/v1/data` نیست
4. تغییر نام پوشهٔ افزونه → بدون مشکل (مسیرها داینامیک)
5. ذخیرهٔ یک محصول → تا ~۱۰ ثانیه بعد کش رفرش می‌شود
6. دکمه‌های «بازسازی/پاک‌کردن کش» در تنظیمات + شمارنده‌های وضعیت
