# دليل التثبيت والإعداد - Askro

## 📋 المتطلبات الأساسية

### متطلبات الخادم

| المكون | الحد الأدنى | الموصى به | ملاحظات |
|--------|-------------|------------|----------|
| **WordPress** | 5.0+ | 6.0+ | يجب أن يكون محدث |
| **PHP** | 7.4+ | 8.0+ | مع دعم MySQLi |
| **MySQL** | 5.6+ | 8.0+ | أو MariaDB 10.3+ |
| **الذاكرة** | 128MB | 256MB+ | للمواقع الكبيرة |
| **مساحة القرص** | 50MB | 100MB+ | للملفات والتخزين المؤقت |

### إضافات PHP المطلوبة

```bash
# التحقق من الإضافات المطلوبة
php -m | grep -E "(mysqli|json|mbstring|curl|gd|zip)"
```

**الإضافات الأساسية:**
- `mysqli` - للاتصال بقاعدة البيانات
- `json` - لمعالجة JSON
- `mbstring` - لدعم النصوص متعددة البايت
- `curl` - للطلبات الخارجية
- `gd` أو `imagick` - لمعالجة الصور
- `zip` - لضغط الملفات

**الإضافات الاختيارية (للأداء الأفضل):**
- `opcache` - لتسريع PHP
- `redis` أو `memcached` - للتخزين المؤقت
- `intl` - لدعم التدويل

### إعدادات PHP الموصى بها

```ini
# في ملف php.ini
memory_limit = 256M
max_execution_time = 300
max_input_vars = 3000
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 20

# للأمان
allow_url_fopen = Off
allow_url_include = Off
expose_php = Off
```

## 🚀 طرق التثبيت

### 1. التثبيت عبر لوحة إدارة ووردبريس

#### الطريقة الأولى: رفع ملف ZIP

1. **تحميل البلجن**
   - احصل على ملف `askro-plugin.zip`
   - أو حمل أحدث إصدار من [GitHub Releases](https://github.com/arashdi/askro-plugin/releases)

2. **رفع البلجن**
   ```
   لوحة الإدارة → الإضافات → أضف جديد → رفع إضافة
   ```
   - اختر ملف `askro-plugin.zip`
   - انقر "تثبيت الآن"

3. **تفعيل البلجن**
   - انقر "تفعيل الإضافة" بعد التثبيت
   - أو اذهب إلى **الإضافات** وفعل "Askro"

#### الطريقة الثانية: البحث في مستودع ووردبريس

```
لوحة الإدارة → الإضافات → أضف جديد → البحث عن "Askro"
```

### 2. التثبيت اليدوي عبر FTP

1. **تحميل الملفات**
   ```bash
   # تحميل من GitHub
   wget https://github.com/arashdi/askro-plugin/archive/main.zip
   unzip main.zip
   ```

2. **رفع الملفات**
   ```bash
   # رفع عبر FTP أو cPanel File Manager
   /wp-content/plugins/askro-plugin/
   ```

3. **ضبط الصلاحيات**
   ```bash
   # صلاحيات المجلدات
   find /path/to/wp-content/plugins/askro-plugin/ -type d -exec chmod 755 {} \;
   
   # صلاحيات الملفات
   find /path/to/wp-content/plugins/askro-plugin/ -type f -exec chmod 644 {} \;
   ```

4. **تفعيل البلجن**
   - اذهب إلى **الإضافات** في لوحة الإدارة
   - ابحث عن "Askro" وانقر **تفعيل**

### 3. التثبيت عبر WP-CLI

```bash
# تثبيت من مستودع ووردبريس
wp plugin install askro --activate

# أو تثبيت من GitHub
wp plugin install https://github.com/arashdi/askro-plugin/archive/main.zip --activate

# التحقق من التثبيت
wp plugin status askro
```

### 4. التثبيت عبر Composer

```bash
# إضافة إلى composer.json
composer require arashdi/askro-plugin

# أو تثبيت مباشر
composer install arashdi/askro-plugin
```

## ⚙️ الإعداد الأولي

### 1. التحقق من متطلبات النظام

بعد التفعيل، اذهب إلى **Askro → فحص النظام** للتحقق من:

- ✅ إصدار ووردبريس
- ✅ إصدار PHP وإضافاته
- ✅ إعدادات قاعدة البيانات
- ✅ صلاحيات الملفات
- ✅ إعدادات الأمان

```bash
# أو استخدم WP-CLI
wp askro system-check
```

### 2. إعداد قاعدة البيانات

سيتم إنشاء الجداول تلقائياً عند التفعيل. للتحقق:

```bash
# فحص الجداول
wp askro check-database

# إعادة إنشاء الجداول (إذا لزم الأمر)
wp askro setup-database --force
```

**الجداول التي سيتم إنشاؤها:**

| الجدول | الوصف |
|--------|-------|
| `askro_votes` | تصويتات الأسئلة والإجابات |
| `askro_comments` | تعليقات مخصصة |
| `askro_user_points` | نقاط المستخدمين |
| `askro_user_badges` | شارات المستخدمين |
| `askro_user_achievements` | إنجازات المستخدمين |
| `askro_user_followers` | متابعات المستخدمين |
| `askro_user_stats` | إحصائيات المستخدمين |
| `askro_notifications` | الإشعارات |
| `askro_analytics` | بيانات التحليلات |
| `askro_daily_analytics` | التحليلات اليومية |
| `askro_security_logs` | سجلات الأمان |
| `askro_file_uploads` | الملفات المرفوعة |
| `askro_user_sessions` | جلسات المستخدمين |

### 3. الإعدادات الأساسية

اذهب إلى **Askro → الإعدادات** وقم بتكوين:

#### الإعدادات العامة

```php
// أو عبر wp-config.php
define('ASKRO_SITE_NAME', 'منتدى الأسئلة');
define('ASKRO_SITE_DESCRIPTION', 'مجتمع للأسئلة والأجوبة');
define('ASKRO_DEFAULT_LANGUAGE', 'ar');
```

#### إعدادات النقاط

| النشاط | النقاط الافتراضية | القابل للتخصيص |
|--------|------------------|-----------------|
| طرح سؤال | 5 | ✅ |
| نشر إجابة | 10 | ✅ |
| قبول إجابة | 15 | ✅ |
| تصويت مفيد | 2 | ✅ |
| تصويت إبداعي | 3 | ✅ |
| تصويت عميق | 4 | ✅ |
| تصويت مضحك | 2 | ✅ |
| تصويت عاطفي | 3 | ✅ |

#### إعدادات التصويت

```php
// تخصيص أنواع التصويت
add_filter('askro_vote_types', function($types) {
    // تعطيل نوع معين
    unset($types['funny']);
    
    // تخصيص نوع موجود
    $types['helpful']['points'] = 5;
    
    // إضافة نوع جديد
    $types['expert'] = [
        'name' => 'خبير',
        'icon' => '🎓',
        'color' => '#8B5CF6',
        'points' => 10
    ];
    
    return $types;
});
```

#### إعدادات الأمان

| الإعداد | الافتراضي | الوصف |
|---------|-----------|-------|
| حد الأسئلة/ساعة | 5 | عدد الأسئلة المسموح بها |
| حد الإجابات/ساعة | 10 | عدد الإجابات المسموح بها |
| حد التعليقات/ساعة | 20 | عدد التعليقات المسموح بها |
| حد التصويتات/ساعة | 100 | عدد التصويتات المسموح بها |
| حجم الملف الأقصى | 10MB | حجم الملفات المرفوعة |

### 4. إنشاء الصفحات الأساسية

قم بإنشاء الصفحات التالية وأضف الشورت كودز:

#### صفحة الأسئلة الرئيسية

```php
// إنشاء صفحة جديدة
العنوان: "الأسئلة"
المحتوى: [askro_questions_list]
الرابط الثابت: /questions/
```

#### صفحة طرح سؤال

```php
العنوان: "اطرح سؤالاً"
المحتوى: [askro_ask_question]
الرابط الثابت: /ask-question/
```

#### صفحة لوحة المتصدرين

```php
العنوان: "لوحة المتصدرين"
المحتوى: [askro_leaderboard]
الرابط الثابت: /leaderboard/
```

#### صفحة الملف الشخصي

```php
العنوان: "الملف الشخصي"
المحتوى: [askro_user_profile]
الرابط الثابت: /profile/
```

#### إنشاء الصفحات تلقائياً

```bash
# استخدام WP-CLI
wp askro create-pages

# أو عبر PHP
askro_create_default_pages();
```

### 5. تكوين القوائم

أضف روابط Askro إلى قوائم الموقع:

```
المظهر → القوائم → إنشاء قائمة جديدة

أضف العناصر التالية:
- الأسئلة (/questions/)
- اطرح سؤالاً (/ask-question/)
- لوحة المتصدرين (/leaderboard/)
- ملفي الشخصي (/profile/)
```

### 6. تخصيص الصلاحيات

#### صلاحيات المستخدمين

```php
// تخصيص الصلاحيات
add_action('init', function() {
    // السماح للمشتركين بنشر الأسئلة
    $subscriber = get_role('subscriber');
    $subscriber->add_cap('askro_post_questions');
    $subscriber->add_cap('askro_post_answers');
    $subscriber->add_cap('askro_vote');
    
    // صلاحيات إضافية للمحررين
    $editor = get_role('editor');
    $editor->add_cap('askro_moderate_content');
    $editor->add_cap('askro_manage_points');
    
    // صلاحيات كاملة للمدراء
    $admin = get_role('administrator');
    $admin->add_cap('askro_full_access');
});
```

#### إنشاء أدوار مخصصة

```php
// إنشاء دور "مشرف الأسئلة"
add_role('askro_moderator', 'مشرف الأسئلة', [
    'read' => true,
    'askro_post_questions' => true,
    'askro_post_answers' => true,
    'askro_moderate_content' => true,
    'askro_edit_others_posts' => true,
    'askro_delete_posts' => true
]);
```

## 🎨 تخصيص المظهر

### 1. نسخ ملفات القوالب

```bash
# نسخ قوالب Askro إلى القالب النشط
cp -r /wp-content/plugins/askro-plugin/templates/ /wp-content/themes/your-theme/askro/

# أو استخدام WP-CLI
wp askro copy-templates
```

**ملفات القوالب المتاحة:**

| الملف | الوصف |
|-------|-------|
| `questions-list.php` | قائمة الأسئلة |
| `single-question.php` | صفحة السؤال الواحد |
| `ask-question-form.php` | نموذج طرح سؤال |
| `answer-form.php` | نموذج الإجابة |
| `user-profile.php` | الملف الشخصي |
| `leaderboard.php` | لوحة المتصدرين |
| `search-results.php` | نتائج البحث |

### 2. تخصيص الألوان والتصميم

```css
/* إضافة إلى style.css في القالب */
:root {
  --askro-primary: #4F46E5;
  --askro-secondary: #10B981;
  --askro-accent: #F59E0B;
  --askro-neutral: #6B7280;
  --askro-base-100: #FFFFFF;
  --askro-base-200: #F9FAFB;
  --askro-base-300: #E5E7EB;
}

/* تخصيص أزرار التصويت */
.askro-vote-btn {
  background: var(--askro-primary);
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.askro-vote-btn:hover {
  background: var(--askro-secondary);
  transform: translateY(-2px);
}

.askro-vote-btn.voted {
  background: var(--askro-accent);
}

/* تخصيص كروت الأسئلة */
.askro-question-card {
  background: var(--askro-base-100);
  border: 1px solid var(--askro-base-300);
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.askro-question-card:hover {
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}
```

### 3. تخصيص JavaScript

```javascript
// إضافة إلى ملف JavaScript مخصص
document.addEventListener('DOMContentLoaded', function() {
    // تخصيص سلوك التصويت
    document.querySelectorAll('.askro-vote-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // إضافة تأثير بصري
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
    
    // تخصيص نماذج الإرسال
    const forms = document.querySelectorAll('.askro-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('[type="submit"]');
            submitBtn.textContent = 'جاري الإرسال...';
            submitBtn.disabled = true;
        });
    });
});
```

## 🔧 الإعدادات المتقدمة

### 1. تحسين الأداء

#### تفعيل التخزين المؤقت

```php
// في wp-config.php
define('ASKRO_ENABLE_CACHE', true);
define('ASKRO_CACHE_DURATION', 3600); // ساعة واحدة

// تخصيص إعدادات التخزين المؤقت
add_filter('askro_cache_config', function($config) {
    $config['questions_list'] = 1800; // 30 دقيقة
    $config['user_stats'] = 3600; // ساعة
    $config['leaderboard'] = 7200; // ساعتان
    return $config;
});
```

#### تحسين قاعدة البيانات

```bash
# تشغيل تحسين قاعدة البيانات
wp askro optimize-database

# جدولة التحسين التلقائي
wp cron event schedule askro_daily_optimization daily
```

#### ضغط الأصول

```php
// تفعيل ضغط CSS و JS
define('ASKRO_MINIFY_ASSETS', true);

// أو عبر الفلتر
add_filter('askro_minify_css', '__return_true');
add_filter('askro_minify_js', '__return_true');
```

### 2. إعدادات الأمان المتقدمة

#### تخصيص حدود المعدل

```php
add_filter('askro_rate_limits', function($limits) {
    // تخصيص حدود المعدل حسب دور المستخدم
    if (current_user_can('askro_moderator')) {
        $limits['question_post']['limit'] = 20; // 20 سؤال للمشرفين
    }
    
    // حدود أكثر صرامة للمستخدمين الجدد
    $user = wp_get_current_user();
    if ($user->user_registered > date('Y-m-d', strtotime('-7 days'))) {
        $limits['question_post']['limit'] = 2; // سؤالان فقط للمستخدمين الجدد
    }
    
    return $limits;
});
```

#### إضافة نطاقات محظورة

```php
add_filter('askro_blocked_domains', function($domains) {
    $domains[] = 'spam-site.com';
    $domains[] = 'malicious-domain.net';
    return $domains;
});
```

#### تخصيص فحص الأمان

```php
add_filter('askro_security_patterns', function($patterns) {
    // إضافة أنماط مشبوهة جديدة
    $patterns['custom_threats'] = [
        '/malicious_pattern/i',
        '/another_threat/i'
    ];
    return $patterns;
});
```

### 3. تخصيص الإشعارات

#### إعداد البريد الإلكتروني

```php
// تخصيص قوالب البريد الإلكتروني
add_filter('askro_email_templates', function($templates) {
    $templates['new_answer'] = [
        'subject' => 'إجابة جديدة على سؤالك: {question_title}',
        'body' => 'مرحباً {user_name}،\n\nتم نشر إجابة جديدة على سؤالك...'
    ];
    return $templates;
});

// تخصيص إعدادات SMTP
add_action('phpmailer_init', function($phpmailer) {
    if (defined('ASKRO_SMTP_HOST')) {
        $phpmailer->isSMTP();
        $phpmailer->Host = ASKRO_SMTP_HOST;
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = ASKRO_SMTP_USER;
        $phpmailer->Password = ASKRO_SMTP_PASS;
        $phpmailer->SMTPSecure = 'tls';
        $phpmailer->Port = 587;
    }
});
```

#### إعداد إشعارات المتصفح

```javascript
// طلب إذن الإشعارات
if ('Notification' in window && 'serviceWorker' in navigator) {
    Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
            // تسجيل Service Worker للإشعارات
            navigator.serviceWorker.register('/wp-content/plugins/askro-plugin/assets/js/sw.js');
        }
    });
}
```

### 4. التكامل مع الخدمات الخارجية

#### تكامل مع Google Analytics

```php
add_action('askro_question_viewed', function($question_id) {
    // إرسال حدث إلى Google Analytics
    if (function_exists('gtag')) {
        echo "<script>gtag('event', 'question_view', {'question_id': {$question_id}});</script>";
    }
});
```

#### تكامل مع Slack

```php
add_action('askro_question_posted', function($question_id, $user_id) {
    $question = get_post($question_id);
    $user = get_user_by('id', $user_id);
    
    $message = sprintf(
        'سؤال جديد من %s: %s',
        $user->display_name,
        $question->post_title
    );
    
    // إرسال إلى Slack
    wp_remote_post(SLACK_WEBHOOK_URL, [
        'body' => json_encode(['text' => $message]),
        'headers' => ['Content-Type' => 'application/json']
    ]);
}, 10, 2);
```

## 🔍 استكشاف الأخطاء

### مشاكل التثبيت الشائعة

#### خطأ: "Plugin could not be activated"

```bash
# التحقق من سجلات الأخطاء
tail -f /var/log/apache2/error.log
# أو
tail -f /var/log/nginx/error.log

# التحقق من صلاحيات الملفات
ls -la /wp-content/plugins/askro-plugin/

# إصلاح الصلاحيات
chown -R www-data:www-data /wp-content/plugins/askro-plugin/
chmod -R 755 /wp-content/plugins/askro-plugin/
```

#### خطأ: "Database tables not created"

```bash
# فحص قاعدة البيانات
wp askro check-database

# إعادة إنشاء الجداول
wp askro setup-database --force

# التحقق من صلاحيات قاعدة البيانات
mysql -u username -p -e "SHOW GRANTS FOR 'wp_user'@'localhost';"
```

#### خطأ: "Memory limit exceeded"

```php
// زيادة حد الذاكرة في wp-config.php
ini_set('memory_limit', '256M');

// أو في .htaccess
php_value memory_limit 256M
```

### مشاكل الأداء

#### بطء في تحميل الصفحات

```bash
# تفعيل التخزين المؤقت
wp askro enable-cache

# تحسين قاعدة البيانات
wp askro optimize-database

# فحص الاستعلامات البطيئة
wp askro analyze-queries
```

#### استهلاك عالي للذاكرة

```php
// مراقبة استخدام الذاكرة
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        echo '<!-- Memory Usage: ' . memory_get_peak_usage(true) / 1024 / 1024 . ' MB -->';
    }
});
```

### مشاكل الأمان

#### تنبيهات أمنية متكررة

```bash
# فحص سجلات الأمان
wp askro security-logs --limit=50

# تحديث قائمة IP المحظورة
wp askro block-ip 192.168.1.100 --reason="Suspicious activity"

# إعادة تعيين إعدادات الأمان
wp askro reset-security-settings
```

### أدوات التشخيص

#### معلومات النظام

```bash
# عرض معلومات شاملة عن النظام
wp askro system-info

# تصدير تقرير تشخيصي
wp askro export-diagnostics --format=json
```

#### اختبار الوظائف

```bash
# تشغيل جميع الاختبارات
wp askro test

# اختبار وظيفة محددة
wp askro test --test=database_connection

# اختبار الأداء
wp askro test --category=performance
```

## 📊 المراقبة والصيانة

### 1. المراقبة التلقائية

#### إعداد مراقبة الأداء

```php
// في wp-config.php
define('ASKRO_ENABLE_MONITORING', true);
define('ASKRO_PERFORMANCE_THRESHOLD', 2.0); // ثانيتان

// تنبيه عند تجاوز الحد
add_action('askro_performance_alert', function($page, $load_time) {
    $message = sprintf('تحذير: صفحة %s تستغرق %.2f ثانية للتحميل', $page, $load_time);
    wp_mail(get_option('admin_email'), 'تنبيه أداء Askro', $message);
});
```

#### مراقبة قاعدة البيانات

```bash
# جدولة فحص يومي لقاعدة البيانات
wp cron event schedule askro_daily_db_check daily

# مراقبة حجم الجداول
wp askro monitor-database-size
```

### 2. الصيانة الدورية

#### تنظيف البيانات القديمة

```bash
# تنظيف سجلات الأمان القديمة (أكثر من 30 يوم)
wp askro cleanup --type=security_logs --days=30

# تنظيف التحليلات القديمة (أكثر من سنة)
wp askro cleanup --type=analytics --days=365

# تنظيف الإشعارات المقروءة القديمة
wp askro cleanup --type=notifications --days=90
```

#### تحسين الأداء الدوري

```bash
# جدولة تحسين أسبوعي
wp cron event schedule askro_weekly_optimization weekly

# تحسين فهارس قاعدة البيانات
wp askro optimize-indexes

# ضغط الجداول
wp askro compress-tables
```

### 3. النسخ الاحتياطي

#### نسخ احتياطي للبيانات

```bash
# نسخ احتياطي لبيانات Askro فقط
wp askro backup --type=data --output=/backups/askro-data.sql

# نسخ احتياطي للإعدادات
wp askro backup --type=settings --output=/backups/askro-settings.json

# نسخ احتياطي شامل
wp askro backup --type=full --output=/backups/askro-full-backup.zip
```

#### استعادة البيانات

```bash
# استعادة البيانات
wp askro restore --file=/backups/askro-data.sql --type=data

# استعادة الإعدادات
wp askro restore --file=/backups/askro-settings.json --type=settings
```

## 🔄 التحديث والترقية

### 1. تحديث البلجن

#### التحديث التلقائي

```php
// تفعيل التحديثات التلقائية
add_filter('auto_update_plugin', function($update, $item) {
    if ($item->slug === 'askro-plugin') {
        return true; // تفعيل التحديث التلقائي
    }
    return $update;
}, 10, 2);
```

#### التحديث اليدوي

```bash
# التحقق من وجود تحديثات
wp plugin update --dry-run askro

# تحديث البلجن
wp plugin update askro

# التحقق من الإصدار
wp plugin status askro
```

### 2. ترقية قاعدة البيانات

```bash
# فحص الحاجة للترقية
wp askro check-database-version

# تشغيل ترقية قاعدة البيانات
wp askro upgrade-database

# التحقق من سلامة البيانات بعد الترقية
wp askro verify-data-integrity
```

### 3. ترحيل البيانات

#### من إصدار قديم

```bash
# ترحيل البيانات من إصدار 1.x إلى 2.x
wp askro migrate --from=1.x --to=2.x

# التحقق من نجاح الترحيل
wp askro verify-migration
```

#### إلى خادم جديد

```bash
# تصدير البيانات من الخادم القديم
wp askro export --format=json --output=askro-export.json

# استيراد البيانات في الخادم الجديد
wp askro import --file=askro-export.json
```

## 🌐 البيئات المتعددة

### 1. بيئة التطوير

```php
// في wp-config.php للتطوير
define('ASKRO_DEBUG', true);
define('ASKRO_LOG_LEVEL', 'debug');
define('ASKRO_ENABLE_PROFILING', true);
define('ASKRO_CACHE_DISABLED', true);
```

### 2. بيئة الاختبار

```php
// إعدادات بيئة الاختبار
define('ASKRO_TESTING_MODE', true);
define('ASKRO_FAKE_DATA', true);
define('ASKRO_DISABLE_EMAILS', true);
```

### 3. بيئة الإنتاج

```php
// إعدادات الإنتاج
define('ASKRO_DEBUG', false);
define('ASKRO_ENABLE_CACHE', true);
define('ASKRO_MINIFY_ASSETS', true);
define('ASKRO_SECURITY_STRICT', true);
```

## 📞 الحصول على المساعدة

### موارد الدعم

- **التوثيق الكامل**: [docs.askro.com](https://docs.askro.com)
- **منتدى المجتمع**: [community.askro.com](https://community.askro.com)
- **GitHub Issues**: [github.com/arashdi/askro-plugin/issues](https://github.com/arashdi/askro-plugin/issues)
- **البريد الإلكتروني**: support@askro.com

### قبل طلب المساعدة

1. **تحقق من سجلات الأخطاء**
   ```bash
   wp askro logs --type=error --limit=20
   ```

2. **شغل فحص النظام**
   ```bash
   wp askro system-check
   ```

3. **جرب الاختبارات التشخيصية**
   ```bash
   wp askro test --category=all
   ```

4. **اجمع معلومات النظام**
   ```bash
   wp askro system-info > system-info.txt
   ```

### معلومات مطلوبة عند طلب الدعم

- إصدار ووردبريس
- إصدار PHP
- إصدار Askro
- وصف المشكلة
- خطوات إعادة الإنتاج
- سجلات الأخطاء
- لقطات شاشة (إن أمكن)

---

**تهانينا! 🎉** لقد أكملت تثبيت وإعداد Askro بنجاح. استمتع بنظام الأسئلة والأجوبة المتطور!

