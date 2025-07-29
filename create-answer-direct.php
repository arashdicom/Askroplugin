<?php
// Create answer directly in database
require_once '../wp-config.php';

echo "=== Creating Test Answer ===\n";

// Get the first question
$questions = get_posts([
    'post_type' => 'askro_question',
    'post_status' => 'publish',
    'numberposts' => 1
]);

if (empty($questions)) {
    echo "No questions found!\n";
    exit;
}

$question = $questions[0];
echo "Question: " . $question->post_title . " (ID: " . $question->ID . ")\n";

// Create answer post
$answer_data = [
    'post_title' => 'إجابة تجريبية على: ' . $question->post_title,
    'post_content' => 'هذه إجابة تجريبية لاختبار نظام الإجابات والتقييم والتعليقات.

المحتوى يتضمن:
- نقاط متعددة
- أمثلة عملية
- شرح مفصل
- كود برمجي

هذا يساعد في اختبار:
1. عرض الإجابات
2. أزرار التصويت
3. نظام التعليقات
4. التصميم العام',
    'post_status' => 'publish',
    'post_type' => 'askro_answer',
    'post_author' => 1, // Admin user
    'post_date' => current_time('mysql'),
    'post_date_gmt' => current_time('mysql', 1)
];

$answer_id = wp_insert_post($answer_data);

if (is_wp_error($answer_id)) {
    echo "Error creating answer: " . $answer_id->get_error_message() . "\n";
    exit;
}

echo "Answer created with ID: " . $answer_id . "\n";

// Link answer to question
$meta_result = update_post_meta($answer_id, '_askro_question_id', $question->ID);
echo "Meta link created: " . ($meta_result ? 'Success' : 'Failed') . "\n";

// Verify the link
$linked_question_id = get_post_meta($answer_id, '_askro_question_id', true);
echo "Verified link - Answer " . $answer_id . " linked to question " . $linked_question_id . "\n";

// Create a second answer for testing multiple answers
$answer_data2 = [
    'post_title' => 'إجابة تجريبية ثانية على: ' . $question->post_title,
    'post_content' => 'هذه إجابة تجريبية ثانية لاختبار عرض الإجابات المتعددة.

هذه الإجابة تحتوي على:
1. قائمة مرقمة
2. أمثلة عملية
3. شرح مفصل
4. نصائح مفيدة

هذا يساعد في اختبار:
- عرض الإجابات المتعددة
- أزرار التصويت لكل إجابة
- نظام التعليقات لكل إجابة
- التصميم العام للصفحة',
    'post_status' => 'publish',
    'post_type' => 'askro_answer',
    'post_author' => 1, // Admin user
    'post_date' => current_time('mysql'),
    'post_date_gmt' => current_time('mysql', 1)
];

$answer_id2 = wp_insert_post($answer_data2);

if (is_wp_error($answer_id2)) {
    echo "Error creating second answer: " . $answer_id2->get_error_message() . "\n";
} else {
    update_post_meta($answer_id2, '_askro_question_id', $question->ID);
    echo "Second answer created with ID: " . $answer_id2 . "\n";
}

// Verify answers exist
$answers = get_posts([
    'post_type' => 'askro_answer',
    'post_status' => 'publish',
    'meta_query' => [
        [
            'key' => '_askro_question_id',
            'value' => $question->ID
        ]
    ],
    'numberposts' => -1
]);

echo "\n=== Verification ===\n";
echo "Total answers for question " . $question->ID . ": " . count($answers) . "\n";

foreach ($answers as $answer) {
    echo "- Answer ID: " . $answer->ID . ", Title: " . $answer->post_title . "\n";
}

echo "\n✅ Done! Now refresh the question page to see the answers.\n";
?> 
