<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verificar reCAPTCHA
$recaptchaSecret = $_POST['secret'] ?? '';
$recaptchaToken = $_POST['recaptchaToken'] ?? '';

if (empty($recaptchaSecret) || empty($recaptchaToken)) {
    http_response_code(400);
    echo json_encode(['error' => 'reCAPTCHA verification failed']);
    exit;
}

$recaptchaResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify');
$responseData = json_decode($recaptchaResponse);

if (!$responseData->success) {
    http_response_code(400);
    echo json_encode(['error' => 'reCAPTCHA verification failed']);
    exit;
}

// Procesar el formulario
$sendTo = $_POST['sendTo'] ?? '';
$titleWeb = $_POST['titleWeb'] ?? '';
$fromEmail = $_POST['fromEmail'] ?? '';
$nombre = $_POST['Nombre'] ?? '';
$apellido = $_POST['Apellido'] ?? '';
$email = $_POST['Email'] ?? '';
$telefono = $_POST['Telefono'] ?? '';
$mensaje = $_POST['Mensaje'] ?? '';
$fecha = $_POST['Fecha'] ?? '';
$id = $_POST['ID'] ?? '';

if (empty($sendTo) || empty($nombre) || empty($email) || empty($mensaje)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Enviar email
$to = $sendTo;
$subject = "Nuevo contacto de $titleWeb - $nombre $apellido";
$headers = [
    'From: ' . $fromEmail,
    'Reply-To: ' . $email,
    'Content-Type: text/html; charset=UTF-8'
];

$emailBody = "
<html>
<body>
    <h2>Nuevo contacto de $titleWeb</h2>
    <p><strong>ID:</strong> $id</p>
    <p><strong>Fecha:</strong> $fecha</p>
    <p><strong>Nombre:</strong> $nombre $apellido</p>
    <p><strong>Email:</strong> $email</p>
    <p><strong>Teléfono:</strong> $telefono</p>
    <p><strong>Mensaje:</strong></p>
    <p>" . nl2br(htmlspecialchars($mensaje)) . "</p>
</body>
</html>";

$mailSent = mail($to, $subject, $emailBody, implode("\r\n", $headers));

if ($mailSent) {
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send email']);
}
?>
