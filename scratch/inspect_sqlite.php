<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
$r = $pdo->query('SELECT count(*) FROM rooms')->fetchColumn();
$p1 = $pdo->query("SELECT count(*) FROM schedules WHERE cycle_type = 'Putaran 1'")->fetchColumn();
$p2 = $pdo->query("SELECT count(*) FROM schedules WHERE cycle_type = 'Putaran 2'")->fetchColumn();
$s = $pdo->query("SELECT subject, start_time, end_time, day_of_week FROM schedules WHERE cycle_type = 'Putaran 1' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "Rooms: $r\nPutaran 1 Schedules: $p1\nPutaran 2 Schedules: $p2\nSample:\n";
print_r($s);
