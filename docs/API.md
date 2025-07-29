# Askro API Documentation

## نظرة عامة

API الخاص بـ Askro يوفر وصولاً برمجياً لجميع ميزات منصة الأسئلة والأجوبة. يدعم API المصادقة المتعددة، التخزين المؤقت، وحد الطلبات للحماية.

## معلومات أساسية

- **Base URL**: `https://your-domain.com/wp-json/askro/v1`
- **Content-Type**: `application/json`
- **Authentication**: Bearer Token, API Key, أو Basic Auth

## المصادقة (Authentication)

### 1. Bearer Token (JWT)

```http
Authorization: Bearer <your_jwt_token>
```

### 2. API Key

```http
Authorization: ApiKey <your_api_key>
```

### 3. Basic Authentication

```http
Authorization: Basic <base64_encoded_credentials>
```

## نقاط النهاية (Endpoints)

### الأسئلة (Questions)

#### الحصول على قائمة الأسئلة

```http
GET /questions
```

**المعاملات:**
- `per_page` (int): عدد الأسئلة في الصفحة (افتراضي: 15)
- `page` (int): رقم الصفحة (افتراضي: 1)
- `orderby` (string): ترتيب حسب (date, title, views, answers, votes)
- `order` (string): اتجاه الترتيب (ASC, DESC)
- `category` (string): تصفية حسب الفئة
- `tag` (string): تصفية حسب الوسم
- `status` (string): تصفية حسب الحالة (open, closed, solved)

**مثال:**
```bash
curl -X GET "https://your-domain.com/wp-json/askro/v1/questions?per_page=10&orderby=date&order=DESC" \
-H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### الحصول على سؤال واحد

```http
GET /questions/{id}
```

**مثال:**
```bash
curl -X GET "https://your-domain.com/wp-json/askro/v1/questions/123" \
-H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### إنشاء سؤال جديد

```http
POST /questions
```

**بيانات الطلب:**
```json
{
  "title": "عنوان السؤال",
  "content": "محتوى السؤال",
  "category": "programming",
  "tags": ["javascript", "react"],
  "status": "open"
}
```

**مثال:**
```bash
curl -X POST "https://your-domain.com/wp-json/askro/v1/questions" \
-H "Authorization: Bearer YOUR_JWT_TOKEN" \
-H "Content-Type: application/json" \
-d '{
  "title": "كيفية استخدام React Hooks؟",
  "content": "أريد معرفة كيفية استخدام React Hooks في مشروعي...",
  "category": "programming",
  "tags": ["javascript", "react", "hooks"]
}'
```

### الإجابات (Answers)

#### الحصول على إجابات السؤال

```http
GET /questions/{question_id}/answers
```

**المعاملات:**
- `per_page` (int): عدد الإجابات في الصفحة (افتراضي: 20)
- `page` (int): رقم الصفحة (افتراضي: 1)

#### إضافة إجابة

```http
POST /questions/{question_id}/answers
```

**بيانات الطلب:**
```json
{
  "content": "محتوى الإجابة"
}
```

**مثال:**
```bash
curl -X POST "https://your-domain.com/wp-json/askro/v1/questions/123/answers" \
-H "Authorization: Bearer YOUR_JWT_TOKEN" \
-H "Content-Type: application/json" \
-d '{
  "content": "يمكنك استخدام React Hooks كالتالي..."
}'
```

### التصويت (Voting)

#### التصويت على منشور

```http
POST /posts/{post_id}/vote
```

**بيانات الطلب:**
```json
{
  "vote_type": "useful",
  "vote_value": 1
}
```

**أنواع التصويت المتاحة:**
- `useful` - مفيد
- `innovative` - مبتكر
- `well_researched` - مدروس جيداً
- `incorrect` - غير صحيح
- `redundant` - مكرر

**مثال:**
```bash
curl -X POST "https://your-domain.com/wp-json/askro/v1/posts/123/vote" \
-H "Authorization: Bearer YOUR_JWT_TOKEN" \
-H "Content-Type: application/json" \
-d '{
  "vote_type": "useful",
  "vote_value": 1
}'
```

### البحث (Search)

#### البحث في المحتوى

```http
GET /search
```

**المعاملات:**
- `q` (string): نص البحث (مطلوب)
- `type` (string): نوع البحث (questions, answers)
- `category` (string): تصفية حسب الفئة
- `tag` (string): تصفية حسب الوسم
- `status` (string): تصفية حسب الحالة
- `per_page` (int): عدد النتائج في الصفحة
- `page` (int): رقم الصفحة

**مثال:**
```bash
curl -X GET "https://your-domain.com/wp-json/askro/v1/search?q=react+hooks&type=questions&category=programming" \
-H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### المتصدرين (Leaderboard)

#### الحصول على قائمة المتصدرين

```http
GET /leaderboard
```

**المعاملات:**
- `timeframe` (string): الفترة الزمنية (all_time, weekly, monthly)
- `limit` (int): عدد المستخدمين (افتراضي: 10)

**مثال:**
```bash
curl -X GET "https://your-domain.com/wp-json/askro/v1/leaderboard?timeframe=weekly&limit=20" \
-H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### المستخدمين (Users)

#### الحصول على بيانات المستخدم

```http
GET /users/{id}
```

#### الحصول على بيانات المستخدم الحالي

```http
GET /users/me
```

**مثال:**
```bash
curl -X GET "https://your-domain.com/wp-json/askro/v1/users/me" \
-H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## تنسيق الاستجابة

### استجابة ناجحة

```json
{
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
}
```

### استجابة خطأ

```json
{
  "success": false,
  "error": {
    "message": "رسالة الخطأ",
    "code": 400
  }
}
```

## رموز الأخطاء

| الكود | الوصف |
|-------|--------|
| 200 | نجح الطلب |
| 201 | تم إنشاء المورد بنجاح |
| 400 | طلب غير صحيح |
| 401 | غير مصرح (مطلوب مصادقة) |
| 403 | محظور (غير مصرح بالوصول) |
| 404 | المورد غير موجود |
| 429 | تجاوز حد الطلبات |
| 500 | خطأ في الخادم |

## حد الطلبات (Rate Limiting)

يتم تطبيق حدود على الطلبات لحماية الخادم:

| النقطة النهائية | الحد | النافذة الزمنية |
|----------------|------|-----------------|
| الأسئلة | 100 طلب | ساعة واحدة |
| الإجابات | 50 طلب | ساعة واحدة |
| التصويت | 200 طلب | ساعة واحدة |
| التعليقات | 100 طلب | ساعة واحدة |
| البحث | 300 طلب | ساعة واحدة |

## أمثلة SDK

### JavaScript/Node.js

```javascript
const axios = require('axios');

const api = axios.create({
  baseURL: 'https://your-domain.com/wp-json/askro/v1',
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
};
```

### PHP

```php
<?php
$api_url = 'https://your-domain.com/wp-json/askro/v1';
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
}
```

### Python

```python
import requests
import json

API_URL = 'https://your-domain.com/wp-json/askro/v1'
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
print(questions)
```

## إدارة API Keys

### إنشاء API Key

```php
<?php
// إنشاء API key للمستخدم
$api_key = askro_generate_api_key($user_id);
echo $api_key;
```

### الحصول على API Keys للمستخدم

```php
<?php
// الحصول على جميع API keys للمستخدم
$api_keys = askro_get_user_api_keys($user_id);
foreach ($api_keys as $key) {
    echo "Key: " . $key['key'] . " - Created: " . $key['created_at'] . "\n";
}
```

### إلغاء API Key

```php
<?php
// إلغاء API key
$result = askro()->get_component('api_auth')->revoke_api_key($api_key);
if ($result) {
    echo "تم إلغاء API key بنجاح";
}
```

## إحصائيات الاستخدام

### الحصول على إحصائيات API

```php
<?php
// الحصول على إحصائيات استخدام API للمستخدم
$stats = askro_get_api_usage_stats($user_id);
foreach ($stats as $stat) {
    echo "Date: " . $stat->date . " - Requests: " . $stat->requests . "\n";
}
```

### الحصول على إحصائيات التخزين المؤقت

```php
<?php
// الحصول على إحصائيات التخزين المؤقت
$cache_stats = askro_get_api_cache_stats();
echo "Hits: " . $cache_stats['hits'] . " - Misses: " . $cache_stats['misses'] . "\n";
```

## اختبار API

### اختبار نقطة نهاية

```php
<?php
// اختبار نقطة نهاية API
$result = askro_test_api_endpoint('/questions', 'GET', [], 'YOUR_JWT_TOKEN');
if ($result['success']) {
    echo "Status Code: " . $result['status_code'] . "\n";
    print_r($result['data']);
} else {
    echo "Error: " . $result['error'] . "\n";
}
```

### الحصول على حالة API

```php
<?php
// الحصول على حالة API
$health = askro_get_api_health_status();
if ($health['api_enabled']) {
    echo "API is enabled and working\n";
} else {
    echo "API is disabled\n";
}
```

## أفضل الممارسات

1. **استخدم HTTPS دائماً** - تأكد من استخدام HTTPS لجميع الطلبات
2. **احفظ الرموز المميزة بأمان** - لا تشارك الرموز المميزة أو API keys
3. **تعامل مع الأخطاء** - تأكد من التعامل مع جميع أنواع الأخطاء
4. **استخدم التخزين المؤقت** - استفد من التخزين المؤقت لتحسين الأداء
5. **راقب حد الطلبات** - تأكد من عدم تجاوز حدود الطلبات
6. **استخدم المعاملات المناسبة** - استخدم المعاملات المطلوبة فقط
7. **تحقق من الاستجابات** - تأكد من التحقق من جميع الاستجابات

## الدعم

للمساعدة والدعم التقني، يرجى التواصل معنا عبر:
- البريد الإلكتروني: support@arashdi.com
- الموقع الإلكتروني: https://arashdi.com

