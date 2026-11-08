<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

try {
    $db = new PDO('sqlite:barbershop.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec("CREATE TABLE IF NOT EXISTS appointments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_id TEXT,
        email TEXT,
        phone TEXT,
        barber_id INTEGER,
        service TEXT,
        appointment_date TEXT,
        appointment_time TEXT
    )");

    $cols = $db->query("PRAGMA table_info(appointments)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('email', $cols)) { $db->exec("ALTER TABLE appointments ADD COLUMN email TEXT"); }
    if (!in_array('service', $cols)) { $db->exec("ALTER TABLE appointments ADD COLUMN service TEXT"); }

    $data = json_decode(file_get_contents("php://input"), true);
    
    // Барберлердің жұлдызшасы мен клиенттер санын алу
    if (isset($data['action']) && $data['action'] === 'get_ratings') {
        $query = $db->query("SELECT barber_id, COUNT(*) as count FROM appointments GROUP BY barber_id");
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        
        $ratings = ["1" => 0, "2" => 0, "3" => 0, "4" => 0];
        foreach ($results as $row) {
            $ratings[$row['barber_id']] = (int)$row['count'];
        }
        
        echo json_encode(["status" => "success", "ratings" => $ratings]);
        exit;
    }

    // --- ЖАҢА ФУНКЦИЯ: БОС ЕМЕС УАҚЫТТАРДЫ ҚАЙТАРУ ---
    if (isset($data['action']) && $data['action'] === 'get_booked_times') {
        $stmt = $db->prepare("SELECT appointment_time FROM appointments WHERE barber_id = :barber AND appointment_date = :date");
        $stmt->execute([':barber' => $data['barberId'], ':date' => $data['date']]);
        
        // Бос емес уақыттар тізімін аламыз (мысалы: ["10:00", "14:00"])
        $bookedTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(["status" => "success", "booked_times" => $bookedTimes]);
        exit;
    }

    if ($data) {
        if (isset($data['action']) && $data['action'] === 'cancel_booking') {
            $email = $data['email'] ?? '';
            if (!empty($email)) {
                $stmt = $db->prepare("DELETE FROM appointments WHERE email = :email");
                $stmt->execute([':email' => $email]);
                echo json_encode(["status" => "success", "message" => "Бронь сәтті жойылды!"]);
                exit; 
            } else {
                echo json_encode(["status" => "error", "message" => "Email табылмады"]);
                exit;
            }
        }

        $checkSql = "SELECT COUNT(*) FROM appointments WHERE barber_id = :barber AND appointment_date = :date AND appointment_time = :time";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([':barber' => $data['barberId'], ':date' => $data['date'], ':time' => $data['time']]);
        
        $isBooked = $checkStmt->fetchColumn();

        if ($isBooked > 0) {
            echo json_encode(["status" => "error", "message" => "Кешіріңіз, бұл уақытқа басқа адам жазылып қойған. Басқа уақыт таңдаңыз!"]);
            exit;
        } else {
            $sql = "INSERT INTO appointments (customer_id, email, phone, barber_id, service, appointment_date, appointment_time) 
                    VALUES (:name, :email, :phone, :barber, :service, :date, :time)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'], ':email' => $data['email'] ?? '', ':phone' => $data['phone'],
                ':barber' => $data['barberId'], ':service' => $data['service'] ?? '',
                ':date' => $data['date'], ':time' => $data['time']
            ]);
            echo json_encode(["status" => "success", "message" => "Бронь сәтті сақталды!"]);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Деректер қабылданбады"]);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Дерекқор қатесі: " . $e->getMessage()]);
    exit;
}
?>