<?php
$TOKEN = "8026240341:AAEkhuOr_OSc0-Q71A7g9lErsFE-2FHcOu0";

// قراءة تحديثات تلجرام
$update = json_decode(file_get_contents('php://input'), TRUE);

$chat_id = $update['message']['chat']['id'] ?? null;
$user_id = $update['message']['from']['id'] ?? null;
$text = $update['message']['text'] ?? '';
$first_name = $update['message']['from']['first_name'] ?? '';
$username = $update['message']['from']['username'] ?? '';
$mention = $username ? "@$username" : $first_name;

// دالة إرسال رسالة
function sendMessage($chat_id, $text) {
    global $TOKEN;
    $url = "https://api.telegram.org/bot".$TOKEN."/sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields)); 
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

// دالة كتم عضو في المجموعة لمدة 30 يوم
function muteMember($chat_id, $user_id) {
    global $TOKEN;
    $url = "https://api.telegram.org/bot".$TOKEN."/restrictChatMember";
    
    $until_date = time() + (30 * 24 * 60 * 60); // 30 يوم

    $permissions = [
        'can_send_messages' => false,
        'can_send_media_messages' => false,
        'can_send_polls' => false,
        'can_send_other_messages' => false,
        'can_add_web_page_previews' => false,
        'can_change_info' => false,
        'can_invite_users' => false,
        'can_pin_messages' => false
    ];

    $post_fields = [
        'chat_id' => $chat_id,
        'user_id' => $user_id,
        'permissions' => json_encode($permissions),
        'until_date' => $until_date
    ];

    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

// الكلمات الإعلانية للحذف
$ads_keywords = ['نوفر', 'تواصل معي ', 'للتواصل:', 'شركة استثمار ', 'نحل واجبات','@', 'subscribe', 'http', 'www'];

if ($chat_id && $text) {
    $text_lower = mb_strtolower($text);

    // حذف الرسائل التي تحتوي على كلمات إعلانية
    foreach ($ads_keywords as $keyword) {
        if (strpos($text_lower, $keyword) !== false) {
            $message_id = $update['message']['message_id'];
            deleteMessage($chat_id, $message_id);
            muteMember($chat_id, $user_id);
            $reason = "نشر إعلان مخالف يحتوي على كلمات محظورة.";
            $punishment = "تم كتم المستخدم لمدة 30 يومًا.";
            sendMessage($chat_id, "🚫 المستخدم $mention\n📌 السبب: $reason\n⏳ العقوبة: $punishment");
            exit;
        }
    }

    // الرد على "مرحبا"
    if (strpos($text_lower, 'مرحبا') !== false) {
        sendMessage($chat_id, "أهلاً وسهلاً بك في المجموعة!");
    }

    // الرد على "مكتب قبول"
    if (strpos($text_lower, 'مكتب قبول') !== false) {
        $response = "نُقدم قبولات جامعية ودورات لغة إنجليزية <b>مجانًا</b> من جامعات ومعاهد معتمدة من وزارات التعليم في الدول العربية، ولجميع المراحل الأكاديمية:
        
• اللغة الإنجليزية  
• البكالوريوس  
• الماجستير  
• الدكتوراه  
• البرامج الصيفية  
• الدورات التدريبية  

كما نوفر:  
• سكن للطلاب (مع عائلات بريطانية أو في سكن طلابي خاص)  
• استقبال وتوصيل من وإلى جميع مطارات بريطانيا  
• متابعة أكاديمية دورية مع تقارير مخصصة لأولياء الأمور (حسب عمر الطالب)  

📌 فرصتك للدراسة في بريطانيا تبدأ معنا!  
📲 تواصل معنا الآن عبر الواتساب، ولا تنسَ مشاركة هذا الإعلان مع من تحب ليستفيد الجميع.  

📞 واتساب وهاتف: +447772354489  
📧 البريد الإلكتروني: info@be-tc.co.uk  
🌐 الموقع الإلكتروني: www.be-tc.co.uk  

<b>British E-Training Centre LTD</b>  
شركة مسجلة في إنجلترا وويلز – رقم التسجيل: 13731156";
        
        sendMessage($chat_id, $response);
    }

    // أمر /kick يقوم بكتم العضو
    if ($text_lower == '/kick') {
        muteMember($chat_id, $user_id);
        sendMessage($chat_id, "🚫 المستخدم $mention\n📌 السبب: أمر إداري /kick\n⏳ العقوبة: كتم لمدة 30 يومًا.");
    }
}

// إظهار رسالة لو فتح الملف مباشرة من المتصفح
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "بوت تلجرام يعمل بنجاح.";
    exit;
}
?>
