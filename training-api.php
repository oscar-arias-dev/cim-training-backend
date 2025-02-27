<?php
include 'db.php';
header('Content-Type: application/json');
$meetTuesday = 'https://meet.google.com/fyk-pjyw-fro';
$meetThursday = 'https://meet.google.com/udb-ycho-qym';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = json_decode(file_get_contents('php://input'), true);
    $api = $data['api'] ?? null;
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
                    p.whatsapp
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
    echo json_encode(["error" => "Endpoint not found"]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $selectedDate = $data['date'] ?? null;
    $selectedDay = $data['day'] ?? null;
    $stmt = $conn->query("SELECT * FROM training WHERE date BETWEEN '{$selectedDate} 00:00:00' AND '{$selectedDate} 23:59:59';");
    $fetchedDates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $trainingId = null;
    if (count($fetchedDates) === 0) {
        $stmt = $conn->prepare("INSERT INTO training (date, link) VALUES (?, ?)");
        $stmt->execute([$selectedDate, $selectedDay == 2 ? $meetTuesday : $meetThursday]);
        $trainingId = $conn->lastInsertId();
    } else {
        $trainingId = $fetchedDates[0]['id'];
    }
    $stmt = $conn->prepare("INSERT INTO participants (email, fullname, phone_number, whatsapp, customer) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data['email'], $data['fullname'], $data['phone_number'], $data['whatsapp'], $data['customer']]);
    $participantId = $conn->lastInsertId();
    $stmt = $conn->prepare("INSERT INTO enrollments (id_training, id_participant) VALUES (?, ?)");
    $stmt->execute([$trainingId, $participantId]);
    echo json_encode(["p" => $participantId, "t" => $trainingId]);
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
