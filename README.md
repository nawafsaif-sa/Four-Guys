# Four Guys - Restaurant Management System

A collaborative university project that brings customer ordering and restaurant staff workflows into one PHP/MySQL application.

## Features

- Customer registration and login, searchable menu, persistent shopping carts, and order placement and tracking.
- Product reviews, restaurant ratings, and customer contact messages.
- Separate workflows for managers, cashiers, chefs, and waiters.
- Menu and staff administration, backed by a 13-table relational database.
- Responsive interfaces with shared styling, mobile navigation, and light/dark theme assets.

## Technology stack

**PHP, MySQL/MariaDB, JavaScript, HTML5, CSS3, MySQLi, XAMPP**

No npm or Composer installation is required.

## Run locally

1. Install XAMPP or an equivalent Apache/PHP/MySQL environment with **PHP 8.2** and the **MySQLi** extension.
2. Place the contents of this repository in `C:\xampp\htdocs\Project` (or the equivalent Apache document root). Keep the folder name `Project`, since some existing routes use this path.
3. Start Apache and MySQL.
4. Create an empty database named `web_project` using the `utf8mb4` character set.
5. Import `database/schema.sql`, followed by `database/products.sql`.
6. Copy `Backend/db.example.php` to `Backend/db.local.php` and configure your local database connection. The local file is excluded by `.gitignore`.
7. Open `http://localhost/Project/` or `http://localhost/Project/home.php`.

You can register a customer through the Sign Up page. Staff accounts must be configured locally; this repository does not include default staff credentials. A PHP-capable server is required; a static preview or GitHub Pages will not run the backend.

## Repository structure

| Path | Purpose |
| --- | --- |
| Root PHP/HTML pages | Customer and staff interfaces |
| `Backend/` | Request handlers, database access, and account/order operations |
| `Includes/` | Shared page headers, footers, and staff navigation |
| `assets/` | Shared CSS and JavaScript |
| `database/schema.sql` | Database structure |
| `database/products.sql` | Sample menu data |

The SQL files contain the schema and sample products, without customer or staff records, passwords, messages, orders, or reviews.

## Scope

This is an academic demonstration for local use. Legacy authentication and query handling need a security review before deploying a live service. The repository does not include a deployed application.

---

## Arabic setup guide

# Four Guys Restaurant

نسخة المشروع بتصميم عصري متجاوب: صفحات العملاء، المنيو والبحث، الدخول، السلة، والتقييمات، ولوحات المدير والكاشير والشيف والنادل.

## المتطلبات

- PHP 8.2 مع إضافة mysqli، وMySQL/MariaDB وApache (مثل XAMPP).
- لا يحتاج المشروع npm أو Composer.

## التشغيل المحلي

1. ضع **محتويات هذا المجلد** داخل `C:\xampp\htdocs\Project`.
2. شغّل Apache وMySQL من XAMPP.
3. أنشئ قاعدة بيانات جديدة وفارغة باسم `web_project` بترميز `utf8mb4`، أو اختر اسمًا آخر في الإعدادات.
4. استورد `database/schema.sql` إلى القاعدة، ثم `database/products.sql` لإضافة قائمة الطعام.
5. انسخ `Backend/db.example.php` إلى `Backend/db.local.php` واضبط بيانات اتصال جهازك. ملف `db.php` المرفق آمن للرفع؛ يقرأ الإعدادات من الملف المحلي المستثنى من Git.
6. افتح `http://localhost/Project/home.php` أو `http://localhost/Project/`.

استخدم اسم المجلد `Project` لأن بعض المسارات الموجودة تعتمد عليه. لا يشغّل Live Server ملفات PHP.

## قاعدة البيانات والحسابات

يتضمن التصدير بنية الجداول وقائمة المنتجات فقط. لا يتضمن العملاء أو الموظفين أو كلمات المرور أو الرسائل أو الطلبات أو التقييمات الفعلية. يمكن إنشاء حساب عميل من صفحة Sign Up. يجب تجهيز حسابات الموظفين محليًا قبل تجربة لوحاتهم؛ لا توجد حسابات دخول افتراضية مرفقة.

## ملفات التصميم

- `assets/four-guys.css`: الألوان والتنسيقات المشتركة وتجاوب الجوال.
- `assets/four-guys.js`: قائمة الجوال والبحث وتحسين النماذج والجداول.
- `Includes/`: رأس الصفحة وتذييلها وتنقل الموظفين.
- صفحات PHP: الواجهات والربط بالخدمات الموجودة.

## الرفع إلى GitHub

ارفع محتويات هذا المجلد مع `.gitignore` إلى جذر المستودع. ملف ZIP وسيلة لنقل المشروع؛ رفع ZIP وحده لا يجعل GitHub يعرض ملفات الكود داخله كمشروع. لا ترفع `db.local.php` أو نسخة قاعدة بيانات تحتوي معلومات حقيقية. استضافة التطبيق تحتاج خادمًا يدعم PHP وMySQL؛ المستودع يحفظ الكود فقط.

## حدود هذه النسخة

التحديث يركّز على التصميم، وليس إعادة بناء الخدمات. منطق تسجيل الدخول القديم يحتاج مراجعة أمنية قبل النشر العام، بما فيه تخزين كلمات المرور والاستعلامات. ملفات فحص قاعدة البيانات التي تعرض بيانات المستخدمين مستبعدة من الحزمة. لم تُضمَّن إعدادات Apache أو مسارات سطح المكتب الخاصة بالجهاز.
