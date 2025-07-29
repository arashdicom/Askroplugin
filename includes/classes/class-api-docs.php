<?php
/**
 * API Documentation Handler Class
 *
 * @package    Askro
 * @subpackage Core/API/Docs
 * @since      1.0.0
 * @author     Arashdi <arashdi@wratcliff.dev>
 * @copyright  2025 William Ratcliff
 * @license    GPL-3.0-or-later
 * @link       https://arashdi.com
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro API Documentation Class
 *
 * Handles API documentation and examples
 *
 * @since 1.0.0
 */
class Askro_API_Docs {

    /**
     * API base URL
     *
     * @var string
     * @since 1.0.0
     */
    private $api_base_url;

    /**
     * Initialize the documentation component
     *
     * @since 1.0.0
     */
    public function init() {
        // Delay API URL generation until after WordPress is fully loaded
        add_action('init', [$this, 'setup_api_url']);
        // Register submenu after main menu is guaranteed to exist
        add_action('admin_menu', [$this, 'add_docs_page'], 20);
        add_action('wp_ajax_askro_test_api_endpoint', [$this, 'test_api_endpoint']);
    }

    /**
     * Setup API URL after WordPress is fully loaded
     *
     * @since 1.0.0
     */
    public function setup_api_url() {
        $this->api_base_url = get_rest_url(null, 'askro/v1');
    }

    /**
     * Get API base URL with fallback
     *
     * @return string
     * @since 1.0.0
     */
    public function get_api_base_url() {
        if (empty($this->api_base_url)) {
            $this->api_base_url = get_rest_url(null, 'askro/v1');
        }
        return $this->api_base_url;
    }

    /**
     * Add documentation page to admin menu
     *
     * @since 1.0.0
     */
    public function add_docs_page() {
        add_submenu_page(
            'askro',
            __('API Documentation', 'askro'),
            __('API Documentation', 'askro'),
            'manage_options',
            'askro-api-docs',
            [$this, 'render_docs_page']
        );
    }

    /**
     * Render documentation page
     *
     * @since 1.0.0
     */
    public function render_docs_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Askro API Documentation', 'askro'); ?></h1>
            
            <div class="askro-api-docs">
                <!-- Authentication Section -->
                <section class="docs-section">
                    <h2><?php _e('المصادقة (Authentication)', 'askro'); ?></h2>
                    <p><?php _e('يدعم API ثلاث طرق للمصادقة:', 'askro'); ?></p>
                    
                    <h3><?php _e('1. Bearer Token (JWT)', 'askro'); ?></h3>
                    <pre><code>Authorization: Bearer &lt;your_jwt_token&gt;</code></pre>
                    
                    <h3><?php _e('2. API Key', 'askro'); ?></h3>
                    <pre><code>Authorization: ApiKey &lt;your_api_key&gt;</code></pre>
                    
                    <h3><?php _e('3. Basic Authentication', 'askro'); ?></h3>
                    <pre><code>Authorization: Basic &lt;base64_encoded_credentials&gt;</code></pre>
                </section>

                <!-- Questions Endpoints -->
                <section class="docs-section">
                    <h2><?php _e('نقاط النهاية للأسئلة (Questions Endpoints)', 'askro'); ?></h2>
                    
                    <h3><?php _e('الحصول على قائمة الأسئلة', 'askro'); ?></h3>
                    <pre><code>GET <?php echo $this->get_api_base_url(); ?>/questions</code></pre>
                    
                    <h4><?php _e('المعاملات (Parameters):', 'askro'); ?></h4>
                    <ul>
                        <li><code>per_page</code> - عدد الأسئلة في الصفحة (افتراضي: 15)</li>
                        <li><code>page</code> - رقم الصفحة (افتراضي: 1)</li>
                        <li><code>orderby</code> - ترتيب حسب (date, title, views, answers, votes)</li>
                        <li><code>order</code> - اتجاه الترتيب (ASC, DESC)</li>
                        <li><code>category</code> - تصفية حسب الفئة</li>
                        <li><code>tag</code> - تصفية حسب الوسم</li>
                        <li><code>status</code> - تصفية حسب الحالة (open, closed, solved)</li>
                    </ul>
                    
                    <h4><?php _e('مثال:', 'askro'); ?></h4>
                    <pre><code>curl -X GET "<?php echo $this->get_api_base_url(); ?>/questions?per_page=10&orderby=date&order=DESC" \
-H "Authorization: Bearer YOUR_JWT_TOKEN"</code></pre>
                    
                    <h3><?php _e('الحصول على سؤال واحد', 'askro'); ?></h3>
                    <pre><code>GET <?php echo $this->get_api_base_url(); ?>/questions/{id}</code></pre>
                    
                    <h4><?php _e('مثال:', 'askro'); ?></h4>
                    <pre><code>curl -X GET "<?php echo $this->get_api_base_url(); ?>/questions/123" \
-H "Authorization: Bearer YOUR_JWT_TOKEN"</code></pre>
                    
                    <h3><?php _e('إنشاء سؤال جديد', 'askro'); ?></h3>
                    <pre><code>POST <?php echo $this->get_api_base_url(); ?>/questions</code></pre>
                    
                    <h4><?php _e('بيانات الطلب (Request Body):', 'askro'); ?></h4>
                    <pre><code>{
  "title": "عنوان السؤال",
  "content": "محتوى السؤال",
  "category": "programming",
  "tags": ["javascript", "react"],
  "status": "open"
}</code></pre>
                    
                    <h4><?php _e('مثال:', 'askro'); ?></h4>
                    <pre><code>curl -X POST "<?php echo $this->get_api_base_url(); ?>/questions" \
-H "Authorization: Bearer YOUR_JWT_TOKEN" \
-H "Content-Type: application/json" \
-d '{
  "title": "كيفية استخدام React Hooks؟",
  "content": "أريد معرفة كيفية استخدام React Hooks في مشروعي...",
  "category": "programming",
  "tags": ["javascript", "react", "hooks"]
}'</code></pre>
                </section>

                <!-- Answers Endpoints -->
                <section class="docs-section">
                    <h2><?php _e('نقاط النهاية للإجابات (Answers Endpoints)', 'askro'); ?></h2>
                    
                    <h3><?php _e('الحصول على إجابات السؤال', 'askro'); ?></h3>
                    <pre><code>GET <?php echo $this->get_api_base_url(); ?>/questions/{question_id}/answers</code></pre>
                    
                    <h4><?php _e('المعاملات:', 'askro'); ?></h4>
                    <ul>
                        <li><code>per_page</code> - عدد الإجابات في الصفحة (افتراضي: 20)</li>
                        <li><code>page</code> - رقم الصفحة (افتراضي: 1)</li>
                    </ul>
                    
                    <h3><?php _e('إضافة إجابة', 'askro'); ?></h3>
                    <pre><code>POST <?php echo $this->get_api_base_url(); ?>/questions/{question_id}/answers</code></pre>
                    
                    <h4><?php _e('بيانات الطلب:', 'askro'); ?></h4>
                    <pre><code>{
  "content": "محتوى الإجابة"
}</code></pre>
                    
                    <h4><?php _e('مثال:', 'askro'); ?></h4>
                    <pre><code>curl -X POST "<?php echo $this->get_api_base_url(); ?>/questions/123/answers" \
-H "Authorization: Bearer YOUR_JWT_TOKEN" \
-H "Content-Type: application/json" \
-d '{
  "content": "يمكنك استخدام React Hooks كالتالي..."
}'</code></pre>
                </section>

                <!-- Voting Endpoints -->
                <section class="docs-section">
                    <h2><?php _e('نقاط النهاية للتصويت (Voting Endpoints)', 'askro'); ?></h2>
                    
                    <h3><?php _e('التصويت على منشور', 'askro'); ?></h3>
                    <pre><code>POST <?php echo $this->get_api_base_url(); ?>/posts/{post_id}/vote</code></pre>
                    
                    <h4><?php _e('بيانات الطلب:', 'askro'); ?></h4>
                    <pre><code>{
  "vote_type": "useful",
  "vote_value": 1
}</code></pre>
                    
                    <h4><?php _e('أنواع التصويت المتاحة:', 'askro'); ?></h4>
                    <ul>
                        <li><code>useful</code> - مفيد</li>
                        <li><code>innovative</code> - مبتكر</li>
                        <li><code>well_researched</code> - مدروس جيداً</li>
                        <li><code>incorrect</code> - غير صحيح</li>
                        <li><code>redundant</code> - مكرر</li>
                    </ul>
                    
                    <h4><?php _e('مثال:', 'askro'); ?></h4>
                    <pre><code>curl -X POST "<?php echo $this->get_api_base_url(); ?>/posts/123/vote" \
-H "Authorization: Bearer YOUR_JWT_TOKEN" \
-H "Content-Type: application/json" \
-d '{
  "vote_type": "useful",
  "vote_value": 1
}'</code></pre>
                </section>

                <!-- Search Endpoints -->
                <section class="docs-section">
                    <h2><?php _e('نقاط النهاية للبحث (Search Endpoints)', 'askro'); ?></h2>
                    
                    <h3><?php _e('البحث في المحتوى', 'askro'); ?></h3>
                    <pre><code>GET <?php echo $this->get_api_base_url(); ?>/search</code></pre>
                    
                    <h4><?php _e('المعاملات:', 'askro'); ?></h4>
                    <ul>
                        <li><code>q</code> - نص البحث (مطلوب)</li>
                        <li><code>type</code> - نوع البحث (questions, answers)</li>
                        <li><code>category</code> - تصفية حسب الفئة</li>
                        <li><code>tag</code> - تصفية حسب الوسم</li>
                        <li><code>status</code> - تصفية حسب الحالة</li>
                        <li><code>per_page</code> - عدد النتائج في الصفحة</li>
                        <li><code>page</code> - رقم الصفحة</li>
                    </ul>
                    
                    <h4><?php _e('مثال:', 'askro'); ?></h4>
                    <pre><code>curl -X GET "<?php echo $this->get_api_base_url(); ?>/search?q=react+hooks&type=questions&category=programming" \
-H "Authorization: Bearer YOUR_JWT_TOKEN"</code></pre>
                </section>

                <!-- Leaderboard Endpoints -->
                <section class="docs-section">
                    <h2><?php _e('نقاط النهاية للمتصدرين (Leaderboard Endpoints)', 'askro'); ?></h2>
                    
                    <h3><?php _e('الحصول على قائمة المتصدرين', 'askro'); ?></h3>
                    <pre><code>GET <?php echo $this->api_base_url; ?>/leaderboard</code></pre>
                    
                    <h4><?php _e('المعاملات:', 'askro'); ?></h4>
                    <ul>
                        <li><code>timeframe</code> - الفترة الزمنية (all_time, weekly, monthly)</li>
                        <li><code>limit</code> - عدد المستخدمين (افتراضي: 10)</li>
                    </ul>
                    
                    <h4><?php _e('مثال:', 'askro'); ?></h4>
                    <pre><code>curl -X GET "<?php echo $this->api_base_url; ?>/leaderboard?timeframe=weekly&limit=20" \
-H "Authorization: Bearer YOUR_JWT_TOKEN"</code></pre>
                </section>

                <!-- User Endpoints -->
                <section class="docs-section">
                    <h2><?php _e('نقاط النهاية للمستخدمين (User Endpoints)', 'askro'); ?></h2>
                    
                    <h3><?php _e('الحصول على بيانات المستخدم', 'askro'); ?></h3>
                    <pre><code>GET <?php echo $this->api_base_url; ?>/users/{id}</code></pre>
                    
                    <h3><?php _e('الحصول على بيانات المستخدم الحالي', 'askro'); ?></h3>
                    <pre><code>GET <?php echo $this->api_base_url; ?>/users/me</code></pre>
                    
                    <h4><?php _e('مثال:', 'askro'); ?></h4>
                    <pre><code>curl -X GET "<?php echo $this->api_base_url; ?>/users/me" \
-H "Authorization: Bearer YOUR_JWT_TOKEN"</code></pre>
                </section>

                <!-- Response Format -->
                <section class="docs-section">
                    <h2><?php _e('تنسيق الاستجابة (Response Format)', 'askro'); ?></h2>
                    
                    <h3><?php _e('استجابة ناجحة:', 'askro'); ?></h3>
                    <pre><code>{
  "success": true,
  "data": {
    // البيانات المطلوبة
  },
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 100,
    "per_page": 20
  }
}</code></pre>
                    
                    <h3><?php _e('استجابة خطأ:', 'askro'); ?></h3>
                    <pre><code>{
  "success": false,
  "error": {
    "message": "رسالة الخطأ",
    "code": 400
  }
}</code></pre>
                </section>

                <!-- Error Codes -->
                <section class="docs-section">
                    <h2><?php _e('رموز الأخطاء (Error Codes)', 'askro'); ?></h2>
                    <ul>
                        <li><code>200</code> - نجح الطلب</li>
                        <li><code>201</code> - تم إنشاء المورد بنجاح</li>
                        <li><code>400</code> - طلب غير صحيح</li>
                        <li><code>401</code> - غير مصرح (مطلوب مصادقة)</li>
                        <li><code>403</code> - محظور (غير مصرح بالوصول)</li>
                        <li><code>404</code> - المورد غير موجود</li>
                        <li><code>429</code> - تجاوز حد الطلبات</li>
                        <li><code>500</code> - خطأ في الخادم</li>
                    </ul>
                </section>

                <!-- Rate Limiting -->
                <section class="docs-section">
                    <h2><?php _e('حد الطلبات (Rate Limiting)', 'askro'); ?></h2>
                    <p><?php _e('يتم تطبيق حدود على الطلبات لحماية الخادم:', 'askro'); ?></p>
                    <ul>
                        <li><strong>الأسئلة:</strong> 100 طلب في الساعة</li>
                        <li><strong>الإجابات:</strong> 50 طلب في الساعة</li>
                        <li><strong>التصويت:</strong> 200 طلب في الساعة</li>
                        <li><strong>التعليقات:</strong> 100 طلب في الساعة</li>
                        <li><strong>البحث:</strong> 300 طلب في الساعة</li>
                    </ul>
                </section>

                <!-- Testing Section -->
                <section class="docs-section">
                    <h2><?php _e('اختبار API (API Testing)', 'askro'); ?></h2>
                    
                    <div class="api-test-form">
                        <h3><?php _e('اختبار نقطة نهاية', 'askro'); ?></h3>
                        <form id="api-test-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="test-endpoint"><?php _e('نقطة النهاية:', 'askro'); ?></label>
                                    </th>
                                    <td>
                                        <select id="test-endpoint" name="endpoint">
                                            <option value="GET /questions">GET /questions</option>
                                            <option value="GET /questions/{id}">GET /questions/{id}</option>
                                            <option value="POST /questions">POST /questions</option>
                                            <option value="GET /search">GET /search</option>
                                            <option value="GET /leaderboard">GET /leaderboard</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="test-auth"><?php _e('طريقة المصادقة:', 'askro'); ?></label>
                                    </th>
                                    <td>
                                        <select id="test-auth" name="auth_method">
                                            <option value="bearer">Bearer Token</option>
                                            <option value="apikey">API Key</option>
                                            <option value="basic">Basic Auth</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="test-token"><?php _e('الرمز المميز:', 'askro'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="test-token" name="token" class="regular-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="test-data"><?php _e('بيانات الطلب (JSON):', 'askro'); ?></label>
                                    </th>
                                    <td>
                                        <textarea id="test-data" name="data" rows="5" cols="50"></textarea>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="submit" class="button button-primary">
                                    <?php _e('اختبار الطلب', 'askro'); ?>
                                </button>
                            </p>
                        </form>
                        
                        <div id="test-results" style="display: none;">
                            <h3><?php _e('نتائج الاختبار:', 'askro'); ?></h3>
                            <pre id="test-response"></pre>
                        </div>
                    </div>
                </section>

                <!-- SDK Examples -->
                <section class="docs-section">
                    <h2><?php _e('أمثلة SDK', 'askro'); ?></h2>
                    
                    <h3><?php _e('JavaScript/Node.js', 'askro'); ?></h3>
                    <pre><code>const axios = require('axios');

const api = axios.create({
  baseURL: '<?php echo $this->api_base_url; ?>',
  headers: {
    'Authorization': 'Bearer YOUR_JWT_TOKEN',
    'Content-Type': 'application/json'
  }
});

// الحصول على الأسئلة
const getQuestions = async () => {
  try {
    const response = await api.get('/questions', {
      params: {
        per_page: 10,
        orderby: 'date',
        order: 'DESC'
      }
    });
    return response.data;
  } catch (error) {
    console.error('Error:', error.response.data);
  }
};

// إنشاء سؤال جديد
const createQuestion = async (questionData) => {
  try {
    const response = await api.post('/questions', questionData);
    return response.data;
  } catch (error) {
    console.error('Error:', error.response.data);
  }
};</code></pre>
                    
                    <h3><?php _e('PHP', 'askro'); ?></h3>
                    <pre><code>&lt;?php
$api_url = '<?php echo $this->api_base_url; ?>';
$token = 'YOUR_JWT_TOKEN';

// الحصول على الأسئلة
function getQuestions($params = []) {
    global $api_url, $token;
    
    $url = $api_url . '/questions';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// إنشاء سؤال جديد
function createQuestion($data) {
    global $api_url, $token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '/questions');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}</code></pre>
                    
                    <h3><?php _e('Python', 'askro'); ?></h3>
                    <pre><code>import requests
import json

API_URL = '<?php echo $this->api_base_url; ?>'
TOKEN = 'YOUR_JWT_TOKEN'

headers = {
    'Authorization': f'Bearer {TOKEN}',
    'Content-Type': 'application/json'
}

# الحصول على الأسئلة
def get_questions(params=None):
    response = requests.get(f'{API_URL}/questions', headers=headers, params=params)
    return response.json()

# إنشاء سؤال جديد
def create_question(data):
    response = requests.post(f'{API_URL}/questions', headers=headers, json=data)
    return response.json()

# مثال للاستخدام
questions = get_questions({'per_page': 10, 'orderby': 'date'})
print(questions)</code></pre>
                </section>
            </div>
        </div>

        <style>
        .askro-api-docs {
            max-width: 1200px;
            margin: 20px 0;
        }
        
        .docs-section {
            margin-bottom: 40px;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .docs-section h2 {
            color: #23282d;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }
        
        .docs-section h3 {
            color: #23282d;
            margin-top: 20px;
        }
        
        .docs-section pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 3px;
            overflow-x: auto;
        }
        
        .docs-section code {
            background: #f0f0f0;
            padding: 2px 4px;
            border-radius: 3px;
        }
        
        .api-test-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        #test-results {
            margin-top: 20px;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 3px;
        }
        
        #test-response {
            background: #fff;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            max-height: 300px;
            overflow-y: auto;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#api-test-form').on('submit', function(e) {
                e.preventDefault();
                
                const endpoint = $('#test-endpoint').val();
                const authMethod = $('#test-auth').val();
                const token = $('#test-token').val();
                const data = $('#test-data').val();
                
                // بناء URL
                let url = '<?php echo $this->api_base_url; ?>' + endpoint.replace('GET ', '').replace('POST ', '');
                
                // إعداد الطلب
                const requestOptions = {
                    method: endpoint.startsWith('GET') ? 'GET' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                    }
                };
                
                // إضافة المصادقة
                if (token) {
                    switch (authMethod) {
                        case 'bearer':
                            requestOptions.headers['Authorization'] = 'Bearer ' + token;
                            break;
                        case 'apikey':
                            requestOptions.headers['Authorization'] = 'ApiKey ' + token;
                            break;
                        case 'basic':
                            requestOptions.headers['Authorization'] = 'Basic ' + btoa(token);
                            break;
                    }
                }
                
                // إضافة البيانات للطلب POST
                if (requestOptions.method === 'POST' && data) {
                    try {
                        requestOptions.body = data;
                    } catch (e) {
                        alert('بيانات JSON غير صحيحة');
                        return;
                    }
                }
                
                // إرسال الطلب
                fetch(url, requestOptions)
                    .then(response => response.json())
                    .then(data => {
                        $('#test-response').text(JSON.stringify(data, null, 2));
                        $('#test-results').show();
                    })
                    .catch(error => {
                        $('#test-response').text('خطأ: ' + error.message);
                        $('#test-results').show();
                    });
            });
        });
        </script>
        <?php
    }

    /**
     * Test API endpoint AJAX handler
     *
     * @since 1.0.0
     */
    public function test_api_endpoint() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'askro_test_api')) {
            wp_send_json_error(['message' => 'فشل التحقق من الأمان']);
        }

        $endpoint = sanitize_text_field($_POST['endpoint'] ?? '');
        $method = sanitize_text_field($_POST['method'] ?? 'GET');
        $data = $_POST['data'] ?? '';

        if (!$endpoint) {
            wp_send_json_error(['message' => 'نقطة النهاية مطلوبة']);
        }

        // بناء URL
        $url = $this->api_base_url . $endpoint;

        // إعداد الطلب
        $args = [
            'method' => $method,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-WP-Nonce' => wp_create_nonce('wp_rest')
            ]
        ];

        // إضافة البيانات للطلب POST
        if ($method === 'POST' && $data) {
            $args['body'] = $data;
        }

        // إرسال الطلب
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        wp_send_json_success([
            'response_code' => wp_remote_retrieve_response_code($response),
            'data' => $data
        ]);
    }

    /**
     * Generate API documentation in JSON format
     *
     * @return array
     * @since 1.0.0
     */
    public function generate_api_docs() {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Askro API',
                'description' => 'API for Askro Q&A Platform',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'Arashdi',
                    'url' => 'https://arashdi.com'
                ]
            ],
            'servers' => [
                [
                    'url' => $this->api_base_url,
                    'description' => 'Production server'
                ]
            ],
            'paths' => [
                '/questions' => [
                    'get' => [
                        'summary' => 'Get questions list',
                        'parameters' => [
                            [
                                'name' => 'per_page',
                                'in' => 'query',
                                'description' => 'Number of questions per page',
                                'schema' => ['type' => 'integer', 'default' => 15]
                            ],
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'description' => 'Page number',
                                'schema' => ['type' => 'integer', 'default' => 1]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Successful response',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'success' => ['type' => 'boolean'],
                                                'data' => ['type' => 'array'],
                                                'pagination' => ['type' => 'object']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'post' => [
                        'summary' => 'Create a new question',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['title', 'content'],
                                        'properties' => [
                                            'title' => ['type' => 'string'],
                                            'content' => ['type' => 'string'],
                                            'category' => ['type' => 'string'],
                                            'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                                            'status' => ['type' => 'string', 'enum' => ['open', 'closed', 'solved']]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Question created successfully'
                            ]
                        ]
                    ]
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ],
                    'apiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'Authorization'
                    ]
                ]
            ],
            'security' => [
                ['bearerAuth' => []],
                ['apiKeyAuth' => []]
            ]
        ];
    }
} 
