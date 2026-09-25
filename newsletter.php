<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$safeEmail = str_replace(["\r", "\n"], '', $email);
$siteEmail = 'info@dscpls.co';
$brandName = 'DSCPLS';
$envelopeSender = '-f' . $siteEmail;

// Internal notification.
$adminSubject = 'DSCPLS — New newsletter signup';
$adminBody = "A new visitor joined the DSCPLS newsletter.\n\nEmail: {$safeEmail}\n\nSubmitted: " . gmdate('Y-m-d H:i:s') . " UTC\n";
$adminHeaders = [
    'From: DSCPLS Website <' . $siteEmail . '>',
    'Reply-To: ' . $safeEmail,
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion()
];
$adminSent = @mail($siteEmail, $adminSubject, $adminBody, implode("\r\n", $adminHeaders), $envelopeSender);





// Customer confirmation / autoresponder.
// The exact HTML in newsletter.html is what the subscriber receives in their inbox.
$customerSubject = 'DSCPLS — You’re in. Launching this fall.';
$templatePath = __DIR__ . '/newsletter.html';
$customerHtml = @file_get_contents($templatePath);

if ($customerHtml === false || trim($customerHtml) === '') {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'customer_sent' => false,
        'message' => 'The newsletter email template could not be loaded.'
    ]);
    exit;
}

$customerHeaders = [
    'From: ' . $brandName . ' <' . $siteEmail . '>',
    'Reply-To: ' . $siteEmail,
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion()
];
$customerSent = @mail($safeEmail, $customerSubject, $customerHtml, implode("\r\n", $customerHeaders), $envelopeSender);

// Report the real delivery attempt result instead of silently hiding an autoresponder failure.
if (!$adminSent) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'customer_sent' => $customerSent,
        'message' => 'We could not complete the signup. Please try again.'
    ]);
    exit;
}

if (!$customerSent) {
    http_response_code(202);
    echo json_encode([
        'ok' => true,
        'customer_sent' => false,
        'message' => 'You’re signed up. The confirmation email could not be sent by this server, so please check your hosting mail settings.'
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'customer_sent' => true,
    'message' => 'Thank you — you’re signed up. Check your inbox for your DSCPLS confirmation email.'
]);
