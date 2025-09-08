<?php

$data = file_get_contents("https://emo.lv/weather-api/forecast/?city=cesis,latvia");
$weatherData = json_decode($data, true);

$temperature = $weatherData['list'][0]['main']['temp'] ?? 'N/A';
$weatherDesc = $weatherData['list'][0]['weather'][0]['description'] ?? 'N/A';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VTDT Sky</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <nav class="navbar">
        <p>VTDT Sky</p>
        <p><?php echo $weatherData['city']['name']; ?></p>
        <p><?php echo $weatherData['city']['country']; ?></p>

    </nav>
</body>

</html>