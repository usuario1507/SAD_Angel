<?php
$log_file = '/var/log/user_access.log';
    if (!file_exists($log_file)) {
    echo "No hay registros disponibles.";
    exit;
}
$log_entries = file($log_file, FILE_IGNORE_NEW_LINES);
echo "<table border='1'>";
echo
"<tr><th>Fecha/Hora</th><th>Usuario</th><th>Correo</th><th>IP</th></tr>";
foreach ($log_entries as $entry) {
    list($timestamp, $user, $email, $ip) = explode(", ", $entry);
    echo
"<tr><td>$timestamp</td><td>$user</td><td>$email</td><td>$ip</td></tr>";}
echo "</table>";
?>