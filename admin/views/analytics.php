<?php
/**
 * صفحة التحليلات المتقدمة
 * 
 * @package AskRow
 */

// منع الوصول المباشر
if (!defined('ABSPATH')) {
    exit;
}

// جلب بيانات التحليلات
$analytics_data = askro_get_analytics_data();
$date_range = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '30';
$chart_type = isset($_GET['chart_type']) ? sanitize_text_field($_GET['chart_type']) : 'line';
?>

<div class="wrap askro-admin-wrap">
    <!-- رأس الصفحة -->
    <div class="askro-admin-header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-1">📊 التحليلات المتقدمة</h1>
                <p class="text-gray-600 text-base lg:text-lg">تتبع أداء المجتمع ومقاييس التفاعل الشاملة</p>
            </div>
            
            <!-- أدوات التحكم -->
            <div class="flex flex-wrap items-center gap-2 lg:gap-4">
                <!-- تصدير البيانات -->
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-outline btn-sm lg:btn-lg">
                        <i class="fas fa-download mr-1 lg:mr-2"></i>
                        <span class="hidden sm:inline">تصدير البيانات</span>
                        <span class="sm:hidden">تصدير</span>
                    </label>
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-48 lg:w-64">
                        <li><a href="#" onclick="exportAnalytics('pdf')" class="text-sm lg:text-lg">
                            <i class="fas fa-file-pdf text-red-500 mr-2"></i> تصدير كـ PDF
                        </a></li>
                        <li><a href="#" onclick="exportAnalytics('csv')" class="text-sm lg:text-lg">
                            <i class="fas fa-file-csv text-green-500 mr-2"></i> تصدير كـ CSV
                        </a></li>
                        <li><a href="#" onclick="exportAnalytics('excel')" class="text-sm lg:text-lg">
                            <i class="fas fa-file-excel text-blue-500 mr-2"></i> تصدير كـ Excel
                        </a></li>
                        <li><a href="#" onclick="exportAnalytics('json')" class="text-sm lg:text-lg">
                            <i class="fas fa-file-code text-purple-500 mr-2"></i> تصدير كـ JSON
                        </a></li>
                    </ul>
                </div>
                
                <!-- نطاق التاريخ -->
                <select class="select select-bordered select-sm lg:select-lg" onchange="updateDateRange(this.value)">
                    <option value="7" <?php selected($date_range, '7'); ?>>آخر 7 أيام</option>
                    <option value="30" <?php selected($date_range, '30'); ?>>آخر 30 يوم</option>
                    <option value="90" <?php selected($date_range, '90'); ?>>آخر 90 يوم</option>
                    <option value="365" <?php selected($date_range, '365'); ?>>آخر سنة</option>
                    <option value="custom">نطاق مخصص</option>
                </select>
                
                <!-- تحديث -->
                <button class="btn btn-primary btn-sm lg:btn-lg" onclick="refreshAnalytics()">
                    <i class="fas fa-sync-alt mr-1 lg:mr-2"></i>
                    <span class="hidden sm:inline">تحديث البيانات</span>
                    <span class="sm:hidden">تحديث</span>
                </button>
            </div>
        </div>
    </div>

    <!-- البطاقات الرئيسية للمقاييس -->
    <div class="analytics-metrics-grid">
        <!-- إجمالي الأسئلة -->
        <div class="card bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-xl">
            <div class="card-body p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-lg mb-2">إجمالي الأسئلة</p>
                        <p class="text-4xl font-bold"><?php echo number_format(isset($analytics_data['total_questions']) ? $analytics_data['total_questions'] : 0); ?></p>
                        <div class="flex items-center mt-3">
                            <span class="text-sm <?php echo (isset($analytics_data['questions_change']) && $analytics_data['questions_change'] >= 0) ? 'text-green-300' : 'text-red-300'; ?>">
                                <i class="fas fa-arrow-<?php echo (isset($analytics_data['questions_change']) && $analytics_data['questions_change'] >= 0) ? 'up' : 'down'; ?> mr-1"></i>
                                <?php echo abs(isset($analytics_data['questions_change']) ? $analytics_data['questions_change'] : 0); ?>%
                            </span>
                            <span class="text-sm text-blue-200 mr-2">مقارنة بالفترة السابقة</span>
                        </div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-question-circle text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- إجمالي الإجابات -->
        <div class="card bg-gradient-to-br from-green-500 to-green-600 text-white shadow-xl">
            <div class="card-body p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-lg mb-2">إجمالي الإجابات</p>
                        <p class="text-4xl font-bold"><?php echo number_format(isset($analytics_data['total_answers']) ? $analytics_data['total_answers'] : 0); ?></p>
                        <div class="flex items-center mt-3">
                            <span class="text-sm <?php echo (isset($analytics_data['answers_change']) && $analytics_data['answers_change'] >= 0) ? 'text-green-300' : 'text-red-300'; ?>">
                                <i class="fas fa-arrow-<?php echo (isset($analytics_data['answers_change']) && $analytics_data['answers_change'] >= 0) ? 'up' : 'down'; ?> mr-1"></i>
                                <?php echo abs(isset($analytics_data['answers_change']) ? $analytics_data['answers_change'] : 0); ?>%
                            </span>
                            <span class="text-sm text-green-200 mr-2">مقارنة بالفترة السابقة</span>
                        </div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-comments text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- المستخدمون النشطون -->
        <div class="card bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-xl">
            <div class="card-body p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-lg mb-2">المستخدمون النشطون</p>
                        <p class="text-4xl font-bold"><?php echo number_format(isset($analytics_data['active_users']) ? $analytics_data['active_users'] : 0); ?></p>
                        <div class="flex items-center mt-3">
                            <span class="text-sm <?php echo (isset($analytics_data['users_change']) && $analytics_data['users_change'] >= 0) ? 'text-green-300' : 'text-red-300'; ?>">
                                <i class="fas fa-arrow-<?php echo (isset($analytics_data['users_change']) && $analytics_data['users_change'] >= 0) ? 'up' : 'down'; ?> mr-1"></i>
                                <?php echo abs(isset($analytics_data['users_change']) ? $analytics_data['users_change'] : 0); ?>%
                            </span>
                            <span class="text-sm text-purple-200 mr-2">مقارنة بالفترة السابقة</span>
                        </div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- معدل التفاعل -->
        <div class="card bg-gradient-to-br from-orange-500 to-orange-600 text-white shadow-xl">
            <div class="card-body p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-lg mb-2">معدل التفاعل</p>
                        <p class="text-4xl font-bold"><?php echo number_format(isset($analytics_data['engagement_rate']) ? $analytics_data['engagement_rate'] : 0, 1); ?>%</p>
                        <div class="flex items-center mt-3">
                            <span class="text-sm <?php echo (isset($analytics_data['engagement_change']) && $analytics_data['engagement_change'] >= 0) ? 'text-green-300' : 'text-red-300'; ?>">
                                <i class="fas fa-arrow-<?php echo (isset($analytics_data['engagement_change']) && $analytics_data['engagement_change'] >= 0) ? 'up' : 'down'; ?> mr-1"></i>
                                <?php echo abs(isset($analytics_data['engagement_change']) ? $analytics_data['engagement_change'] : 0); ?>%
                            </span>
                            <span class="text-sm text-orange-200 mr-2">مقارنة بالفترة السابقة</span>
                        </div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الرسوم البيانية الرئيسية -->
    <div class="analytics-charts-grid">
        <!-- رسم بياني للنشاط العام -->
        <div class="card bg-white shadow-lg border border-gray-200">
            <div class="card-body p-3 lg:p-4">
                <div class="flex items-center justify-between mb-3 lg:mb-4">
                    <h3 class="text-base lg:text-lg font-bold text-gray-800">📈 النشاط العام</h3>
                    <div class="flex items-center gap-2 lg:gap-3">
                        <div class="btn-group">
                            <button class="btn btn-xs lg:btn-sm <?php echo $chart_type === 'line' ? 'btn-active bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'; ?>" onclick="updateChartType('line')">
                                <i class="fas fa-chart-line mr-1"></i> <span class="hidden sm:inline">خطي</span>
                            </button>
                            <button class="btn btn-xs lg:btn-sm <?php echo $chart_type === 'bar' ? 'btn-active bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'; ?>" onclick="updateChartType('bar')">
                                <i class="fas fa-chart-bar mr-1"></i> <span class="hidden sm:inline">أعمدة</span>
                            </button>
                            <button class="btn btn-xs lg:btn-sm <?php echo $chart_type === 'area' ? 'btn-active bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'; ?>" onclick="updateChartType('area')">
                                <i class="fas fa-chart-area mr-1"></i> <span class="hidden sm:inline">مساحي</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="h-64 lg:h-80">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- رسم بياني للتفاعل -->
        <div class="card bg-white shadow-lg border border-gray-200">
            <div class="card-body p-3 lg:p-4">
                <div class="flex items-center justify-between mb-3 lg:mb-4">
                    <h3 class="text-base lg:text-lg font-bold text-gray-800">🎯 تحليل التفاعل</h3>
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-ghost btn-xs lg:btn-sm bg-gray-100 hover:bg-gray-200 text-gray-700">
                            <i class="fas fa-ellipsis-v"></i>
                        </label>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-white rounded-box w-48 lg:w-52 border border-gray-200">
                            <li><a href="#" onclick="viewEngagementDetails()" class="text-gray-700 hover:bg-gray-100 text-sm">
                                <i class="fas fa-eye mr-2 text-blue-500"></i> عرض التفاصيل
                            </a></li>
                            <li><a href="#" onclick="exportChart('engagement')" class="text-gray-700 hover:bg-gray-100 text-sm">
                                <i class="fas fa-download mr-2 text-green-500"></i> تصدير الرسم البياني
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="h-64 lg:h-80">
                    <canvas id="engagementChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- الرسوم البيانية المتقدمة -->
    <div class="analytics-advanced-charts-grid">
        <!-- رسم بياني للفئات -->
        <div class="card bg-white shadow-lg border border-gray-200">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">🏷️ توزيع الفئات</h3>
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-ghost btn-sm bg-gray-100 hover:bg-gray-200 text-gray-700">
                            <i class="fas fa-filter"></i>
                        </label>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-white rounded-box w-52 border border-gray-200">
                            <li><a href="#" onclick="filterCategories('questions')" class="text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-question-circle mr-2 text-blue-500"></i> حسب الأسئلة
                            </a></li>
                            <li><a href="#" onclick="filterCategories('answers')" class="text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-comments mr-2 text-green-500"></i> حسب الإجابات
                            </a></li>
                            <li><a href="#" onclick="filterCategories('engagement')" class="text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-chart-line mr-2 text-purple-500"></i> حسب التفاعل
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="categoriesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- رسم بياني للعلامات -->
        <div class="card bg-white shadow-lg border border-gray-200">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">📊 العلامات الشائعة</h3>
                    <div class="flex items-center gap-2">
                        <div class="form-control">
                            <label class="label cursor-pointer">
                                <span class="label-text text-sm mr-2 text-gray-700">النمو</span>
                                <input type="checkbox" class="toggle toggle-sm toggle-primary" checked />
                            </label>
                        </div>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="tagsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- رسم بياني للمستخدمين -->
        <div class="card bg-white shadow-lg border border-gray-200">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">👥 نشاط المستخدمين</h3>
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-ghost btn-sm bg-gray-100 hover:bg-gray-200 text-gray-700">
                            <i class="fas fa-users"></i>
                        </label>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-white rounded-box w-52 border border-gray-200">
                            <li><a href="#" onclick="filterUsers('daily')" class="text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-calendar-day mr-2 text-blue-500"></i> يومي
                            </a></li>
                            <li><a href="#" onclick="filterUsers('weekly')" class="text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-calendar-week mr-2 text-green-500"></i> أسبوعي
                            </a></li>
                            <li><a href="#" onclick="filterUsers('monthly')" class="text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-calendar-alt mr-2 text-purple-500"></i> شهري
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="usersChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- التخطيط الرئيسي: 70% محتوى + 30% شريط جانبي -->
    <div class="analytics-main-layout">
        <!-- المحتوى الرئيسي (70%) -->
        <div class="analytics-main-content">

    <!-- التحليلات التفصيلية -->
    <div class="card bg-white shadow-lg border border-gray-200">
        <div class="card-body p-4">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800">📋 التحليلات التفصيلية</h3>
                <div class="flex items-center gap-4">
                    <div class="tabs tabs-boxed bg-gray-100">
                        <a class="tab tab-active text-base text-blue-600 bg-white shadow-sm" onclick="switchTab('content')">المحتوى</a>
                        <a class="tab text-base text-gray-600 hover:text-blue-600" onclick="switchTab('users')">المستخدمون</a>
                        <a class="tab text-base text-gray-600 hover:text-blue-600" onclick="switchTab('engagement')">التفاعل</a>
                        <a class="tab text-base text-gray-600 hover:text-blue-600" onclick="switchTab('traffic')">الزيارات</a>
                    </div>
                </div>
            </div>

            <!-- تبويب المحتوى -->
            <div id="content-tab" class="tab-content">
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full bg-white">
                        <thead>
                            <tr class="text-base bg-gray-50">
                                <th class="text-gray-700 font-bold">المحتوى</th>
                                <th class="text-gray-700 font-bold">المشاهدات</th>
                                <th class="text-gray-700 font-bold">التفاعل</th>
                                <th class="text-gray-700 font-bold">التصويتات</th>
                                <th class="text-gray-700 font-bold">الإجابات</th>
                                <th class="text-gray-700 font-bold">تاريخ الإنشاء</th>
                                <th class="text-gray-700 font-bold">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($analytics_data['content_performance']) && is_array($analytics_data['content_performance'])): ?>
                                <?php foreach (array_slice($analytics_data['content_performance'], 0, 10) as $content): ?>
                                    <tr class="hover:bg-gray-50 border-b border-gray-200">
                                        <td>
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center text-white">
                                                    <i class="fas fa-question-circle"></i>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-lg"><?php echo esc_html(wp_trim_words($content['title'] ?? '', 8)); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo esc_html($content['author'] ?? ''); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <span class="text-lg font-bold"><?php echo number_format($content['views'] ?? 0); ?></span>
                                                <span class="text-sm <?php echo (isset($content['views_change']) && $content['views_change'] >= 0) ? 'text-success' : 'text-error'; ?>">
                                                    (<?php echo (isset($content['views_change']) && $content['views_change'] >= 0) ? '+' : ''; ?><?php echo $content['views_change'] ?? 0; ?>%)
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <progress class="progress progress-primary w-20 h-3" value="<?php echo $content['engagement'] ?? 0; ?>" max="100"></progress>
                                                <span class="text-lg font-bold"><?php echo $content['engagement'] ?? 0; ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center gap-1">
                                                    <i class="fas fa-thumbs-up text-success text-lg"></i>
                                                    <span class="text-success font-bold"><?php echo $content['upvotes'] ?? 0; ?></span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <i class="fas fa-thumbs-down text-error text-lg"></i>
                                                    <span class="text-error font-bold"><?php echo $content['downvotes'] ?? 0; ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary badge-lg"><?php echo $content['answers_count'] ?? 0; ?></span>
                                        </td>
                                        <td>
                                            <span class="text-lg text-gray-600"><?php echo isset($content['created']) ? date('d/m/Y', strtotime($content['created'])) : ''; ?></span>
                                        </td>
                                        <td>
                                            <div class="dropdown dropdown-end">
                                                <label tabindex="0" class="btn btn-ghost btn-sm">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </label>
                                                <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                                                    <li><a href="<?php echo isset($content['id']) ? get_permalink($content['id']) : '#'; ?>" target="_blank">
                                                        <i class="fas fa-eye mr-2"></i> عرض
                                                    </a></li>
                                                    <li><a href="<?php echo isset($content['id']) ? get_edit_post_link($content['id']) : '#'; ?>">
                                                        <i class="fas fa-edit mr-2"></i> تحرير
                                                    </a></li>
                                                    <li><a href="#" onclick="viewAnalytics(<?php echo $content['id'] ?? 0; ?>)">
                                                        <i class="fas fa-chart-bar mr-2"></i> تحليلات
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-chart-bar text-4xl mb-4"></i>
                                        <p class="text-lg">لا توجد بيانات متاحة</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- تبويب المستخدمين -->
            <div id="users-tab" class="tab-content hidden">
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-users text-6xl mb-6"></i>
                    <p class="text-2xl">تحليلات المستخدمين ستظهر هنا قريباً</p>
                </div>
            </div>

            <!-- تبويب التفاعل -->
            <div id="engagement-tab" class="tab-content hidden">
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-chart-line text-6xl mb-6"></i>
                    <p class="text-2xl">تحليلات التفاعل ستظهر هنا قريباً</p>
                </div>
            </div>

            <!-- تبويب الزيارات -->
            <div id="traffic-tab" class="tab-content hidden">
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-globe text-6xl mb-6"></i>
                    <p class="text-2xl">تحليلات الزيارات ستظهر هنا قريباً</p>
                </div>
            </div>
        </div>
    </div>

    <!-- أفضل الأسئلة أداءً -->
    <div class="card bg-white shadow-lg border border-gray-200 mb-4 lg:mb-6">
        <div class="card-body p-3 lg:p-4">
            <div class="flex items-center justify-between mb-3 lg:mb-4">
                <h3 class="text-base lg:text-lg font-bold text-gray-800">🏆 أفضل الأسئلة أداءً</h3>
                <a href="#" class="text-sm lg:text-lg text-blue-600 hover:text-blue-800 hover:underline">عرض الكل</a>
            </div>
            <div class="analytics-top-questions-grid">
                <?php if (isset($analytics_data['top_questions']) && is_array($analytics_data['top_questions'])): ?>
                    <?php foreach (array_slice($analytics_data['top_questions'], 0, 6) as $index => $question): ?>
                        <div class="flex items-start gap-4 p-4 rounded-xl hover:bg-gray-50 transition-all duration-300 border border-gray-100">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-bold">
                                    <?php echo $index + 1; ?>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-900 text-base mb-2 truncate">
                                    <a href="<?php echo isset($question['id']) ? get_permalink($question['id']) : '#'; ?>" class="hover:text-blue-600 transition-colors">
                                        <?php echo esc_html($question['title'] ?? ''); ?>
                                    </a>
                                </h4>
                                <div class="flex items-center gap-4 text-sm text-gray-600">
                                    <span class="flex items-center">
                                        <i class="fas fa-eye mr-2 text-blue-500"></i>
                                        <?php echo number_format($question['views'] ?? 0); ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-comments mr-2 text-green-500"></i>
                                        <?php echo $question['answers'] ?? 0; ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-thumbs-up mr-2 text-orange-500"></i>
                                        <?php echo $question['votes'] ?? 0; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-8 text-gray-500">
                        <i class="fas fa-chart-bar text-4xl mb-4"></i>
                        <p>لا توجد بيانات متاحة</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- الشريط الجانبي (30%) -->
        <div class="analytics-sidebar">
            <!-- أفضل المساهمين -->
            <div class="card bg-white shadow-lg border border-gray-200 mb-6">
                <div class="card-body p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800">⭐ أفضل المساهمين</h3>
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">عرض الكل</a>
                    </div>
                    <div class="space-y-3">
                        <?php if (isset($analytics_data['top_contributors']) && is_array($analytics_data['top_contributors'])): ?>
                            <?php foreach (array_slice($analytics_data['top_contributors'], 0, 5) as $index => $contributor): ?>
                                <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-all duration-300 border border-gray-100">
                                    <div class="flex-shrink-0 relative">
                                        <?php echo get_avatar($contributor['user_id'] ?? 0, 40, '', '', ['class' => 'rounded-lg']); ?>
                                        <div class="absolute -top-1 -right-1 w-6 h-6 bg-gradient-to-br from-yellow-400 to-orange-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                                            <?php echo $index + 1; ?>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-900 text-sm mb-1">
                                            <?php echo esc_html($contributor['display_name'] ?? ''); ?>
                                        </h4>
                                        <div class="flex items-center gap-2 text-xs text-gray-600">
                                            <span class="flex items-center">
                                                <i class="fas fa-question-circle mr-1 text-blue-500"></i>
                                                <?php echo $contributor['questions'] ?? 0; ?>
                                            </span>
                                            <span class="flex items-center">
                                                <i class="fas fa-comments mr-1 text-green-500"></i>
                                                <?php echo $contributor['answers'] ?? 0; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-primary"><?php echo number_format($contributor['points'] ?? 0); ?></div>
                                        <div class="text-xs text-gray-500">نقطة</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-6 text-gray-500">
                                <i class="fas fa-users text-3xl mb-3"></i>
                                <p class="text-sm">لا توجد بيانات متاحة</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- أفضل الأسئلة -->
            <div class="card bg-white shadow-lg border border-gray-200">
                <div class="card-body p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800">🔥 أفضل الأسئلة أداءً</h3>
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">عرض الكل</a>
                    </div>
                    <div class="space-y-3">
                        <?php if (isset($analytics_data['top_questions']) && is_array($analytics_data['top_questions'])): ?>
                            <?php foreach (array_slice($analytics_data['top_questions'], 0, 5) as $index => $question): ?>
                                <div class="p-3 rounded-lg hover:bg-gray-50 transition-all duration-300 border border-gray-100">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 text-white text-sm font-bold rounded-lg flex items-center justify-center">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-800 text-sm mb-2 line-clamp-2">
                                                <a href="<?php echo isset($question['id']) ? get_permalink($question['id']) : '#'; ?>" class="hover:text-blue-600">
                                                    <?php echo esc_html(wp_trim_words($question['title'] ?? '', 6)); ?>
                                                </a>
                                            </h4>
                                            <div class="flex items-center justify-between text-xs text-gray-500">
                                                <span class="flex items-center">
                                                    <i class="fas fa-eye mr-1 text-blue-500"></i>
                                                    <?php echo number_format($question['views'] ?? 0); ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="fas fa-comments mr-1 text-green-500"></i>
                                                    <?php echo $question['answers'] ?? 0; ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="fas fa-thumbs-up mr-1 text-orange-500"></i>
                                                    <?php echo $question['votes'] ?? 0; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-6 text-gray-500">
                                <i class="fas fa-question-circle text-3xl mb-3"></i>
                                <p class="text-sm">لا توجد بيانات متاحة</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نافذة النطاق المخصص -->
<div id="customDateModal" class="modal">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-2xl mb-6">📅 نطاق تاريخ مخصص</h3>
        <form id="customDateForm">
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-lg">تاريخ البداية</span>
                    </label>
                    <input type="date" class="input input-bordered input-lg" name="start_date" required>
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-lg">تاريخ النهاية</span>
                    </label>
                    <input type="date" class="input input-bordered input-lg" name="end_date" required>
                </div>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-lg" onclick="closeModal('customDateModal')">إلغاء</button>
                <button type="submit" class="btn btn-primary btn-lg">تطبيق</button>
            </div>
        </form>
    </div>
</div>

<script>
// تهيئة الرسوم البيانية
document.addEventListener('DOMContentLoaded', function() {
    initializeActivityChart();
    initializeEngagementChart();
    initializeCategoriesChart();
    initializeTagsChart();
    initializeUsersChart();
    
    // تحميل البيانات عبر AJAX
    if (window.AskroAdmin) {
        window.AskroAdmin.loadAnalyticsData();
    }
});

// رسم بياني النشاط العام
function initializeActivityChart() {
    const ctx = document.getElementById('activityChart').getContext('2d');
    const data = <?php echo json_encode(isset($analytics_data['activity_chart_data']) ? $analytics_data['activity_chart_data'] : ['labels' => [], 'questions' => [], 'answers' => []]); ?>;
    
    new Chart(ctx, {
        type: '<?php echo $chart_type; ?>',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'الأسئلة',
                data: data.questions,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'الإجابات',
                data: data.answers,
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
}

// رسم بياني التفاعل
function initializeEngagementChart() {
    const ctx = document.getElementById('engagementChart').getContext('2d');
    const data = <?php echo json_encode(isset($analytics_data['engagement_chart_data']) ? $analytics_data['engagement_chart_data'] : ['labels' => [], 'values' => []]); ?>;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)',
                    'rgb(239, 68, 68)',
                    'rgb(139, 92, 246)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                }
            }
        }
    });
}

// رسم بياني الفئات
function initializeCategoriesChart() {
    const ctx = document.getElementById('categoriesChart').getContext('2d');
    const data = <?php echo json_encode(isset($analytics_data['categories_chart_data']) ? $analytics_data['categories_chart_data'] : ['labels' => [], 'values' => []]); ?>;
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)',
                    'rgb(239, 68, 68)',
                    'rgb(139, 92, 246)',
                    'rgb(236, 72, 153)',
                    'rgb(14, 165, 233)',
                    'rgb(34, 197, 94)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
}

// رسم بياني العلامات
function initializeTagsChart() {
    const ctx = document.getElementById('tagsChart').getContext('2d');
    const data = <?php echo json_encode(isset($analytics_data['tags_chart_data']) ? $analytics_data['tags_chart_data'] : ['labels' => [], 'values' => []]); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'الاستخدام',
                data: data.values,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                },
                y: {
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
}

// رسم بياني المستخدمين
function initializeUsersChart() {
    const ctx = document.getElementById('usersChart').getContext('2d');
    const data = <?php echo json_encode(isset($analytics_data['users_chart_data']) ? $analytics_data['users_chart_data'] : ['labels' => [], 'values' => []]); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'المستخدمون النشطون',
                data: data.values,
                borderColor: 'rgb(139, 92, 246)',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
}

// الدوال التفاعلية
function updateDateRange(range) {
    if (range === 'custom') {
        document.getElementById('customDateModal').classList.add('modal-open');
    } else {
        window.location.href = updateUrlParameter(window.location.href, 'date_range', range);
    }
}

function updateChartType(type) {
    window.location.href = updateUrlParameter(window.location.href, 'chart_type', type);
}

function refreshAnalytics() {
    // إظهار مؤشر التحميل
    showToast('جاري تحديث البيانات...', 'info');
    
    // جلب البيانات عبر AJAX
    $.ajax({
        url: askroAdmin.ajax_url,
        type: 'POST',
        data: {
            action: 'askro_get_analytics_data',
            nonce: askroAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                // تحديث البيانات
                if (window.AskroAdmin && window.AskroAdmin.renderAnalyticsCharts) {
                    window.AskroAdmin.renderAnalyticsCharts(response.data);
                }
                showToast('تم تحديث البيانات بنجاح', 'success');
            } else {
                showToast('فشل في تحديث البيانات: ' + (response.data?.message || 'خطأ غير معروف'), 'error');
            }
        },
        error: function() {
            showToast('حدث خطأ في الاتصال', 'error');
        }
    });
}

function exportAnalytics(format) {
    const params = new URLSearchParams({
        action: 'askro_export_analytics',
        format: format,
        date_range: '<?php echo $date_range; ?>',
        nonce: '<?php echo wp_create_nonce('askro_export_analytics'); ?>'
    });
    
    window.open('<?php echo admin_url('admin-ajax.php'); ?>?' + params.toString());
}

function switchTab(tab) {
    // إخفاء جميع التبويبات
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // إظهار التبويب المحدد
    document.getElementById(tab + '-tab').classList.remove('hidden');
    
    // تحديث أزرار التبويب
    document.querySelectorAll('.tab').forEach(tabBtn => {
        tabBtn.classList.remove('tab-active');
    });
    event.target.classList.add('tab-active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('modal-open');
}

function updateUrlParameter(url, param, paramVal) {
    var newAdditionalURL = "";
    var tempArray = url.split("?");
    var baseURL = tempArray[0];
    var additionalURL = tempArray[1];
    var temp = "";
    if (additionalURL) {
        tempArray = additionalURL.split("&");
        for (var i = 0; i < tempArray.length; i++) {
            if (tempArray[i].split('=')[0] != param) {
                newAdditionalURL += temp + tempArray[i];
                temp = "&";
            }
        }
    }
    var rows_txt = temp + "" + param + "=" + paramVal;
    return baseURL + "?" + newAdditionalURL + rows_txt;
}

// معالج نموذج التاريخ المخصص
document.getElementById('customDateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const startDate = formData.get('start_date');
    const endDate = formData.get('end_date');
    
    let url = updateUrlParameter(window.location.href, 'start_date', startDate);
    url = updateUrlParameter(url, 'end_date', endDate);
    url = updateUrlParameter(url, 'date_range', 'custom');
    
    window.location.href = url;
});

// دوال إضافية
function viewEngagementDetails() {
    alert('سيتم إضافة تفاصيل التفاعل قريباً');
}

function exportChart(chartType) {
    alert('سيتم إضافة تصدير الرسم البياني قريباً');
}

function filterCategories(type) {
    alert('سيتم إضافة فلترة الفئات قريباً');
}

function filterUsers(period) {
    alert('سيتم إضافة فلترة المستخدمين قريباً');
}

function viewAnalytics(contentId) {
    alert('سيتم إضافة تحليلات المحتوى قريباً');
}

// دالة تصدير البيانات التحليلية
function exportAnalytics(format) {
    // إظهار مؤشر التحميل
    const loadingToast = showToast('جاري تصدير البيانات...', 'info');
    
    // إنشاء form data
    const formData = new FormData();
    formData.append('action', 'askro_export_analytics');
    formData.append('export_type', format);
    formData.append('nonce', '<?php echo wp_create_nonce('askro_analytics_export'); ?>');
    
    // إرسال طلب AJAX
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (format === 'json' || format === 'csv') {
            // للتصدير المباشر
            return response.blob();
        } else {
            return response.json();
        }
    })
    .then(data => {
        if (format === 'json' || format === 'csv') {
            // إنشاء رابط تحميل
            const url = window.URL.createObjectURL(data);
            const a = document.createElement('a');
            a.href = url;
            a.download = `askro-analytics-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.${format}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showToast('تم تصدير البيانات بنجاح!', 'success');
        } else {
            // للصيغ الأخرى (PDF, Excel)
            if (data.success) {
                showToast('تم تصدير البيانات بنجاح!', 'success');
            } else {
                showToast(data.data || 'حدث خطأ أثناء التصدير', 'error');
            }
        }
    })
    .catch(error => {
        console.error('خطأ في التصدير:', error);
        showToast('حدث خطأ أثناء تصدير البيانات', 'error');
    })
    .finally(() => {
        // إخفاء مؤشر التحميل
        if (loadingToast) {
            loadingToast.remove();
        }
    });
}

// دالة تحديث نطاق التاريخ
function updateDateRange(range) {
    if (range === 'custom') {
        // إظهار نافذة اختيار التاريخ المخصص
        const startDate = prompt('أدخل تاريخ البداية (YYYY-MM-DD):');
        const endDate = prompt('أدخل تاريخ النهاية (YYYY-MM-DD):');
        
        if (startDate && endDate) {
            window.location.href = window.location.pathname + '?date_range=custom&start=' + startDate + '&end=' + endDate;
        }
    } else {
        window.location.href = window.location.pathname + '?date_range=' + range;
    }
}

// دالة تحديث البيانات
function refreshAnalytics() {
    location.reload();
}

// دالة إظهار الإشعارات
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} fixed top-4 right-4 z-50 max-w-sm`;
    toast.innerHTML = `
        <div class="flex items-center">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="btn btn-sm btn-circle btn-ghost">✕</button>
        </div>
    `;
    document.body.appendChild(toast);
    
    // إزالة الإشعار بعد 5 ثوان
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 5000);
    
    return toast;
}
</script>

<style>
.askro-admin-wrap {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Cairo', sans-serif;
    background-color: #f8fafc;
    min-height: 100vh;
    padding: 20px;
}

.card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: white;
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.progress-primary::-webkit-progress-value {
    background-color: rgb(59, 130, 246);
}

.progress-primary::-moz-progress-bar {
    background-color: rgb(59, 130, 246);
}

.tab-content {
    min-height: 300px;
}

/* تحسينات للرسوم البيانية */
.chart-container {
    position: relative;
    height: 350px !important;
    max-height: 350px !important;
    overflow: hidden;
}

.chart-container canvas {
    height: 100% !important;
    max-height: 100% !important;
    width: 100% !important;
}

/* منع تمدد الشارت */
canvas {
    max-height: 400px !important;
    max-width: 100% !important;
    object-fit: contain;
}

/* تحسين أحجام الشارت */
.analytics-charts-grid canvas {
    max-height: 350px !important;
}

.analytics-advanced-charts-grid canvas {
    max-height: 300px !important;
}

/* تحسينات للاستجابة */
@media (max-width: 768px) {
    .askro-admin-wrap {
        padding: 10px;
    }
    
    .grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    
    .grid-cols-2 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
    
    .grid-cols-3 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
}

/* تحسينات للتبويبات */
.tabs-boxed .tab {
    font-size: 14px;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.tabs-boxed .tab:hover {
    background-color: #f1f5f9;
}

.tabs-boxed .tab-active {
    background-color: #3b82f6;
    color: white;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
}

/* تحسينات للجداول */
.table {
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 8px;
    overflow: hidden;
}

.table th {
    font-size: 14px;
    font-weight: 600;
    background-color: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 12px 16px;
}

.table td {
    font-size: 14px;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
}

.table tr:hover {
    background-color: #f8fafc;
}

/* تحسينات للبطاقات */
.card-body {
    padding: 16px;
}

/* تحسينات للأزرار */
.btn {
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-lg {
    padding: 10px 20px;
    font-size: 14px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

/* تحسينات للقوائم المنسدلة */
.dropdown-content {
    font-size: 14px;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.dropdown-content li a {
    padding: 10px 16px;
    border-radius: 4px;
    margin: 2px;
    transition: all 0.2s ease;
}

.dropdown-content li a:hover {
    background-color: #f1f5f9;
    transform: translateX(2px);
}

/* تحسينات للبطاقات المتراجعة */
.card.bg-gradient-to-br {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
}

.card.bg-gradient-to-br.from-blue-500.to-blue-600 {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.card.bg-gradient-to-br.from-green-500.to-green-600 {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.card.bg-gradient-to-br.from-purple-500.to-purple-600 {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.card.bg-gradient-to-br.from-orange-500.to-orange-600 {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

/* تحسينات للشريط الجانبي */
.askro-admin-header {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

/* تحسينات للرسوم البيانية */
canvas {
    border-radius: 8px;
}

/* تحسينات للقوائم */
.space-y-4 > * + * {
    margin-top: 12px;
}

/* تحسينات للروابط */
a {
    transition: all 0.3s ease;
}

a:hover {
    text-decoration: none;
}

/* تحسينات للبطاقات الصغيرة */
.flex.items-start.gap-4 {
    padding: 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.flex.items-start.gap-4:hover {
    background-color: #f8fafc;
    transform: translateX(4px);
}

/* تحسينات للأيقونات */
.fas {
    transition: all 0.3s ease;
}

.fas:hover {
    transform: scale(1.1);
}

/* تحسينات ديناميكية للتخطيط */
@media (max-width: 640px) {
    .askro-admin-wrap {
        padding: 12px;
    }
    
    .askro-admin-header {
        padding: 16px;
        margin-bottom: 12px;
    }
    
    .card-body {
        padding: 16px !important;
    }
    
    .grid {
        gap: 12px !important;
    }
    
    .btn-group .btn {
        padding: 6px 8px;
        font-size: 11px;
    }
}

@media (min-width: 641px) and (max-width: 1024px) {
    .askro-admin-wrap {
        padding: 16px;
    }
    
    .grid {
        gap: 16px !important;
    }
}

@media (min-width: 1025px) {
    .askro-admin-wrap {
        padding: 20px;
    }
    
    .grid {
        gap: 20px !important;
    }
}

/* تحسينات للشبكة الديناميكية */
.grid-cols-2 {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.grid-cols-3 {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

.grid-cols-4 {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

/* تحسينات للبطاقات المتراجعة */
.card.bg-gradient-to-br {
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* تحسينات للرسوم البيانية */
.chart-container {
    position: relative;
    width: 100%;
    height: 100%;
}

/* تحسينات للقوائم التفاعلية */
.space-y-3 > * + * {
    margin-top: 8px;
}

/* تحسينات للعناصر المتراجعة */
.flex.items-center.gap-4 {
    padding: 8px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.flex.items-center.gap-4:hover {
    background-color: #f8fafc;
    transform: translateY(-1px);
}

/* تحسينات للتبويبات المتجاوبة */
.tabs-boxed {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.tabs-boxed .tab {
    flex: 1;
    min-width: 80px;
    text-align: center;
}

/* تحسينات للأزرار المتجاوبة */
.btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
}

.btn-group .btn {
    flex: 1;
    min-width: 60px;
}

/* تحسينات للقوائم المنسدلة المتجاوبة */
.dropdown-content {
    min-width: 200px;
    max-width: 300px;
}

/* تحسينات للجداول المتجاوبة */
@media (max-width: 768px) {
    table {
        font-size: 12px;
    }
    
    table th,
    table td {
        padding: 8px 12px;
    }
}

/* تحسينات للبطاقات الصغيرة */
@media (max-width: 480px) {
    .card-body {
        padding: 12px !important;
    }
    
    .text-4xl {
        font-size: 1.5rem;
    }
    
    .text-2xl {
        font-size: 1.25rem;
    }
    
    .text-lg {
        font-size: 1rem;
    }
}

/* تحسينات للشبكة المتقدمة */
@media (min-width: 1280px) {
    .xl\:grid-cols-2 {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .xl\:grid-cols-3 {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* تحسينات للعناصر المتراجعة */
@media (max-width: 1024px) {
    .lg\:grid-cols-2 {
        grid-template-columns: repeat(1, 1fr);
    }
    
    .lg\:grid-cols-3 {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .lg\:grid-cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* تحسينات للعناصر المتراجعة */
@media (max-width: 768px) {
    .md\:grid-cols-2 {
        grid-template-columns: repeat(1, 1fr);
    }
    
    .md\:grid-cols-3 {
        grid-template-columns: repeat(1, 1fr);
    }
}

/* تحسينات للعناصر المتراجعة */
@media (max-width: 640px) {
    .sm\:grid-cols-2 {
        grid-template-columns: repeat(1, 1fr);
    }
    
    .sm\:grid-cols-3 {
        grid-template-columns: repeat(1, 1fr);
    }
}

/* === التخطيط الرئيسي: 70% محتوى + 30% شريط جانبي === */
.analytics-main-layout {
    display: grid;
    grid-template-columns: 70% 30%;
    gap: 2rem;
    margin-bottom: 2rem;
    width: 100%;
}

.analytics-main-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    width: 100%;
}

.analytics-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    width: 100%;
    position: sticky;
    top: 2rem;
    height: fit-content;
}

/* === CSS Grid المتقدم بناءً على CSS-Tricks === */

/* البطاقات الرئيسية للمقاييس - Grid متقدم */
.analytics-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

/* الرسوم البيانية الرئيسية - Grid متقدم */
.analytics-charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.analytics-charts-grid .card {
    height: 400px;
    overflow: hidden;
}

.analytics-charts-grid canvas {
    max-height: 350px !important;
    width: 100% !important;
}

/* الرسوم البيانية المتقدمة - Grid متقدم */
.analytics-advanced-charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: -16.5rem;
}

.analytics-advanced-charts-grid .card {
    height: 350px;
    overflow: hidden;
}

.analytics-advanced-charts-grid canvas {
    max-height: 300px !important;
    width: 100% !important;
}

/* أفضل المساهمين - Grid متقدم */
.analytics-contributors-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

/* أفضل الأسئلة - Grid متقدم */
.analytics-top-questions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
}

/* تحسينات للشبكة الديناميكية باستخدام CSS Grid المتقدم */
@media (max-width: 1024px) {
    .analytics-main-layout {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .analytics-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .analytics-main-layout {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .analytics-metrics-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .analytics-charts-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .analytics-advanced-charts-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .analytics-top-questions-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 0.75rem;
    }
}

@media (max-width: 480px) {
    .analytics-metrics-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .analytics-advanced-charts-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .analytics-top-questions-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
}

/* تحسينات للشبكة المتقدمة باستخدام Grid Areas */
@media (min-width: 1200px) {
    .analytics-charts-grid {
        grid-template-columns: repeat(2, 1fr);
        grid-template-areas: 
            "activity engagement"
            "activity engagement";
    }
    
    .analytics-advanced-charts-grid {
        grid-template-columns: repeat(3, 1fr);
        grid-template-areas: 
            "categories tags users"
            "categories tags users";
    }
    
    .analytics-contributors-grid {
        grid-template-columns: 1fr;
        grid-template-areas: 
            "contributors";
    }
}

/* تحسينات للعناصر داخل الشبكة */
.analytics-metrics-grid .card {
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.analytics-charts-grid .card {
    min-height: 400px;
}

.analytics-advanced-charts-grid .card {
    min-height: 300px;
}

.analytics-contributors-grid .card {
    min-height: 350px;
}

/* تحسينات للشبكة باستخدام Grid Lines المخصصة */
.analytics-metrics-grid {
    grid-template-columns: [start] repeat(auto-fit, minmax(250px, 1fr)) [end];
}

.analytics-charts-grid {
    grid-template-columns: [chart-start] repeat(auto-fit, minmax(400px, 1fr)) [chart-end];
}

/* تحسينات للشبكة باستخدام Grid Gap المتقدم */
.analytics-metrics-grid {
    grid-gap: 1rem;
}

.analytics-charts-grid {
    grid-gap: 1.5rem;
}

.analytics-advanced-charts-grid {
    grid-gap: 1.5rem;
}

.analytics-contributors-grid {
    grid-gap: 1.5rem;
}

.analytics-top-questions-grid {
    grid-gap: 1rem;
}

/* تحسينات للشبكة باستخدام Grid Auto Flow */
.analytics-metrics-grid {
    grid-auto-flow: row dense;
}

.analytics-top-questions-grid {
    grid-auto-flow: row dense;
}

/* تحسينات للشبكة باستخدام Grid Auto Columns/Rows */
.analytics-metrics-grid {
    grid-auto-rows: minmax(120px, auto);
}

.analytics-charts-grid {
    grid-auto-rows: minmax(400px, auto);
}

.analytics-advanced-charts-grid {
    grid-auto-rows: minmax(300px, auto);
}

.analytics-contributors-grid {
    grid-auto-rows: minmax(350px, auto);
}

.analytics-top-questions-grid {
    grid-auto-rows: minmax(200px, auto);
}

/* تحسينات للشبكة باستخدام Grid Template Areas */
@media (min-width: 1024px) {
    .analytics-charts-grid {
        grid-template-areas: 
            "activity engagement";
    }
    
    .analytics-contributors-grid {
        grid-template-areas: 
            "contributors";
    }
}

/* تحسينات للشبكة باستخدام Grid Justify/Align Content */
.analytics-metrics-grid {
    justify-content: center;
    align-content: start;
}

.analytics-charts-grid {
    justify-content: center;
    align-content: start;
}

.analytics-advanced-charts-grid {
    justify-content: center;
    align-content: start;
}

.analytics-contributors-grid {
    justify-content: center;
    align-content: start;
}

.analytics-top-questions-grid {
    justify-content: center;
    align-content: start;
}

/* تحسينات للشبكة باستخدام Grid Justify/Align Items */
.analytics-metrics-grid .card {
    justify-self: stretch;
    align-self: stretch;
}

.analytics-charts-grid .card {
    justify-self: stretch;
    align-self: stretch;
}

.analytics-advanced-charts-grid .card {
    justify-self: stretch;
    align-self: stretch;
}

.analytics-contributors-grid .card {
    justify-self: stretch;
    align-self: stretch;
}

.analytics-top-questions-grid .card {
    justify-self: stretch;
    align-self: stretch;
}

/* تحسينات للشريط الجانبي */
.analytics-sidebar .card {
    min-height: auto;
    margin-bottom: 0;
}

.analytics-sidebar .card-body {
    padding: 1rem;
}

/* ضمان تطبيق التخطيط بشكل صحيح */
.analytics-main-layout {
    display: grid !important;
    grid-template-columns: 70% 30% !important;
    gap: 2rem !important;
    margin-bottom: 2rem !important;
    width: 100% !important;
}

/* إزالة أي margins إضافية */
.analytics-main-content > * {
    margin-bottom: 1rem;
}

.analytics-main-content > *:last-child {
    margin-bottom: 0;
}

/* تحسين عرض النص في الشريط الجانبي */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* === إصلاح التحليلات التفصيلية === */
/* ضمان عدم تجاوز حدود المربع */
.card .card-body {
    overflow: hidden;
}

/* تحسين الجدول في التحليلات التفصيلية */
.overflow-x-auto {
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}

.table {
    min-width: 100%;
    table-layout: fixed;
}

.table th,
.table td {
    padding: 1rem;
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
    max-width: none;
}

/* تحسين عرض المحتوى في الجدول */
.table td:first-child {
    min-width: 350px;
    max-width: 450px;
}

.table td:nth-child(2),
.table td:nth-child(3),
.table td:nth-child(4),
.table td:nth-child(5) {
    min-width: 150px;
    max-width: 200px;
}

.table td:nth-child(6) {
    min-width: 120px;
    max-width: 150px;
}

.table td:last-child {
    min-width: 100px;
    max-width: 120px;
}

/* تحسين التبويبات */
.tabs-boxed {
    flex-wrap: wrap;
    gap: 0.25rem;
}

.tabs-boxed .tab {
    white-space: nowrap;
    min-width: fit-content;
}

/* تحسين العناصر داخل الجدول */
.table .flex {
    flex-wrap: wrap;
    gap: 0.5rem;
}

.table .badge {
    white-space: nowrap;
}

.table .progress {
    min-width: 60px;
}

/* تحسين القوائم المنسدلة */
.dropdown-content {
    max-width: 200px;
    overflow: hidden;
}

.dropdown-content li a {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* تحسين للشاشات الصغيرة */
@media (max-width: 768px) {
    .table {
        font-size: 0.875rem;
    }
    
    .table th,
    .table td {
        padding: 0.75rem;
        max-width: none;
    }
    
    .table td:first-child {
        min-width: 250px;
        max-width: 350px;
    }
    
    .tabs-boxed {
        flex-direction: column;
        width: 100%;
    }
    
    .tabs-boxed .tab {
        width: 100%;
        text-align: center;
    }
}

@media (max-width: 640px) {
    .table th,
    .table td {
        padding: 0.5rem;
        max-width: none;
        font-size: 0.75rem;
    }
    
    .table td:first-child {
        min-width: 200px;
        max-width: 300px;
    }
    
    .table .flex {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .table .progress {
        min-width: 60px;
    }
}

/* تحسين التمرير الأفقي */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
