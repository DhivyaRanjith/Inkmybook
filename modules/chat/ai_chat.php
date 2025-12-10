<?php
require_once '../../config/openai_config.php';

header('Content-Type: application/json');

$message = $_POST['message'] ?? '';

if (empty($message)) {
    echo json_encode(['reply' => 'Please say something!']);
    exit;
}

// Check if API Key is set
if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === 'YOUR_OPENAI_API_KEY_HERE') {
    echo json_encode(['reply' => '⚠️ System Error: OpenAI API Key is missing. Please ask the admin to configure it in `config/openai_config.php`.']);
    exit;
}

// Prepare Data for OpenAI
$data = [
    'model' => OPENAI_MODEL,
    'messages' => [
        [
            'role' => 'system',
            'content' => 'You are the helpful AI support assistant for InkMyBook, a freelance services marketplace. 
            Your goal is to help users find services, understand pricing, and navigate the site. 
            Be polite, professional, and concise. 
            If you do not know the answer, suggest they contact human support via the "Support" tab.'
        ],
        [
            'role' => 'user',
            'content' => $message
        ]
    ],
    'temperature' => 0.7,
    'max_tokens' => 150
];

// Send Request via cURL
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . OPENAI_API_KEY
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    $reply = $result['choices'][0]['message']['content'] ?? 'Sorry, I could not understand that.';
    echo json_encode(['reply' => $reply]);
} else {
    // Fallback to Local Logic if API fails (e.g., Quota Exceeded)
    $reply = getFallbackReply($message);
    echo json_encode(['reply' => $reply . " (⚠️ AI Offline - Using Backup System)"]);
}

function getFallbackReply($msg)
{
    $msg = strtolower($msg);

    if (strpos($msg, 'hello') !== false || strpos($msg, 'hi') !== false) {
        return "Hello! Welcome to InkMyBook. How can I help you today?";
    }

    if (strpos($msg, 'price') !== false || strpos($msg, 'cost') !== false) {
        return "Our services start from just $10! You can browse various categories to find the best deal.";
    }

    if (strpos($msg, 'hire') !== false || strpos($msg, 'writer') !== false || strpos($msg, 'designer') !== false) {
        return "To hire a professional, simply browse our 'Services' page, choose a freelancer, and click 'Order Now'.";
    }

    if (strpos($msg, 'support') !== false || strpos($msg, 'help') !== false) {
        return "I can connect you to a human agent. Please click the 'Human Support' tab above.";
    }

    return "I'm currently having trouble connecting to my brain (OpenAI), but I'm here! For complex queries, please switch to the 'Human Support' tab.";
}
?>