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
$city_input = isset($_GET['city']) && trim($_GET['city']) !== '' ? $_GET['city'] : 'cesis,latvia';
$data = fetch_api($city_input);
$city = $data['city'] ?? ['name' => $city_input, 'country' => ''];
$list = $data['list'] ?? [];
$current = $list[0] ?? ($data['current'] ?? []);
$tz = $city['timezone'] ?? 0;
$cur_temp = $current['temp']['day'] ?? $current['temp'] ?? ($current['main']['temp'] ?? null);
$cur_desc = $current['weather'][0]['description'] ?? ($current['description'] ?? '');
$cur_icon = $current['weather'][0]['icon'] ?? '';
$cur_feels = $current['feels_like']['day'] ?? ($current['main']['feels_like'] ?? null);
$cur_wind = $current['speed'] ?? ($current['wind']['speed'] ?? null);
$cur_humidity = $current['humidity'] ?? ($current['main']['humidity'] ?? null);
$cur_pressure = $current['pressure'] ?? ($current['main']['pressure'] ?? null);
$visibility = $current['visibility'] ?? null;
$aqi = $data['air_quality'] ?? ($data['aqi'] ?? null);
function icon_url($icon)
{
    return $icon ? "https://openweathermap.org/img/wn/{$icon}@2x.png" : '';
}
$wind_kmh = is_null($cur_wind) ? null : round($cur_wind * 3.6, 1);
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

            <!-- theme toggle -->
            <button id="theme-toggle" class="theme-btn" type="button" aria-pressed="false"
                title="Toggle dark / light">🌙</button>
        </header>

        <section class="current-card card">
            <div class="current-left">
                <?php if($cur_icon): ?>
                <img src="<?php echo h(icon_url($cur_icon)); ?>" alt="" class="weather-icon">
                <?php else: ?>
                <div class="weather-icon placeholder">☁️</div>
                <?php endif; ?>
                <div class="temp-block">
                    <div class="label">Pašreiz</div>
                    <div class="temp"><?php echo is_null($cur_temp) ? '—' : round($cur_temp,1) . "°C"; ?></div>
                    <div class="small-desc"><?php echo h(ucfirst($cur_desc)); ?></div>
                    <div class="feels">Jūtas kā <?php echo is_null($cur_feels) ? '—' : round($cur_feels,1) . "°C"; ?>
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
                <div class="stat-value"><?php echo is_null($wind_kmh) ? '—' : h($wind_kmh) . ' km/h'; ?></div>
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

            <div class="stat-card card">
                <div class="stat-icon">🔁</div>
                <div class="stat-title">Spiediens (alt)</div>
                <div class="stat-value"><?php echo is_null($cur_pressure) ? '—' : h($cur_pressure); ?></div>
                <div class="stat-sub">Avots: API</div>
            </div>
        </section>

        <section class="sunmoon card">
            <h2>Saules stāvoklis</h2>

            <div class="sunmoon-grid">


                <div class="sun-row">
                    <?php
                        $sunrise = $data['city']['sunrise'] ?? $data['sun']['sunrise'] ?? $current['sunrise'] ?? null;
                        $sunset  = $data['city']['sunset'] ?? $data['sun']['sunset'] ?? $current['sunset'] ?? null;
                    ?>
                    <div class="sun-item">
                        <div class="sm-ico">🌅</div>
                        <div class="sm-title">Saullēkts</div>
                        <div class="sm-time"><?php echo $sunrise ? date('h:i A', $sunrise + $tz) : '—'; ?></div>
                        <div class="sun-arc"><span class="arc-fill" style="width:40%"></span></div>
                    </div>

                    <div class="sun-item">
                        <div class="sm-ico">🌇</div>
                        <div class="sm-title">Saulriets</div>
                        <div class="sm-time"><?php echo $sunset ? date('h:i A', $sunset + $tz) : '—'; ?></div>
                    </div>
                </div>

                <div class="moon-row" style="display:none"></div>
            </div>
        </section>

        <section class="forecast card">
            <h2>Prognoze</h2>
            <div class="forecast-grid">
                <?php if (!empty($list)): foreach ($list as $day):
                    $dt = $day['dt'] ?? $day['date'] ?? null;
                    $dateStr = $dt ? date('d.m.', $dt + $tz) : ($day['date'] ?? '—');
                    $tmin = $day['temp']['min'] ?? $day['main']['temp_min'] ?? null;
                    $tmax = $day['temp']['max'] ?? $day['main']['temp_max'] ?? null;
                    $desc = $day['weather'][0]['description'] ?? $day['description'] ?? '';
                    $icon = $day['weather'][0]['icon'] ?? '';
                ?>
                <div class="day-card">
                    <div class="day-date"><?php echo h($dateStr); ?></div>
                    <?php if ($icon): ?><img src="<?php echo h(icon_url($icon)); ?>" alt=""
                        class="day-icon"><?php endif; ?>
                    <div class="day-temps"><?php echo is_null($tmax)?'—':round($tmax).'°'; ?> /
                        <?php echo is_null($tmin)?'—':round($tmin).'°'; ?></div>
                    <div class="day-desc"><?php echo h(ucfirst($desc)); ?></div>
                </div>
                <?php endforeach; else: ?>
                <p>Prognozes dati nav pieejami.</p>
                <?php endif; ?>
            </div>
        </section>

        <footer class="footer">VTDT Sky © <?php echo date('Y'); ?> — Dati no emo.lv</footer>
    </main>

    <script>
    (function() {
        const KEY = 'vtdt-theme';
        const btn = document.getElementById('theme-toggle');
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const saved = localStorage.getItem(KEY);
        const initial = saved || (prefersDark ? 'dark' : 'light');

        function applyTheme(t) {
            if (t === 'dark') document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
            if (btn) {
                btn.textContent = t === 'dark' ? '☀️' : '🌙';
                btn.setAttribute('aria-pressed', t === 'dark');
            }
        }

        applyTheme(initial);

        if (btn) {
            btn.addEventListener('click', function() {
                const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
                localStorage.setItem(KEY, next);
                applyTheme(next);
            });
        }
    })();
    </script>
</body>

</html>