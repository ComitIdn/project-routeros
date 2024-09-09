<?php
require 'mikrotik.php';

$hotspotUsers = $routerOS->getHotspotUsers();
$pppoeUsers = $routerOS->getPppoeUsers();
$internetTraffic = $routerOS->getInternetTraffic();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Jaringan MikroTik</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <header>
        <h1>Monitoring Jaringan MikroTik</h1>
    </header>
    <main>
        <section>
            <h2>Jumlah Pengguna Hotspot Aktif</h2>
            <p id="active-hotspot-users"><?= count($hotspotUsers) ?></p>
        </section>
        <section>
            <h2>Jumlah Pengguna PPPoE Aktif</h2>
            <p id="active-pppoe-users"><?= count($pppoeUsers) ?></p>
        </section>
        <section>
            <h2>Monitoring Trafik Internet</h2>
            <p id="internet-traffic"><?= json_encode($internetTraffic) ?></p>
        </section>
    </main>
    <script src="script.js"></script>
</body>

</html>