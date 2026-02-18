<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Validar reCAPTCHA
    $recaptchaSecret = '6Lci9mcsAAAAAAUf6S8tbPZvC9lcqkOX0mJsNlXY';
    $recaptchaToken = $_POST['recaptchaToken'] ?? '';
    
    $recaptchaResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptchaSecret . '&response=' . $recaptchaToken);
    $recaptchaData = json_decode($recaptchaResponse, true);
    
    if (!$recaptchaData['success']) {
        throw new Exception('Verificación reCAPTCHA fallida');
    }
    
    // Preparar email
    $to = $_POST['sendTo'] ?? 'baristrobrunch@gmail.com';
    $subject = 'Nuevo contacto desde GoodGYM';
    $fromEmail = $_POST['fromEmail'] ?? 'formularios@infopaginas.com';
    
    // Obtener datos del formulario
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $message = $_POST['message'] ?? '';
    $plan = $_POST['plan'] ?? '';
    $date = $_POST['Fecha'] ?? date('Y-m-d');
    $id = $_POST['ID'] ?? '';
    
    // Crear contenido del email
    $emailContent = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: #ff6b35; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .field { margin-bottom: 15px; }
            .field-label { font-weight: bold; color: #1a1a2e; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #666; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Nuevo Contacto - GoodGYM</h1>
            <p>Has recibido un nuevo mensaje de contacto</p>
        </div>
        <div class='content'>
            <div class='field'>
                <span class='field-label'>Nombre:</span> $name
            </div>
            <div class='field'>
                <span class='field-label'>Email:</span> $email
            </div>
            <div class='field'>
                <span class='field-label'>Teléfono:</span> $phone
            </div>
            <div class='field'>
                <span class='field-label'>Plan de interés:</span> $plan
            </div>
            <div class='field'>
                <span class='field-label'>Mensaje:</span><br>
                " . nl2br($message) . "
            </div>
            <div class='field'>
                <span class='field-label'>Fecha:</span> $date
            </div>
            <div class='field'>
                <span class='field-label'>ID:</span> $id
            </div>
        </div>
        <div class='footer'>
            <p>Este mensaje fue enviado desde el formulario de contacto de GoodGYM</p>
        </div>
    </body>
    </html>";
    
    // Enviar email
    $headers = "From: $fromEmail\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    if (mail($to, $subject, $emailContent, $headers)) {
        // Enviar a Google Sheets
        $googleSheetsUrl = 'https://script.google.com/macros/s/AKfycbytTe2O8usDwCRYrmDDtmR_KEYSJkNvxYZkazbuZ0McyM4x9EOPzuDXn2JCShA7tBFgaQ/exec';
        
        $postData = [
            'sendTo' => $to,
            'titleWeb' => 'GoodGYM',
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'plan' => $plan,
            'Fecha' => $date,
            'ID' => $id,
            'method' => 'POST'
        ];
        
        $options = [
            'http' => [
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'method' => 'POST',
                'content' => http_build_query($postData)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($googleSheetsUrl, false, $context);
        
        echo json_encode([
            'success' => true,
            'message' => 'Mensaje enviado correctamente'
        ]);
    } else {
        throw new Exception('Error al enviar el email');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
