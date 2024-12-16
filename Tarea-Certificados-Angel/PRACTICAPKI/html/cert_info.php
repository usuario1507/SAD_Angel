<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Certificado</title>
    <link rel="stylesheet" href="./asserts/styles.css">

<?php
  // Función para convertir fechas al formato legible
  function formatDate($dateString) {
    return date("d/m/Y H:i:s", strtotime(substr($dateString, 0, 14)));
}

function formatStringDate($dateString) {
    // Validar el formato Zulu (12 dígitos seguidos de 'Z')
    if (preg_match('/^\d{12}Z$/', $dateString)) {
        // Extraer los componentes de la fecha
        $year = substr($dateString, 0, 2); // Año (dos dígitos)
        $month = substr($dateString, 2, 2); // Mes
        $day = substr($dateString, 4, 2); // Día
        $hour = substr($dateString, 6, 2); // Hora
        $minute = substr($dateString, 8, 2); // Minuto
        $second = substr($dateString, 10, 2); // Segundo

        // Convertir el año de dos dígitos a cuatro dígitos (asumiendo 2000-2099)
        $fullYear = ($year >= "50" ? "19" : "20") . $year;

        // Crear la fecha en el formato deseado
        return "$day-$month-$fullYear $hour:$minute:$second";
    } else {
        // Si el formato es incorrecto, devolver un mensaje de error
        return $dateString;
    }
}


function renderTable($data, $title = "Datos") {
    echo "<div class='section'>";
    echo "<h2>$title</h2>";
    echo "<table>";
    echo "<thead><tr><th>Clave</th><th>Valor</th></tr></thead>";
    echo "<tbody>";
    foreach ($data as $key => $value) {
        echo "<tr>";
        echo "<td>$key</td>";
        if ($key === "validFrom" || $key === "validTo") {
            echo "<td>" . formatStringDate($value) . " (Original: $value)</td>";
        } elseif ($key === "validFrom_time_t" || $key === "validTo_time_t") {
            echo "<td>" . date("d/m/Y H:i:s", $value) . " (Timestamp: $value)</td>";
        } elseif (is_array($value)) {
            echo "<td class='highlight'>";
            renderTable($value, ""); // Llamada recursiva para subtablas
            echo "</td>";
        } else {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
}


// Verificar si hay información del certificado de cliente
if (!isset($_SERVER['SSL_CLIENT_CERT']) || empty($_SERVER['SSL_CLIENT_CERT'])) {
    echo "<h1>Error</h1><p>No se ha proporcionado un certificado válido.</p>";
    exit;
}

// Recuperar el certificado y corregir los saltos de línea
$cert = str_replace("\t", "", $_SERVER['SSL_CLIENT_CERT']);


// Intentar analizar el certificado corregido
$certData = openssl_x509_parse($cert);

if ($certData === false) {
    echo "<h1>Error</h1><p>El certificado no pudo ser procesado después de corregir el formato.</p>";
    exit;
}


// Mostrar las secciones principales
renderTable($certData, "Información General del Certificado");
if (isset($certData['subject'])) {
    renderTable($certData['subject'], "Información del Propietario (Subject)");
}
if (isset($certData['issuer'])) {
    renderTable($certData['issuer'], "Emisor del Certificado (Issuer)");
}
if (isset($certData['extensions'])) {
    renderTable($certData['extensions'], "Extensiones");
}


// Mostrar información del certificado
echo "<h1>Datos del Certificado del Cliente</h1>";
echo "<table border='1'>";
echo "<tr><th>Propiedad</th><th>Valor</th></tr>";
foreach ($certData as $key => $value) {
    if (is_array($value)) {
        $value = implode(", ", $value);
    }
    echo "<tr><td>$key</td><td>$value</td></tr>";
}
echo "</table>";

?>

<?php
  $user = $certData['subject']['CN']; // Nombre del cliente
  $email = $certData['subject']['emailAddress']; // Correo del cliente
  $ip = $_SERVER['REMOTE_ADDR']; // IP del cliente
  $timestamp = date("Y-m-d H:i:s"); // Fecha y hora actuales
  $log_entry = "$timestamp, $user, $email, $ip\n";
  file_put_contents('/var/log/user_access.log', $log_entry, FILE_APPEND);
?>

</body>
</html>
