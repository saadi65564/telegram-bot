<?php
$TOKEN = "8026240341:AAEkhuOr_OSc0-Q71A7g9lErsFE-2FHcOu0";

// قراءة التحديثات
$update = json_decode(file_get_contents('php://input'), TRUE);

$chat_id = $update['message']['chat']['id'] ?? null;
$user_id = $update['message']['from']['id'] ?? null;
$text = $update['message']['text'] ?? '';
$first_name = $update['message']['from']['first_name'] ?? '';
$username = $update['message']['from']['username'] ?? '';
$mention = $username ? "@$username" : $first_name;

// دالة التحقق من الصلاحيات
function isAdminOrOwner($chat_id, $user_id) {
    global $TOKEN;
    $url = "https://api.telegram.org/bot$TOKEN/getChatMember?chat_id=$chat_id&user_id=$user_id";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    return isset($data['result']['status']) && in_array($data['result']['status'], ['administrator', 'creator']);
}

// دالة إرسال رسالة
function sendMessage($chat_id, $text) {
    global $TOKEN;
    $url = "https://api.telegram.org/bot$TOKEN/sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields)); 
    curl_exec($ch);
    curl_close($ch);
}

// دالة إرسال رسالة مع زر إلغاء الكتم
function sendMuteMessageWithButton($chat_id, $user_id, $mention, $reason) {
    global $TOKEN;

    $text = "🚫 المستخدم $mention : $reason\n العقوبة: مكتوم 🔇.";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🚨 إلغاء الكتم', 'callback_data' => "unmute:$chat_id:$user_id"]
            ]
        ]
    ];

    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $text,
        'reply_markup' => json_encode($keyboard),
        'parse_mode' => 'HTML'
    ];

    $url = "https://api.telegram.org/bot$TOKEN/sendMessage";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_exec($ch);
    curl_close($ch);
}

// دالة حذف رسالة
function deleteMessage($chat_id, $message_id) {
    global $TOKEN;
    $url = "https://api.telegram.org/bot$TOKEN/deleteMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ];
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
    curl_exec($ch);
    curl_close($ch);
}

// دالة كتم عضو
function muteMember($chat_id, $user_id) {
    global $TOKEN;
    $url = "https://api.telegram.org/bot$TOKEN/restrictChatMember";
    $until_date = time() + (30 * 24 * 60 * 60);
    $mute_date = date("Y-m-d H:i", $until_date);
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
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
    curl_exec($ch);
    curl_close($ch);
}

// الترحيب بأعضاء جدد
if (isset($update['message']['new_chat_members'])) {
    foreach ($update['message']['new_chat_members'] as $new_member) {
        $name = $new_member['first_name'] ?? 'عضو جديد';
        $username = $new_member['username'] ?? '';
        $mention = $username ? "@$username" : $name;
        sendMessage($chat_id, "🎉 مرحباً بك $mention في المجموعة! نتمنى لك وقتاً ممتعاً ومفيداً 🌟");
    }
}

// فلترة الإعلانات
// $ads_keywords = ['نوفر', 'تواصل معي ', 'للتواصل:', 'شركة استثمار ', 'نحل واجبات','@', 'subscribe', 'http', 'www'];

$ads_keywords = [
    'http' => 'نشر رابط مخالف',
    'www' => 'نشر رابط مخالف',
    '@' => 'نشر رابط مخالف',
    'subscribe' => 'نشر إعلان اشتراك',
    'نوفر' => 'نشر إعلان ',
    'تواصل معي' => 'نشر كلمة محضورة',
    'للتواصل:' => 'نشر كلمة محضورة',
    'شركة استثمار' => 'نشر إعلان ',
    'نحل واجبات' => 'طلب خدمات ممنوعة',
];


if ($chat_id && $text) {
    $text_lower = mb_strtolower($text);
    foreach ($ads_keywords as $keyword=>$reason_text) {
        if (strpos($text_lower, $keyword) !== false) {
            if (!isAdminOrOwner($chat_id, $user_id)) {
                deleteMessage($chat_id, $update['message']['message_id']);
                muteMember($chat_id, $user_id);
                sendMuteMessageWithButton($chat_id, $user_id, $mention, $reason_text);
                exit;
            }
        }
    }

    // الرد على كلمات القبول
    $acceptance_keywords = [
        'مكتب قبول', 'مكتب يقدم على الجامعات', 'مكتب تقديم',
        'احتاج قبول', 'قبول مشروط', 'قبول غير مشروط',
        'ابغى قبول', 'كم تكلفة'
    ];

    foreach ($acceptance_keywords as $keyword) {
        if (strpos($text_lower, $keyword) !== false) {
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
            break;
        }
    }

    // أمر /kick (يقوم بالكتم + زر إلغاء الكتم)
    if ($text_lower == '/kick') {
        muteMember($chat_id, $user_id);
        sendMuteMessageWithButton($chat_id, $user_id, $mention, "أمر إداري /kick");
    }
}

// منع الدخول المباشر من المتصفح
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "بوت تلجرام يعمل بنجاح.";
    exit;
}

// التعامل مع زر "إلغاء الكتم"
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $data = explode(":", $callback['data']);

    if ($data[0] === 'unmute') {
        $chat_id_cb = $data[1];
        $user_id_cb = $data[2];
        $caller_id = $callback['from']['id'];

        if (isAdminOrOwner($chat_id_cb, $caller_id)) {
            $url = "https://api.telegram.org/bot$TOKEN/restrictChatMember";
            $permissions = [
                'can_send_messages' => true,
                'can_send_media_messages' => true,
                'can_send_polls' => true,
                'can_send_other_messages' => true,
                'can_add_web_page_previews' => true,
                'can_change_info' => false,
                'can_invite_users' => true,
                'can_pin_messages' => false
            ];
            $post_fields = [
                'chat_id' => $chat_id_cb,
                'user_id' => $user_id_cb,
                'permissions' => json_encode($permissions)
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            curl_exec($ch);
            curl_close($ch);

            $msg_id = $callback['message']['message_id'];
            $edit_text = $callback['message']['text'] . "\n\n✅ تم إلغاء الكتم بواسطة المشرف.";
            file_get_contents("https://api.telegram.org/bot$TOKEN/editMessageText?chat_id=$chat_id_cb&message_id=$msg_id&text=" . urlencode($edit_text));
        }else {
    // عرض رسالة منبثقة بأنه لا يملك صلاحية
    $callback_id = $callback['id'];
    $message = "🚫 ليس لديك صلاحية لإلغاء الكتم.";
    file_get_contents("https://api.telegram.org/bot$TOKEN/answerCallbackQuery?callback_query_id=$callback_id&text=" . urlencode($message) . "&show_alert=true");
}
    }
}
?>
