<?php
try {
    // Дерекқорға қосылу
    $db = new PDO('sqlite:barbershop.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Кестеде email немесе service бағандары жоқ болса, автоматты түрде қосу
    $columns = $db->query("PRAGMA table_info(appointments)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('email', $columns)) {
        $db->exec("ALTER TABLE appointments ADD COLUMN email TEXT");
    }
    if (!in_array('service', $columns)) {
        $db->exec("ALTER TABLE appointments ADD COLUMN service TEXT");
    }

    // ЕГЕР "ОТМЕНИТЬ" БАТЫРМАСЫ БАСЫЛСА, БАЗАДАН ӨШІРУ ЛОГИКАСЫ
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        $delete_stmt = $db->prepare("DELETE FROM appointments WHERE id = :id");
        $delete_stmt->execute([':id' => $delete_id]);
        
        // Өшіргеннен кейін бетті қайта жаңарту
        header("Location: admin.php");
        exit();
    }

    // Барлық қалған броньдарды алу (соңғы жазылғандар жоғары тұруы үшін)
    $result = $db->query('SELECT * FROM appointments ORDER BY id DESC');
?>
<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Клиенттер тізімі</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { 
            background: #fdf5f7; /* Сайттың жалпы ашық қызғылт фоны */
            padding: 30px; 
            color: #333; 
            position: relative; 
            min-height: 100vh; 
        }
        
        /* Домалақ стрелка батырмасының стилі */
        .circle-back-btn { 
            position: absolute; 
            top: 20px; 
            left: 20px; 
            width: 45px;
            height: 45px;
            font-size: 24px; 
            text-decoration: none; 
            color: #fff; 
            background: #111; 
            border-radius: 50%; 
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); 
            transition: all 0.3s ease; 
            z-index: 9999;
        }
        .circle-back-btn:hover { background: #ff66b2; box-shadow: 0 4px 15px rgba(255,102,178,0.5); transform: translateY(-2px); }
        
        .container { 
            max-width: 1200px; 
            margin: 40px auto 0 auto; 
            background: #fff; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(255, 102, 178, 0.15); 
        }

        .header { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #ffe3ec; 
            padding-bottom: 15px; 
        }
        
        .header h1 { 
            color: #111; 
            font-size: 28px; 
            text-align: center; 
            width: 100%; 
        }
        
        .header h1 span { color: #ff66b2; }

        .table-wrapper {
            overflow-x: auto;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            background-color: white; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
            border-radius: 12px;
            overflow: hidden;
        }
        
        th, td { 
            border-bottom: 1px solid #ffe3ec; 
            padding: 15px; 
            text-align: left; 
            font-size: 14px; 
        }
        
        th { 
            background-color: #111; 
            color: white; 
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: rgba(255, 102, 178, 0.05); }
        
        .email-style { color: #555; font-size: 13px; }
        .service-style { color: #d6336c; font-weight: bold; }
        .phone-style { font-family: monospace; font-size: 13px; color: #555; }
        .name-style { font-weight: bold; color: #111; }
        .datetime-style { font-weight: bold; color: #333; }
        
        /* Өшіру батырмасының дизайны */
        .delete-btn { 
            background-color: #fff; 
            color: #e03131; 
            border: 1px solid #e03131; 
            padding: 8px 15px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: bold;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        .delete-btn:hover { 
            background-color: #e03131; 
            color: #fff;
            box-shadow: 0 4px 10px rgba(224, 49, 49, 0.3);
        }
    </style>
</head>
<body>

    <!-- ДОМАЛАҚ СТРЕЛКА (index.html?return=admin сілтемесіне апарады) -->
    <a href="index.html?return=admin" class="circle-back-btn" title="Сайтқа қайту">⬅</a>

    <div class="container">
        <div class="header">
            <h1>Жазылған <span>Клиенттер Тізімі</span></h1>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>№ (ID)</th>
                        <th>Клиенттің аты</th>
                        <th>Google аккаунт (Email)</th>
                        <th>Телефон нөмірі</th>
                        <th>Барбер</th>
                        <th>Процедура (Бағасы)</th>
                        <th>Күні</th>
                        <th>Уақыты</th>
                        <th>Әрекет</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($result as $row) { 
                        $barberName = "Белгісіз";
                        if (isset($row['barber_id'])) {
                            if ($row['barber_id'] == 1) $barberName = "Balnur";
                            if ($row['barber_id'] == 2) $barberName = "Tamila";
                            if ($row['barber_id'] == 3) $barberName = "Indira";
                            if ($row['barber_id'] == 4) $barberName = "Ailin";
                        }
                    ?>
                    <tr>
                        <td style="color: #888;">#<?php echo htmlspecialchars($row['id'] ?? ''); ?></td>
                        <td class="name-style"><?php echo htmlspecialchars($row['customer_id'] ?? ''); ?></td>
                        <td class="email-style"><?php echo htmlspecialchars(!empty($row['email']) ? $row['email'] : '—'); ?></td>
                        <td class="phone-style"><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                        <td><?php echo $barberName; ?></td>
                        <td class="service-style"><?php echo htmlspecialchars(!empty($row['service']) ? $row['service'] : '—'); ?></td>
                        <td class="datetime-style"><?php echo htmlspecialchars($row['appointment_date'] ?? ''); ?></td>
                        <td class="datetime-style"><?php echo htmlspecialchars($row['appointment_time'] ?? ''); ?></td>
                        <td>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Бұл адамның бронін өшіресіз бе? Өшіргеннен кейін бұл уақыт қайтадан босайды.');">
                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="delete-btn">Отменить</button>
                            </form>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php
} catch (PDOException $e) {
    echo "Әзірге ешқандай дерекқор жоқ немесе қате шықты: " . $e->getMessage();
}
?>