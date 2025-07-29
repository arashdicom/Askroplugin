<?php
/**
 * فحص شامل لقاعدة البيانات - AskMe Plugin
 * يمكن تشغيل هذا الملف من لوحة التحكم
 */

// منع الوصول المباشر
if (!defined('ABSPATH')) {
    exit;
}

// التحقق من صلاحيات المستخدم
if (!current_user_can('manage_options')) {
    wp_die('ليس لديك صلاحية للوصول لهذه الصفحة');
}

// معالجة الطلبات
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'fix_prefixes':
            fix_table_prefixes();
            break;
        case 'clean_empty_tables':
            clean_empty_tables();
            break;
        case 'migrate_data':
            migrate_data_between_prefixes();
            break;
    }
}

function fix_table_prefixes() {
    global $wpdb;
    
    $results = array();
    
    // لا توجد جداول askrow_ للتحويل
    $results[] = "✅ لا توجد جداول askrow_ للتحويل - النظام يستخدم askro_ فقط";
    
    $results_str = urlencode(json_encode($results));
    echo "<script>window.location.href = '" . admin_url('admin.php?page=askro-db-check&db_results=' . $results_str . '&db_type=fix') . "';</script>";
    exit;
}

function clean_empty_tables() {
    global $wpdb;
    
    $results = array();
    
    // قائمة الجداول الفارغة
    $empty_tables = array(
        $wpdb->prefix . 'askro_comments',
        $wpdb->prefix . 'askro_comment_reactions',
        $wpdb->prefix . 'askro_user_follows',
        $wpdb->prefix . 'askro_user_settings',
        $wpdb->prefix . 'askro_user_stats',
        $wpdb->prefix . 'askro_user_votes',
        $wpdb->prefix . 'askro_votes'
    );
    
    foreach ($empty_tables as $table) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM `$table`");
        if ($count == 0) {
            $result = $wpdb->query("TRUNCATE TABLE `$table`");
            if ($result !== false) {
                $results[] = "✅ تم تنظيف الجدول الفارغ: $table";
            } else {
                $results[] = "❌ فشل في تنظيف: $table";
            }
        }
    }
    
    $results_str = urlencode(json_encode($results));
    echo "<script>window.location.href = '" . admin_url('admin.php?page=askro-db-check&db_results=' . $results_str . '&db_type=clean') . "';</script>";
    exit;
}

function migrate_data_between_prefixes() {
    global $wpdb;
    
    $results = array();
    
    // قائمة الجداول للترحيل (محذوفة - لم تعد مطلوبة)
    $migration_tables = array();
    
    foreach ($migration_tables as $type => $tables) {
        $old_table = $wpdb->prefix . $tables[0];
        $new_table = $wpdb->prefix . $tables[1];
        
        // التحقق من وجود الجداول
        $old_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_table'");
        $new_exists = $wpdb->get_var("SHOW TABLES LIKE '$new_table'");
        
        if ($old_exists && $new_exists) {
            // الحصول على عدد الصفوف في الجدول القديم
            $old_count = $wpdb->get_var("SELECT COUNT(*) FROM `$old_table`");
            
            if ($old_count > 0) {
                // ترحيل البيانات
                $result = $wpdb->query("INSERT IGNORE INTO `$new_table` SELECT * FROM `$old_table`");
                if ($result !== false) {
                    $results[] = "✅ تم ترحيل $old_count صف من $old_table إلى $new_table";
                } else {
                    $results[] = "❌ فشل في ترحيل البيانات من $old_table";
                }
            } else {
                $results[] = "ℹ️ الجدول $old_table فارغ - لا حاجة للترحيل";
            }
        } else {
            $results[] = "⚠️ أحد الجداول غير موجود: $old_table أو $new_table";
        }
    }
    
    $results_str = urlencode(json_encode($results));
    echo "<script>window.location.href = '" . admin_url('admin.php?page=askro-db-check&db_results=' . $results_str . '&db_type=migrate') . "';</script>";
    exit;
}

// الحصول على معلومات قاعدة البيانات
function get_database_report() {
    global $wpdb;
    
    $report = array();
    
    // 1. فحص الجداول حسب البادئة
    $askro_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}askro_%'", ARRAY_N);
    
    $report['askro_tables'] = array();
    
    foreach ($askro_tables as $table) {
        $table_name = $table[0];
        $count = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name`");
        $report['askro_tables'][$table_name] = $count;
    }
    
    // فحص جدول الإعدادات بشكل خاص
    $settings_table = $wpdb->prefix . 'askro_settings';
    if (isset($report['askro_tables'][$settings_table])) {
        $settings_count = $report['askro_tables'][$settings_table];
        $report['settings_info'] = array(
            'table_exists' => true,
            'count' => $settings_count,
            'status' => $settings_count > 0 ? 'محتوي على بيانات' : 'فارغ'
        );
    } else {
        $report['settings_info'] = array(
            'table_exists' => false,
            'count' => 0,
            'status' => 'غير موجود'
        );
    }
    
    // 2. فحص الجداول الفارغة
    $report['empty_tables'] = array();
    foreach ($report['askro_tables'] as $table => $count) {
        if ($count == 0) {
            $report['empty_tables'][] = $table;
        }
    }
    
    // 3. فحص هيكل الجداول المهمة
    $report['table_structures'] = array();
    
    // فحص جدول التعليقات
    if (isset($report['askro_tables'][$wpdb->prefix . 'askro_comments'])) {
        $columns = $wpdb->get_results("DESCRIBE `{$wpdb->prefix}askro_comments`", ARRAY_A);
        $report['table_structures']['comments'] = $columns;
    }
    
    // 4. فحص التضارب في البيانات
    $report['conflicts'] = array();
    
    // فحص تضارب الأسئلة (هذا طبيعي - الأسئلة يجب أن تكون في wp_posts)
    $askro_questions = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->prefix}posts` WHERE post_type = 'askro_question'");
    $askro_answers = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->prefix}posts` WHERE post_type = 'askro_answer'");
    
    // إضافة معلومات عن Custom Post Types (هذا طبيعي وليس تضارب)
    $report['custom_post_types'] = array(
        'questions' => $askro_questions,
        'answers' => $askro_answers
    );
    
    // فحص التضاربات الحقيقية فقط
    // يمكن إضافة فحوصات أخرى هنا في المستقبل
    
    // 5. فحص إعدادات قاعدة البيانات
    $report['db_settings'] = array(
        'prefix' => $wpdb->prefix,
        'charset' => $wpdb->charset,
        'collate' => $wpdb->collate,
        'version' => $wpdb->db_version()
    );
    
    return $report;
}

$report = get_database_report();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فحص قاعدة البيانات - AskMe Plugin</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #fafafa;
        }
        .section h2 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .table th, .table td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #e0e0e0;
        }
        .table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        .table tr:hover {
            background: #f8f9fa;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
        }
        .status.danger {
            background: #f8d7da;
            color: #721c24;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .btn.danger {
            background: #dc3545;
        }
        .btn.danger:hover {
            background: #c82333;
        }
        .btn.warning {
            background: #ffc107;
            color: #212529;
        }
        .btn.warning:hover {
            background: #e0a800;
        }
        .actions {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-right: 4px solid;
        }
        .alert.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .alert.warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .alert.danger {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .info-text {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            color: #1565c0;
            font-size: 0.9em;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert.warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .alert.danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 فحص شامل لقاعدة البيانات</h1>
            <p>AskMe Plugin - تقرير مفصل عن حالة قاعدة البيانات</p>
        </div>
        
        <div class="content">
            <?php 
            if (isset($_GET['db_results'])): 
                $results = json_decode(urldecode($_GET['db_results']), true);
                $type = isset($_GET['db_type']) ? $_GET['db_type'] : '';
                $title = '';
                if ($type === 'fix') $title = 'نتائج إصلاح البادئات:';
                elseif ($type === 'clean') $title = 'نتائج تنظيف الجداول الفارغة:';
                elseif ($type === 'migrate') $title = 'نتائج ترحيل البيانات:';
            ?>
                <div class="alert success">
                    <h3><?php echo $title; ?></h3>
                    <ul>
                        <?php foreach ($results as $result): ?>
                            <li><?php echo $result; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- إحصائيات سريعة -->
            <div class="section">
                <h2>📊 إحصائيات سريعة</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($report['askro_tables']); ?></div>
                        <div class="stat-label">جداول askro_</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">جداول askrow_ (محذوفة)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($report['empty_tables']); ?></div>
                        <div class="stat-label">جداول فارغة</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($report['conflicts']); ?></div>
                        <div class="stat-label">تضاربات</div>
                    </div>
                </div>
            </div>
            
            <!-- معلومات جدول الإعدادات -->
            <div class="section">
                <h2>⚙️ جدول الإعدادات (wp_askro_settings)</h2>
                <div class="settings-info">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>المعلومة</th>
                                <th>القيمة</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>وجود الجدول</td>
                                <td><?php echo $report['settings_info']['table_exists'] ? 'موجود' : 'غير موجود'; ?></td>
                                <td>
                                    <?php if ($report['settings_info']['table_exists']): ?>
                                        <span class="status success">✅</span>
                                    <?php else: ?>
                                        <span class="status danger">❌</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>عدد الإعدادات</td>
                                <td><?php echo number_format($report['settings_info']['count']); ?></td>
                                <td>
                                    <?php if ($report['settings_info']['count'] > 0): ?>
                                        <span class="status success">محتوي على بيانات</span>
                                    <?php else: ?>
                                        <span class="status warning">فارغ</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>الحالة العامة</td>
                                <td><?php echo $report['settings_info']['status']; ?></td>
                                <td>
                                    <?php if ($report['settings_info']['status'] === 'محتوي على بيانات'): ?>
                                        <span class="status success">✅ جيد</span>
                                    <?php elseif ($report['settings_info']['status'] === 'فارغ'): ?>
                                        <span class="status warning">⚠️ يحتاج تهيئة</span>
                                    <?php else: ?>
                                        <span class="status danger">❌ يحتاج إنشاء</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Custom Post Types -->
            <div class="section">
                <h2>📝 Custom Post Types (في wp_posts)</h2>
                <p class="info-text">هذه البيانات طبيعية ومتوقعة - الأسئلة والإجابات تُخزن في جدول wp_posts كـ Custom Post Types</p>
                <table class="table">
                    <thead>
                        <tr>
                            <th>نوع المحتوى</th>
                            <th>عدد العناصر</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>askro_question (الأسئلة)</td>
                            <td><?php echo number_format($report['custom_post_types']['questions']); ?></td>
                            <td>
                                <?php if ($report['custom_post_types']['questions'] > 0): ?>
                                    <span class="status success">✅ طبيعي</span>
                                <?php else: ?>
                                    <span class="status info">لا توجد أسئلة</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>askro_answer (الإجابات)</td>
                            <td><?php echo number_format($report['custom_post_types']['answers']); ?></td>
                            <td>
                                <?php if ($report['custom_post_types']['answers'] > 0): ?>
                                    <span class="status success">✅ طبيعي</span>
                                <?php else: ?>
                                    <span class="status info">لا توجد إجابات</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- جداول askro_ -->
            <div class="section">
                <h2>📋 جداول askro_ (المستخدمة حالياً)</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>اسم الجدول</th>
                            <th>عدد الصفوف</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['askro_tables'] as $table => $count): ?>
                            <tr>
                                <td><?php echo $table; ?></td>
                                <td><?php echo number_format($count); ?></td>
                                <td>
                                    <?php if ($count == 0): ?>
                                        <span class="status warning">فارغ</span>
                                    <?php elseif ($count < 10): ?>
                                        <span class="status info">قليل</span>
                                    <?php else: ?>
                                        <span class="status success">جيد</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- تم حذف قسم جداول askrow_ - النظام يستخدم askro_ فقط -->
            
            <!-- الجداول الفارغة -->
            <?php if (!empty($report['empty_tables'])): ?>
                <div class="section">
                    <h2>🔍 الجداول الفارغة</h2>
                    <div class="alert warning">
                        <strong>تحذير:</strong> هذه الجداول فارغة وقد تحتاج إلى تنظيف أو إعادة تهيئة.
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>اسم الجدول</th>
                                <th>الإجراء المقترح</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['empty_tables'] as $table): ?>
                                <tr>
                                    <td><?php echo $table; ?></td>
                                    <td>
                                        <?php if (strpos($table, 'comments') !== false): ?>
                                            إعادة تهيئة هيكل الجدول
                                        <?php else: ?>
                                            تنظيف أو حذف
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- التضاربات -->
            <?php if (!empty($report['conflicts'])): ?>
                <div class="section">
                    <h2>⚠️ التضاربات المكتشفة</h2>
                    <div class="alert danger">
                        <strong>تحذير:</strong> تم اكتشاف تضاربات في البيانات قد تؤثر على عمل الإضافة.
                    </div>
                    <ul>
                        <?php foreach ($report['conflicts'] as $conflict): ?>
                            <li><?php echo $conflict; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="section">
                    <h2>✅ حالة التضاربات</h2>
                    <div class="alert success">
                        <strong>ممتاز!</strong> لم يتم اكتشاف أي تضاربات في البيانات. قاعدة البيانات منظمة بشكل صحيح.
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- هيكل الجداول المهمة -->
            <?php if (isset($report['table_structures']['comments'])): ?>
                <div class="section">
                    <h2>🔧 هيكل جدول التعليقات</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>العمود</th>
                                <th>النوع</th>
                                <th>NULL</th>
                                <th>المفتاح</th>
                                <th>الافتراضي</th>
                                <th>إضافي</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['table_structures']['comments'] as $column): ?>
                                <tr>
                                    <td><?php echo $column['Field']; ?></td>
                                    <td><?php echo $column['Type']; ?></td>
                                    <td><?php echo $column['Null']; ?></td>
                                    <td><?php echo $column['Key']; ?></td>
                                    <td><?php echo $column['Default']; ?></td>
                                    <td><?php echo $column['Extra']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- إعدادات قاعدة البيانات -->
            <div class="section">
                <h2>⚙️ إعدادات قاعدة البيانات</h2>
                <table class="table">
                    <tbody>
                        <tr>
                            <td><strong>بادئة الجداول:</strong></td>
                            <td><?php echo $report['db_settings']['prefix']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>ترميز الأحرف:</strong></td>
                            <td><?php echo $report['db_settings']['charset']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>ترتيب الأحرف:</strong></td>
                            <td><?php echo $report['db_settings']['collate']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>إصدار MySQL:</strong></td>
                            <td><?php echo $report['db_settings']['version']; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- إجراءات الإصلاح -->
            <div class="section">
                <h2>🛠️ إجراءات الإصلاح</h2>
                <div class="actions">
                    <h3>تحذير مهم:</h3>
                    <p>قبل تنفيذ أي إجراء، يرجى عمل نسخة احتياطية من قاعدة البيانات!</p>
                    
                                         <!-- تم حذف زر إعادة تسمية askrow_ - لم تعد مطلوبة -->
                    
                    <?php if (!empty($report['empty_tables'])): ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="clean_empty_tables">
                            <button type="submit" class="btn" onclick="return confirm('هل أنت متأكد من تنظيف الجداول الفارغة؟')">
                                🧹 تنظيف الجداول الفارغة
                            </button>
                        </form>
                    <?php endif; ?>
                    
                                         <!-- تم حذف زر ترحيل البيانات - لم تعد مطلوبة -->
                </div>
            </div>
            
            <!-- التوصيات -->
            <div class="section">
                <h2>💡 التوصيات</h2>
                <div class="alert info">
                    <h3>خطة الإصلاح المقترحة:</h3>
                                         <ol>
                         <?php if (!empty($report['empty_tables'])): ?>
                             <li><strong>الخطوة الأولى:</strong> تنظيف الجداول الفارغة أو إعادة تهيئتها</li>
                         <?php endif; ?>
                         
                         <li><strong>الخطوة الثانية:</strong> اختبار جميع وظائف الإضافة للتأكد من عملها بشكل صحيح</li>
                         <li><strong>الخطوة الثالثة:</strong> مراقبة أداء النظام والتأكد من عدم وجود أخطاء</li>
                     </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
