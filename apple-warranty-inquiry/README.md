# Apple Warranty Inquiry

پلاگین اختصاصی وردپرس برای **استعلام گارانتی محصولات اپل** بر اساس شماره سریال — با پنل مدیریت فارسی، درون‌ریزی CSV و فرم عمومی با Ajax.

| | |
|---|---|
| **نسخه** | `1.1.7` |
| **PHP** | 8.0+ |
| **WordPress** | 6.0+ |
| **Text domain** | `apple-warranty-inquiry` |
| **Namespace** | `AppleWarranty` |

## نصب سریع

1. پوشه `apple-warranty-inquiry` را در `wp-content/plugins/` قرار دهید.
2. از **افزونه‌ها** پلاگین را فعال کنید.
3. در پیشخوان: **گارانتی اپل** → ابتدا **تصاویر محصول**، سپس **درون‌ریزی** یا افزودن دستی سریال‌ها.
4. در برگهٔ سایت شورت‌کد را قرار دهید: `[apple_warranty_inquiry]`
5. صفحهٔ دارای شورت‌کد را از **Full Page Cache** (مثلاً LiteSpeed) مستثنی کنید.

## شورت‌کد

```
[apple_warranty_inquiry]
```

## مستندات (دانشنامه)

| سند | مخاطب |
|-----|--------|
| **[HANDOFF.md](./HANDOFF.md)** | **مرجع اصلی** — راهنمای مدیر + معماری توسعه‌دهنده |
| [assets/samples/README.txt](./assets/samples/README.txt) | ویرایش فایل نمونه CSV درون‌ریزی |
| [assets/images/README.txt](./assets/images/README.txt) | آیکون بخش گارانتی در نتیجه (`warranty-badge.webp`) |

## ساختار مخزن Git

```
apple-warranty-inquiry/
├── README.md              ← همین فایل (ورودی GitHub)
├── HANDOFF.md             ← دانشنامه کامل
├── readme.txt             ← متادیتای WordPress.org (در صورت انتشار)
├── plugin.php
├── app/                   ← کد PHP (OOP)
├── assets/                ← CSS, JS, نمونه CSV, تصاویر ثابت
└── templates/             ← قالب‌های ادمین و فرانت
```

## توسعه فرانت (CSS)

- منبع: `assets/css/frontend.scss`
- خروجی enqueue: `assets/css/frontend.min.css` (با Live Sass Compiler یا `sass` بسازید)

## مجوز

GPLv2 or later (هم‌راست با `readme.txt`)

---

**Payam Ava - Heydaripour** · برای جزئیات فنی و گردش کار ادمین → [HANDOFF.md](./HANDOFF.md)
