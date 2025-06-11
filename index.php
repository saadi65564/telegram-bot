<?php
            //8026240341:AAEkhuOr_OSc0-Q71A7g9lErsFE-2FHcOu0
    // $TOKEN = "8026240341:AAEkhuOr_OSc0-Q71A7g9lErsFE-2FHcOu0"; 
    $TOKEN = "8026240341:AAEkhuOr_OSc0-Q71A7g9lErsFE-2FHcOu0"; 

    // $apiURL = "https://api.telegram.org/bot$TOKEN/sendMessage";

// قراءة تحديثات تلجرام
$update = json_decode(file_get_contents('php://input'), TRUE);

$chat_id = $update['message']['chat']['id'] ?? null;
$user_id = $update['message']['from']['id'] ?? null;
$text = $update['message']['text'] ?? '';

// دالة إرسال رسالة
function sendMessage($chat_id, $text) {
    global $TOKEN;  // استخدم المتغير العام
    $url = "https://api.telegram.org/bot".$TOKEN."/sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

// دالة حذف رسالة
function deleteMessage($chat_id, $message_id) {
    global $TOKEN;
    $url = "https://api.telegram.org/bot".$TOKEN."/deleteMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ];
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

// دالة طرد عضو من المجموعة
function kickMember($chat_id, $user_id) {
    global $TOKEN;
    $url = "https://api.telegram.org/bot".$TOKEN."/kickChatMember";
    $post_fields = [
        'chat_id' => $chat_id,
        'user_id' => $user_id
    ];
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

// الكلمات الإعلانية للحذف
$ads_keywords = ['شراء', 'رابط', 'مجاناً', 'خصم', 'كود', 'subscribe', 'http', 'www'];

if ($chat_id && $text) {
    $text_lower = mb_strtolower($text);

    // حذف الرسائل التي تحتوي على كلمات إعلانية
    foreach ($ads_keywords as $keyword) {
        if (strpos($text_lower, $keyword) !== false) {
            $message_id = $update['message']['message_id'];
            deleteMessage($chat_id, $message_id);
            sendMessage($chat_id, "تم حذف إعلان غير مسموح به.");
            exit;
        }
    }

    // الرد على كلمة مرحبا
    if (strpos($text_lower, 'مرحبا') !== false) {
        sendMessage($chat_id, "أهلاً وسهلاً بك في المجموعة!");
    }

    // أمر طرد
    if ($text_lower == '/kick') {
        kickMember($chat_id, $user_id);
        sendMessage($chat_id, "تم طرد المستخدم.");
    }
}
?>
