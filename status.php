<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Almaty'); // Қазақстан/Алматы уақыты

try {
    $db = new PDO('sqlite:barbershop.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $today = date('Y-m-d');
    // Дәл қазіргі сағатты "09:00", "14:00" форматына айналдыру
    $currentHourSlot = date('H') . ':00';

    // Бүгін және дәл осы сағатта броні бар барберлердің ID-лерін алу
    $stmt = $db->prepare("SELECT barber_id FROM appointments WHERE appointment_date = :date AND appointment_time = :time");
    $stmt->execute([
        ':date' => $today,
        ':time' => $currentHourSlot
    ]);
    
    // Бос емес (Busy) барберлер тізімі
    $busyBarbers = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    echo json_encode([
        "status" => "success",
        "busy_barbers" => $busyBarbers,
        "current_slot" => $currentHourSlot
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>