<?php
function fetch_api($city)
{
    $url = "https://emo.lv/weather-api/forecast/?city=" . urlencode($city);
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'header' => "User-Agent: VTDT-Sky/1.0\r\n"]]);
    $json = @file_get_contents($url, false, $ctx);
    return $json ? json_decode($json, true) : null;
}
function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// helper to read nested paths like "city.sunrise" or "forecast.0.sunrise"
function getp($arr, $path, $def = null)
{
    if (!is_array($arr) || !$path) return $def;
    $parts = is_array($path) ? $path : explode('.', $path);
    foreach ($parts as $p) {
        if (is_array($arr) && array_key_exists($p, $arr)) $arr = $arr[$p];
        else return $def;
    }
    return $arr;
}
function format_time($val, $tz = 0)
{
    if ($val === null) return '—';
    if (is_numeric($val)) return date('h:i A', intval($val) + intval($tz));
    $t = strtotime((string)$val);
    return $t ? date('h:i A', $t + intval($tz)) : h($val);
}

// inicializācija un mainīgie, lai nebūtu erroru
$city_input = isset($_GET['city']) && trim($_GET['city']) !== '' ? $_GET['city'] : 'cesis,latvia';
$data = fetch_api($city_input);
$city = is_array($data) && isset($data['city']) ? $data['city'] : ['name' => $city_input, 'country' => ''];
$list = is_array($data) && isset($data['list']) ? $data['list'] : [];
$current = !empty($list) ? $list[0] : (is_array($data) && isset($data['current']) ? $data['current'] : []);
$tz = isset($city['timezone']) ? $city['timezone'] : 0;

function icon_url($icon)
{
    return $icon ? "https://openweathermap.org/img/wn/{$icon}@2x.png" : '';
}
function fmt_date_ts($ts, $tz = 0)
{
    if (!$ts) return '—';
    return date('d.m.', intval($ts) + intval($tz));
}

// droša lauku nolasīšana ar getp
$cur_temp = getp($current, 'temp.day', getp($current, 'temp', getp($current, 'main.temp', null)));
$cur_desc = getp($current, 'weather.0.description', getp($current, 'description', ''));
$cur_icon = getp($current, 'weather.0.icon', '');
$cur_feels = getp($current, 'feels_like.day', getp($current, 'main.feels_like', null));
$cur_wind = getp($current, 'speed', getp($current, 'wind.speed', null));
$cur_humidity = getp($current, 'humidity', getp($current, 'main.humidity', null));
$cur_pressure = getp($current, 'pressure', getp($current, 'main.pressure', null));
$visibility = getp($current, 'visibility', getp($data, 'visibility', null));
$aqi = getp($data, 'air_quality', getp($data, 'aqi', null));
?>
<!doctype html>
<html lang="lv">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Laikapstākļi — <?php echo h(ucfirst($city_input)); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <main class="wrap">
        <header class="topbar">
            <div class="brand">VTDT Sky</div>
            <form class="search" method="get">
                <input type="text" name="city" placeholder="Pilsēta, valsts (piem. cesis,latvia)"
                    value="<?php echo h($city_input); ?>">
                <button type="submit">Meklēt</button>
            </form>
        </header>

        <section class="current-card card">
            <div class="current-left">
                <?php if ($cur_icon): ?>
                <img src="<?php echo h(icon_url($cur_icon)); ?>" alt="" class="weather-icon">
                <?php else: ?>
                <div class="weather-icon placeholder">☁️</div>
                <?php endif; ?>
                <div class="temp-block">
                    <div class="label">Pašreiz</div>
                    <div class="temp"><?php echo is_null($cur_temp) ? '—' : round($cur_temp, 1) . "°C"; ?></div>
                    <div class="small-desc"><?php echo h(ucfirst($cur_desc)); ?></div>
                    <div class="feels">Jūtas kā <?php echo is_null($cur_feels) ? '—' : round($cur_feels, 1) . "°C"; ?>
                    </div>
                </div>
            </div>

            <div class="current-right">
                <div class="loc">
                    <div class="city"><?php echo h($city['name'] ?? $city_input); ?>,
                        <?php echo h($city['country'] ?? ''); ?></div>
                    <div class="local-time">Lokālais laiks: <?php echo date('H:i, d.m.Y'); ?></div>
                </div>
                <div class="wind-dir">Pašreizējā vēja virziens: <?php echo $cur_wind ? h('SSE') : '—'; ?></div>
            </div>
        </section>

        <section class="stats-grid">
            <div class="stat-card card">
                <div class="stat-icon">💨</div>
                <div class="stat-title">Vējš</div>
                <div class="stat-value"><?php echo is_null($cur_wind) ? '—' : h(round($cur_wind * 3.6, 1)) . ' km/h'; ?>
                </div>
                <div class="stat-sub">Avots: API</div>
            </div>

            <div class="stat-card card">
                <div class="stat-icon">💧</div>
                <div class="stat-title">Mitrums</div>
                <div class="stat-value"><?php echo is_null($cur_humidity) ? '—' : h($cur_humidity) . '%'; ?></div>
                <div class="stat-sub">Avots: API</div>
            </div>

            <div class="stat-card card">
                <div class="stat-icon">🧭</div>
                <div class="stat-title">Spiediens</div>
                <div class="stat-value"><?php echo is_null($cur_pressure) ? '—' : h($cur_pressure) . ' hPa'; ?></div>
                <div class="stat-sub">Avots: API</div>
            </div>
        </section> <!-- stats-grid -->

        <section class="sunmoon card">
            <h2>Saules kopsavilkums</h2>

            <div class="sunmoon-grid">
                <div class="sun-row">
                    <?php
                    $sunrise = getp($data, 'city.sunrise', null) ?? getp($data, 'sun.sunrise', null) ?? getp($current, 'sunrise', null);
                    $sunset  = getp($data, 'city.sunset', null)  ?? getp($data, 'sun.sunset', null)  ?? getp($current, 'sunset', null);
                    ?>
                    <div class="sun-item">
                        <div class="sm-ico">🌅</div>
                        <div class="sm-title">Saullēkts</div>
                        <div class="sm-time"><?php echo format_time($sunrise, $tz); ?></div>
                        <div class="sun-arc"><span class="arc-fill" style="width:40%"></span></div>
                    </div>

                    <div class="sun-item">
                        <div class="sm-ico">🌇</div>
                        <div class="sm-title">Saulriets</div>
                        <div class="sm-time"><?php echo format_time($sunset, $tz); ?></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="forecast card">
            <h2>Prognoze</h2>
            <div class="forecast-grid">
                <?php if (!empty($list)): foreach ($list as $day):
                        $dt = $day['dt'] ?? ($day['date'] ?? null);
                        $dateStr = $dt ? fmt_date_ts($dt, $tz) : ($day['date'] ?? '—');
                        $tmin = $day['temp']['min'] ?? $day['main']['temp_min'] ?? null;
                        $tmax = $day['temp']['max'] ?? $day['main']['temp_max'] ?? null;
                        $desc = $day['weather'][0]['description'] ?? ($day['description'] ?? '');
                        $icon = $day['weather'][0]['icon'] ?? '';
                ?>
                <div class="day-card">
                    <div class="day-date"><?php echo h($dateStr); ?></div>
                    <?php if ($icon): ?>
                    <img src="<?php echo h(icon_url($icon)); ?>" alt="" class="day-icon">
                    <?php endif; ?>
                    <div class="day-temps"><?php echo is_null($tmax) ? '—' : round($tmax) . '°'; ?> /
                        <?php echo is_null($tmin) ? '—' : round($tmin) . '°'; ?></div>
                    <div class="day-desc"><?php echo h(ucfirst($desc)); ?></div>
                </div>
                <?php endforeach;
                else: ?>
                <p>Prognozes dati nav pieejami.</p>
                <?php endif; ?>
            </div>
        </section>

        <footer class="footer">VTDT Sky © <?php echo date('Y'); ?> — Dati no emo.lv</footer>
    </main>
</body>

</html>