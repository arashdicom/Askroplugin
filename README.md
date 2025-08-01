# AskMe Plugin - نظام الأسئلة والأجوبة المتقدم

![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/arashdicom/Askro?utm_source=oss&utm_medium=github&utm_campaign=arashdicom%2FAskro&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews) nice

## نظرة عامة

AskMe هو إضافة WordPress متقدمة لإنشاء نظام أسئلة وأجوبة تفاعلي ومتطور. يوفر النظام تجربة مستخدم غنية مع ميزات متقدمة مثل نظام النقاط، التصنيفات، التصويت المتعدد الأبعاد، والتعليقات المتداخلة.

## الميزات الرئيسية

### ✅ المكونات الأساسية
- **نظام الأسئلة والأجوبة**: إدارة كاملة للأسئلة والإجابات
- **نظام التصنيفات والعلامات**: تصنيف منظم للمحتوى
- **نظام النقاط والرتب**: نظام تحفيزي متقدم
- **لوحة الإدارة**: تحكم شامل في جميع جوانب النظام

### ✅ مكونات التفاعل المتقدمة
- **نظام التصويت المتعدد الأبعاد**: تصويت متطور مع أنواع مختلفة (مفيد، مبدع، عاطفي، إلخ)
- **نظام التعليقات المتداخلة**: تعليقات متقدمة مع ردود الفعل والتفاعلات
- **نظام البحث المتقدم**: بحث ذكي مع اقتراحات وفلاتر متقدمة
- **نظام التصفية المتقدم**: فلاتر شاملة للأسئلة مع خيارات متعددة

### ✅ الميزات الإضافية
- **نظام الإشعارات**: إشعارات فورية للمستخدمين
- **نظام التحليلات**: إحصائيات مفصلة للنشاط
- **نظام الإنجازات**: شارات وإنجازات للمستخدمين
- **دعم RTL**: دعم كامل للغة العربية
- **الوضع المظلم**: دعم الوضع المظلم
- **تصميم متجاوب**: يعمل على جميع الأجهزة

## التثبيت والإعداد

### المتطلبات
- WordPress 5.0 أو أحدث
- PHP 7.4 أو أحدث
- MySQL 5.7 أو أحدث

### خطوات التثبيت
1. قم برفع مجلد الإضافة إلى `/wp-content/plugins/`
2. قم بتفعيل الإضافة من لوحة الإدارة
3. اذهب إلى "AskMe" في القائمة الجانبية
4. قم بتكوين الإعدادات الأساسية
5. أنشئ صفحات وضف الشورت كود المناسبة

## الشورت كود المتاحة

### الشورت كود الأساسية
- `[askro_archive]` - صفحة أرشيف الأسئلة
- `[askro_single_question]` - صفحة السؤال الواحد
- `[askro_ask_question_form]` - نموذج طرح السؤال
- `[askro_user_profile]` - صفحة الملف الشخصي

### الشورت كود المكونات
- `[askro_questions_list]` - قائمة الأسئلة
- `[askro_leaderboard]` - قائمة المتصدرين
- `[askro_user_stat]` - إحصائيات المستخدم
- `[askro_community_stat]` - إحصائيات المجتمع

## نظام التصويت المتعدد الأبعاد

### أنواع التصويت
- **مفيد (✔️)**: قيمة +3 نقاط
- **مبدع (🧠)**: قيمة +2 نقاط
- **عاطفي (❤️)**: قيمة +2 نقاط
- **سام (☠️)**: قيمة -2 نقاط
- **خارج الموضوع (🔄)**: قيمة -1 نقطة

### الميزات
- تصويت فوري بدون إعادة تحميل الصفحة
- عرض إجمالي النقاط والعدد
- منع التصويت المتكرر
- نظام أوزان متقدم للمستخدمين المتميزين

## نظام التعليقات المتداخلة

### الميزات
- تعليقات متداخلة مع ردود
- تفاعلات صغيرة (👍، ❤️، 🔥)
- تحرير وحذف التعليقات
- تحميل تدريجي للتعليقات
- نظام صلاحيات متقدم

### التفاعلات
- **إعجاب (👍)**: إعجاب بالتعليق
- **حب (❤️)**: حب للتعليق
- **رائع (🔥)**: تعبير عن الإعجاب الشديد

## نظام البحث المتقدم

### الميزات
- بحث فوري مع اقتراحات
- فلاتر متقدمة (التصنيف، الحالة، التاريخ، المؤلف)
- تاريخ البحث
- إحصائيات البحث
- نتائج مرتبة حسب الأهمية

### الفلاتر المتاحة
- **التصنيف**: تصفية حسب تصنيف السؤال
- **الحالة**: مفتوح، محلول، مغلق، عاجل
- **التاريخ**: نطاق زمني محدد
- **المؤلف**: تصفية حسب كاتب السؤال
- **العلامات**: تصفية حسب العلامات

## نظام التصفية المتقدم

### خيارات التصفية
- **التصنيفات**: تصفية حسب تصنيف واحد أو أكثر
- **العلامات**: تصفية حسب علامات محددة
- **الحالة**: تصفية حسب حالة السؤال
- **نطاق التصويت**: تصفية حسب عدد التصويتات
- **نطاق الإجابات**: تصفية حسب عدد الإجابات
- **المؤلفون**: تصفية حسب مؤلفي الأسئلة
- **الأسئلة المحلولة فقط**: عرض الأسئلة المحلولة فقط
- **الأسئلة بدون إجابة**: عرض الأسئلة بدون إجابة
- **الأسئلة مع مرفقات**: عرض الأسئلة التي تحتوي على مرفقات

### خيارات الترتيب
- **التاريخ**: ترتيب حسب تاريخ النشر
- **التصويت**: ترتيب حسب عدد التصويتات
- **الإجابات**: ترتيب حسب عدد الإجابات
- **المشاهدات**: ترتيب حسب عدد المشاهدات
- **العنوان**: ترتيب أبجدي حسب العنوان

## نظام النقاط والرتب

### نظام النقاط
- **طرح سؤال**: +10 نقاط
- **إضافة إجابة**: +20 نقطة
- **إضافة تعليق**: +5 نقاط
- **التصويت الإيجابي**: +1 نقطة
- **التصويت السلبي**: -1 نقطة
- **أفضل إجابة**: +50 نقطة

### نظام الرتب
- **مبتدئ**: 0-500 نقطة
- **مساهم**: 501-1500 نقطة
- **خبير**: 1501-3000 نقطة
- **مستشار**: 3001-5000 نقطة
- **أسطورة**: 5001+ نقطة

## الإعدادات المتقدمة

### إعدادات عامة
- تعيين صفحات النظام
- إعدادات الوصول والصلاحيات
- إعدادات PWA

### إعدادات التصميم
- نظام الألوان المخصص
- الخطوط والتصميم
- إعدادات التخطيط
- CSS مخصص

### إعدادات النقاط
- قيم النقاط لكل إجراء
- أوزان التصويت
- نظام التحلل
- نظام مكافحة الاحتيال

## الملفات الرئيسية

### الملفات الأساسية
- `askro.php` - الملف الرئيسي للإضافة
- `includes/classes/class-admin.php` - لوحة الإدارة
- `includes/classes/class-shortcodes.php` - الشورت كود
- `includes/classes/class-ajax.php` - معالجة AJAX

### ملفات قاعدة البيانات
- `includes/classes/class-database.php` - إدارة قاعدة البيانات
- `includes/classes/class-voting.php` - نظام التصويت
- `includes/classes/class-comments.php` - نظام التعليقات

### ملفات الواجهة
- `assets/js/src/askme-shortcodes.js` - JavaScript الرئيسي
- `assets/css/src/askme-shortcodes.css` - CSS الرئيسي
- `templates/frontend/` - قوالب الواجهة

## الدعم والمساعدة

### التوثيق
- [دليل التثبيت](docs/INSTALLATION.md)
- [دليل API](docs/API.md)
- [دليل الشورت كود](SHORTCODES_GUIDE.md)

### المساعدة
- الموقع الرسمي: https://arashdi.com
- البريد الإلكتروني: arashdi@wratcliff.dev

## الترخيص

هذا المشروع مرخص تحت رخصة GPL-3.0-or-later.

## التحديثات

### الإصدار 1.0.0
- ✅ إضافة نظام التصويت المتعدد الأبعاد
- ✅ إضافة نظام التعليقات المتداخلة
- ✅ إضافة نظام البحث المتقدم
- ✅ إضافة نظام التصفية المتقدم
- ✅ تحسينات في الأداء والاستقرار
- ✅ إصلاحات الأخطاء

## المساهمة

نرحب بالمساهمات! يرجى قراءة [دليل المساهمة](CONTRIBUTING.md) قبل البدء.

## الشكر والتقدير

شكر خاص لجميع المساهمين والمطورين الذين ساعدوا في تطوير هذا المشروع.

#   C o d e R a b b i t   T e s t 
 
 