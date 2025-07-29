<?php
// Create a test answer
require_once '../wp-config.php';

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
echo "Creating answer for question: " . $question->post_title . " (ID: " . $question->ID . ")\n";

// Create test answer
$answer_data = [
    'post_title' => 'إجابة تجريبية على: ' . $question->post_title,
    'post_content' => 'هذه إجابة تجريبية لاختبار نظام الإجابات. تحتوي على محتوى مفصل يوضح كيفية حل المشكلة المطروحة في السؤال.

يمكن أن تحتوي الإجابة على:
- نقاط متعددة
- أمثلة عملية
- كود برمجي
- روابط مفيدة

هذا النص مكتوب باللغة العربية لاختبار عرض النصوص العربية في النظام.',
    'post_status' => 'publish',
    'post_type' => 'askro_answer',
    'post_author' => 1 // Admin user
];

$answer_id = wp_insert_post($answer_data);

if (is_wp_error($answer_id)) {
    echo "Error creating answer: " . $answer_id->get_error_message() . "\n";
} else {
    // Link answer to question
    update_post_meta($answer_id, '_askro_question_id', $question->ID);
    
    echo "Successfully created answer with ID: " . $answer_id . "\n";
    echo "Linked to question ID: " . $question->ID . "\n";
    
    // Verify the link
    $linked_question_id = get_post_meta($answer_id, '_askro_question_id', true);
    echo "Verified link - Answer " . $answer_id . " is linked to question " . $linked_question_id . "\n";
}

// Create a second test answer
$answer_data2 = [
    'post_title' => 'إجابة تجريبية ثانية على: ' . $question->post_title,
    'post_content' => 'هذه إجابة تجريبية ثانية لاختبار عرض الإجابات المتعددة. 

هذه الإجابة تحتوي على:
1. قائمة مرقمة
2. أمثلة عملية
3. شرح مفصل

هذا يساعد في اختبار:
- عرض الإجابات المتعددة
- أزرار التصويت
- نظام التعليقات
- التصميم العام',
    'post_status' => 'publish',
    'post_type' => 'askro_answer',
    'post_author' => 1 // Admin user
];

$answer_id2 = wp_insert_post($answer_data2);

if (is_wp_error($answer_id2)) {
    echo "Error creating second answer: " . $answer_id2->get_error_message() . "\n";
} else {
    // Link answer to question
    update_post_meta($answer_id2, '_askro_question_id', $question->ID);
    
    echo "Successfully created second answer with ID: " . $answer_id2 . "\n";
}

echo "\nDone! Check the question page now.\n";
?> 
