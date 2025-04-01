<?php
include 'db.php';
require_once "vendor/autoload.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
header('Content-Type: application/json');
$meetTuesday = 'https://meet.google.com/fyk-pjyw-fro';
$meetThursday = 'https://meet.google.com/udb-ycho-qym';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = json_decode(file_get_contents('php://input'), true);
    $api = $data['api'] ?? null;
    echo json_encode(["error" => "Endpoint not found"]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $api = $data['api'] ?? null;
    if ($api === "customers") {
        $platform = $data['platform'] ?? null;
        if (!isset($platform)) {
            echo json_encode(["error" => "Request without platform", "OK" => 0]);
            return;
        }
        $url = "https://secure.tecnomotum.com/apis/srmotum/clientsmw2";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        $response = curl_exec($ch);
        $json = json_decode($response);
        if ($response === FALSE) {
            $error = curl_error($ch);
            curl_close($ch);
            echo json_encode(["error" => "Error retrieving customers data", "OK" => 0]);
        }
        curl_close($ch);
        $filterKey = 'platformIds';
        $filterValue = ($platform === "EFL") ? 2 : (($platform === "TELEMATICS") ? 3 : null);
        if (!isset($filterValue)) {
            echo json_encode(["error" => "Platform not found", "OK" => 0]);
            return;
        }
        $filteredArray = array_filter($json, function($item) use ($filterKey, $filterValue) {
            return $item->platformIds[0] == $filterValue;
        });
        $filteredArray = array_values($filteredArray);
        echo json_encode(["error" => "NO", "OK" => 1, "data" => $filteredArray]);
        return;
    }
    if ($api === "trainings") {
        $stmt = $conn->prepare("SELECT t.*, COUNT(e.id_training) AS total_enrollments FROM training t LEFT JOIN enrollments e ON t.id = e.id_training GROUP BY t.id ORDER BY t.date DESC");
        $stmt->execute();
        $trainingCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($trainingCount) {
            echo json_encode($trainingCount);
            return;
        } else {
            echo json_encode(["error" => "Enrollments not found"]);
            return;
        }
    }
    if ($api === "trainings_participants") {
        $id_training = $data['id_training'] ?? null;
        if (isset($id_training)) {
            $stmt = $conn->prepare("
                SELECT 
                    e.id_training, 
                    e.id_participant, 
                    e.attended, 
                    e.created_at, 
                    p.fullname, 
                    p.email, 
                    p.phone_number, 
                    p.whatsapp,
                    p.platform,
                    p.customer
                FROM enrollments e
                INNER JOIN participants p ON e.id_participant = p.id
                WHERE e.id_training = ?
            ");
            $stmt->execute([$id_training]);
            $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($enrollments);
            return;
        } else {
            echo json_encode(["error" => "No ID found"]);
            return;
        }
    }
    if ($api === "resend_email") {
        $currentEmail = $data['email'] ?? null;
        $currentDate = $data['date'] ?? null;
        $currentFullName = $data['fullname'] ?? null;
        $selectedDay = $data['day'] ?? null;
        $imagePath = $data['platform'] === "TELEMATICS" ? "./assets/motum.png" : "./assets/EFL.png";
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->Host = "smtp.gmail.com";
            $mail->Port = 465;
            $mail->Username = "oscararias@tecnomotum.com";
            $mail->Password = "fgldbsgcpqyfjqff";
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    
            $mail->setFrom('oscararias@tecnomotum.com', 'Oscar Arias');
            $mail->addAddress("{$currentEmail}");
    
            $mail->isHTML(true);
            $mail->Subject = ('Capacitación con CIM - ' . $currentDate);
            $mail->AddEmbeddedImage($imagePath, 'logo');
            $mail->Body = '
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>CIM Capacitación</title>
                    <style>
                        /* Reset styles */
                        body, html {
                            margin: 0;
                            padding: 0;
                            font-family: Arial, sans-serif;
                            line-height: 1.6;
                            background-color: #f4f4f4;
                        }
    
                        /* Container */
                        .email-container {
                            max-width: 600px;
                            margin: 0 auto;
                            background-color: #ffffff;
                            border: 1px solid #dddddd;
                        }
    
                        /* Header */
                        .header {
                            background-color: #0073e6;
                            color: #ffffff;
                            padding: 20px;
                            text-align: center;
                        }
    
                        .header h1 {
                            margin: 0;
                            font-size: 24px;
                        }

                        .header-image {
                            max-width: 20%; /* Adjust this value to make the image smaller or larger */
                            height: auto; /* Maintains aspect ratio */
                            margin-bottom: 10px;
                            padding: 5px;
                            border-radius: 8px; /* Optional: Rounded corners */
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Optional: Adds a subtle shadow */
                        }
    
                        /* Content */
                        .content {
                            padding: 20px;
                            color: #333333;
                        }
    
                        .content h2 {
                            font-size: 20px;
                            margin-top: 0;
                        }
    
                        .content p {
                            font-size: 16px;
                        }
    
                        .content a {
                            text-decoration: none;
                        }
    
                        .content a:hover {
                            text-decoration: underline;
                        }
    
                        /* Button */
                        .button {
                            display: inline-block;
                            color: #ffffff; /* Color del texto en blanco para mejor contraste */
                            background-color: #0073e6; /* Color de fondo azul */
                            padding: 10px 20px;
                            text-decoration: none;
                            font-size: 16px;
                            margin: 20px 0;
                            border-radius: 5px;
                        }
    
                        .button:hover {
                            background-color: #005bb5;
                        }
    
                        /* Footer */
                        .footer {
                            background-color: #f4f4f4;
                            padding: 10px;
                            text-align: center;
                            font-size: 14px;
                            color: #777777;
                        }
    
                        .footer a {
                            color: #0073e6;
                            text-decoration: none;
                        }
    
                        .footer a:hover {
                            text-decoration: underline;
                        }

                        .button-word {
                            color: #f4f4f4;
                            padding: 0px 20px;
                        }
                    </style>
                </head>
                <body>
                    <div class="email-container">
                        <!-- Header -->
                        <div class="header">
                            <img class="header-image" src="cid:logo" alt="CIM Logo">
                            <h1>CIM - Capacitaciones</h1>
                        </div>
    
                        <!-- Content -->
                        <div class="content">
                            <h2>Hola,' . $currentFullName . '!</h2>
                            <p>Gracias por inscribirte en nuestra videollamada de capacitación CIM.</p>
                            <p>Estamos emocionados de tenerte con nosotros y compartir contigo la guía básica de seguridad y protocolo de robo.</p>
                            <p>Por favor, asegúrate de tener una conexión estable a internet y los requisitos técnicos necesarios para participar. Si tienes alguna pregunta o necesitas asistencia, no dudes en contactarnos en <a href="mailto:cim@tecnomotum.com">CIM</a>.</p>
                            <p>Fecha: <b>' . $currentDate . '</b></p>
                            <p>Hora: <b>12:00pm</b></p>
                            <a href="'. (strval($selectedDay) == "2" ? $meetTuesday : $meetThursday) .'" class="button" target="_blank" rel="noopener noreferrer"><p class="button-word">Entrar</p></a>
                        </div>
    
                        <!-- Footer -->
                        <div class="footer">
                            <p><!-- &copy; --> 2025 - Tecnomotum</p>
                        </div>
                    </div>
                </body>
                </html>';
            $mail->AltBody = 'Capacitación con CIM';
            // $mail->addAttachment("/home/user/Escritorio/imagendeejemplo.png", " imagendeejemplo.png");
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->send();
            echo json_encode(["OK" => 1]);
            return;
        } catch (Exception $e) {
            echo json_encode(["OK" => 0]);
            return;
        }
    }
    $selectedDate = $data['date'] ?? null;
    $selectedDay = $data['day'] ?? null;
    $stmt = $conn->query("SELECT * FROM training WHERE date BETWEEN '{$selectedDate} 00:00:00' AND '{$selectedDate} 23:59:59';");
    $fetchedDates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $trainingId = null;
    if (count($fetchedDates) === 0) {
        $stmt = $conn->prepare("INSERT INTO training (date, link) VALUES (?, ?)");
        $stmt->execute([$selectedDate, $selectedDay == "2" ? $meetTuesday : $meetThursday]);
        $trainingId = $conn->lastInsertId();
    } else {
        $trainingId = $fetchedDates[0]['id'];
    }
    $stmt = $conn->prepare("INSERT INTO participants (email, fullname, phone_number, whatsapp, customer, platform) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$data['email'], $data['fullname'], $data['phone_number'], $data['whatsapp'], $data['customer'], $data['platform']]);
    $participantId = $conn->lastInsertId();
    $stmt = $conn->prepare("INSERT INTO enrollments (id_training, id_participant) VALUES (?, ?)");
    $stmt->execute([$trainingId, $participantId]);
    echo json_encode(["p" => $participantId, "t" => $trainingId]);
    try {
        $imagePath = $data['platform'] === "TELEMATICS" ? "./assets/motum.png" : "./assets/EFL.png";
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = "smtp.gmail.com";
        $mail->Port = 465;
        $mail->Username = "oscararias@tecnomotum.com";
        $mail->Password = "fgldbsgcpqyfjqff";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

        $mail->setFrom('oscararias@tecnomotum.com', 'Oscar Arias');
        $mail->addAddress("{$data['email']}");

        $mail->isHTML(true);
        $mail->Subject = ('Capacitación con CIM - ' . $selectedDate);
        $mail->AddEmbeddedImage($imagePath, 'logo');
        $mail->Body = '
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>CIM Capacitación</title>
                <style>
                    /* Reset styles */
                    body, html {
                        margin: 0;
                        padding: 0;
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        background-color: #f4f4f4;
                    }

                    /* Container */
                    .email-container {
                        max-width: 600px;
                        margin: 0 auto;
                        background-color: #ffffff;
                        border: 1px solid #dddddd;
                    }

                    /* Header */
                    .header {
                        background-color: #0073e6;
                        color: #ffffff;
                        padding: 20px;
                        text-align: center;
                    }

                    .header h1 {
                        margin: 0;
                        font-size: 24px;
                    }

                    .header-image {
                        max-width: 20%; /* Adjust this value to make the image smaller or larger */
                        height: auto; /* Maintains aspect ratio */
                        margin-bottom: 10px;
                        padding: 5px;
                        border-radius: 8px; /* Optional: Rounded corners */
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Optional: Adds a subtle shadow */
                    }

                    /* Content */
                    .content {
                        padding: 20px;
                        color: #333333;
                    }

                    .content h2 {
                        font-size: 20px;
                        margin-top: 0;
                    }

                    .content p {
                        font-size: 16px;
                    }

                    .content a {
                        text-decoration: none;
                    }

                    .content a:hover {
                        text-decoration: underline;
                    }

                    /* Button */
                    .button {
                        display: inline-block;
                        color: #ffffff; /* Color del texto en blanco para mejor contraste */
                        background-color: #0073e6; /* Color de fondo azul */
                        
                        text-decoration: none;
                        font-size: 16px;
                        
                        border-radius: 5px;
                    }

                    .button:hover {
                        background-color: #005bb5;
                    }

                    /* Footer */
                    .footer {
                        background-color: #f4f4f4;
                        padding: 10px;
                        text-align: center;
                        font-size: 14px;
                        color: #777777;
                    }

                    .footer a {
                        color: #0073e6;
                        text-decoration: none;
                    }

                    .footer a:hover {
                        text-decoration: underline;
                    }

                    .button-word {
                        color: #f4f4f4;
                        padding: 0px 20px;
                    }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <!-- Header -->
                    <div class="header">
                        <img class="header-image" src="cid:logo" alt="CIM Logo">
                        <h1>CIM - Capacitaciones</h1>
                    </div>

                    <!-- Content -->
                    <div class="content">
                        <h2>Hola,' . $data['fullname'] . '!</h2>
                        <p>Gracias por inscribirte en nuestra videollamada de capacitación CIM.</p>
                        <p>Estamos emocionados de tenerte con nosotros y compartir contigo la guía básica de seguridad y protocolo de robo.</p>
                        <p>Por favor, asegúrate de tener una conexión estable a internet y los requisitos técnicos necesarios para participar. Si tienes alguna pregunta o necesitas asistencia, no dudes en contactarnos en <a href="mailto:cim@tecnomotum.com">CIM</a>.</p>
                        <p>Fecha: <b>' . $selectedDate . '</b></p>
                        <p>Hora: <b>12:00pm</b></p>
                        <a href="'. (strval($selectedDay) === "2" ? $meetTuesday : $meetThursday) .'" class="button" target="_blank" rel="noopener noreferrer"><p class="button-word">Entrar</p></a>
                    </div>

                    <!-- Footer -->
                    <div class="footer">
                        <p><!-- &copy; --> 2025 - Tecnomotum</p>
                    </div>
                </div>
            </body>
            </html>';
        $mail->AltBody = 'Capacitación con CIM';
        // $mail->addAttachment("/home/user/Escritorio/imagendeejemplo.png", " imagendeejemplo.png");
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->send();
        // echo 'Message has been sent';
    } catch (Exception $e) {
        // echo "Mailer Error: ".$e->getMessage();
    }
    $hasWhatsapp = $data['whatsapp'] ?? 0;
    if ($hasWhatsapp === 1 || $hasWhatsapp === "1") {
        try {
            $waMessage = "Hola, " . $data['fullname'] . "\nGracias por inscribirte en nuestra videollamada de capacitación CIM.\nEstamos emocionados de tenerte con nosotros y compartir contigo la guía básica de seguridad y protocolo de robo.\nPor favor, asegúrate de tener una conexión estable a internet y los requisitos técnicos necesarios para participar. Si tienes alguna pregunta o necesitas asistencia, no dudes en contactarnos en cim@tecnomotum.com.\nFecha: " . $selectedDate . "\nHora: 12:00pm\nLink: ". (strval($selectedDay) === "2" ? $meetTuesday : $meetThursday);
            $waBody = [
                "message" => $waMessage,
                "number" => $data['phone_number'], // ADD NUMBER
                "type" => "person"
            ];
            $cj = curl_init();
            curl_setopt($cj, CURLOPT_URL, "https://secure.tecnomotum.com/apis/wsp/send");
            curl_setopt($cj, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cj, CURLOPT_HEADER, false);
            curl_setopt($cj, CURLOPT_POST, true);
            curl_setopt($cj, CURLOPT_POSTFIELDS, json_encode($waBody));
            curl_setopt($cj, CURLOPT_HTTPHEADER, [
                "accept: application/json;charset=UTF-8",
                "Content-Type: application/json;charset=UTF-8"
            ]);
            curl_exec($cj);
            curl_close($cj);
        } catch (Exception $e) {
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $trainingId = $data["training_id"];
    $participantId = $data["participant_id"];
    $stmt = $conn->prepare("
        UPDATE enrollments 
        SET attended = 1 
        WHERE id_participant = ? AND id_training = ?
    ");

    $stmt->execute([$participantId, $trainingId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["message" => "Attendance updated successfully"]);
    } else {
        echo json_encode(["error" => "No matching enrollment found"]);
    }
}
