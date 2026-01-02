<?php
session_start();

// Handle form submission for scraping
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start_scraping') {
    $proxyType = $_POST['type'];
    $limit = (int)$_POST['limit'];

    // Expanded proxy sources
    $sources = [
        'https://www.proxy-list.download/api/v1/get?type=' . strtolower($proxyType),
        'https://api.proxyscrape.com/v2/?request=getproxies&protocol=' . strtolower($proxyType) . '&country=all&ssl=all&anonymity=all',
        'https://raw.githubusercontent.com/TheSpeedX/PROXY-List/master/' . strtoupper($proxyType) . '.txt',
        'https://raw.githubusercontent.com/monosans/proxy-list/main/proxies/' . strtolower($proxyType) . '.txt',
        'https://raw.githubusercontent.com/mertguvencli/http-proxy-list/main/proxy-list/data.txt',
        'https://raw.githubusercontent.com/roosterkid/openproxylist/main/' . strtolower($proxyType) . '.txt',
        'https://raw.githubusercontent.com/ShiftyTR/Proxy-List/master/' . strtolower($proxyType) . '.txt',
        'https://raw.githubusercontent.com/hookzof/socks5_list/master/proxy.txt',
        'https://raw.githubusercontent.com/jetkai/proxy-list/main/online-proxies/txt/proxies-' . strtolower($proxyType) . '.txt',
        'https://raw.githubusercontent.com/clarketm/proxy-list/master/proxy-list-raw.txt',
        'https://raw.githubusercontent.com/mmpx12/proxy-list/master/http.txt',
        'https://raw.githubusercontent.com/sunny9577/proxy-scraper/master/proxies.txt',
        'https://www.sslproxies.org/',
        'https://www.us-proxy.org/',
        'https://www.freeproxylists.net/',
        'https://www.socks-proxy.net/',
        'https://www.proxynova.com/proxy-server-list/',
        'https://hidemyass.com/proxy-list/',
        'https://www.proxy-listen.de/Proxy/Proxyliste.html',
    ];

    $proxies = [];
    foreach ($sources as $url) {
        $data = @file_get_contents($url);
        if ($data) {
            preg_match_all('/(\d{1,3}\.){3}\d{1,3}:\d+/', $data, $matches);
            if (!empty($matches[0])) {
                $proxies = array_merge($proxies, $matches[0]);
            }
        }
    }

    $proxies = array_unique($proxies);
    shuffle($proxies);

    if ($limit > 0) {
        $proxies = array_slice($proxies, 0, $limit);
    }

    file_put_contents("proxies.txt", implode("\n", $proxies)); 

    echo "✅ <strong>Scraping complete.</strong><br>Total proxies scraped: " . count($proxies) . "<br><a href='proxies.txt' download>Download proxies.txt</a>";
    exit;
}

// Handle stop scraping action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'stop_scraping') {
    $_SESSION['scraping'] = false;
    echo '🛑 Scraping stopped.';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Proxy Scraper Tool</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f2f2f2; }
    .container { max-width: 600px; background: white; padding: 20px; border-radius: 10px; margin: auto; }
    input, select { width: 100%; padding: 10px; margin: 10px 0; }
    button { padding: 10px 20px; background: green; color: white; border: none; border-radius: 5px; cursor: pointer; }
    button:hover { background: darkgreen; }
    .download-btn {
      padding: 10px 20px;
      background: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-weight: bold;
    }
    .download-btn:hover {
      background: #0056b3;
    }
    .stop-btn {
      padding: 10px 20px;
      background: red;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      display: none;
    }
    .stop-btn:hover {
      background: darkred;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>🕵️ Proxy Scraper</h2>
    <form id="scraperForm">
      <label>Proxy Type</label>
      <select name="type" required>
        <option value="ALL">All</option>
        <option value="HTTP">HTTP</option>
        <option value="HTTPS">HTTPS</option>
        <option value="SOCKS4">SOCKS4</option>
        <option value="SOCKS5">SOCKS5</option>
      </select>
      <label>How many proxies?</label>
      <input type="number" name="limit" value="50" required min="1">
      <button type="submit">🚀 Start Scraping</button>
      <button type="button" id="stopButton" class="stop-btn">🛑 Stop Scraping</button>
    </form>
    <div id="result" style="margin-top:20px;"></div>
    <div style="margin-top: 10px;">
      <a href="proxies.txt" download class="download-btn" id="downloadBtn" style="display:none;">⬇️ Download proxies.txt</a>
    </div>
  </div>

  <script>
    const form = document.getElementById('scraperForm');
    const stopButton = document.getElementById('stopButton');
    const result = document.getElementById('result');
    const downloadBtn = document.getElementById('downloadBtn');

    form.addEventListener('submit', function(e) {
      e.preventDefault();
      result.innerHTML = '⏳ Scraping... Please wait...';
      stopButton.style.display = 'inline-block';

      const formData = new FormData(form);
      formData.append('action', 'start_scraping');

      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(res => res.text())
      .then(data => {
        result.innerHTML = data;
        if (data.includes('proxies.txt')) {
          downloadBtn.style.display = 'inline-block';
        }
      });
    });

    stopButton.addEventListener('click', function() {
      const formData = new FormData();
      formData.append('action', 'stop_scraping');

      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(res => res.text())
      .then(data => {
        result.innerHTML = data;
        stopButton.style.display = 'none';
      });
    });
  </script>

  <footer style="text-align: center; margin-top: 40px; padding: 20px; color: #666; font-size: 14px;">
    Developed by <strong>Kaushalsah606</strong>
  </footer>
</body>
</html>
