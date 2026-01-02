<?php
session_start();
set_time_limit(0); // Remove execution time limit
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Proxy Checker Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f4f4f4;
        }
        h2 { color: #333; }
        form {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        textarea, input[type="file"], select {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px 0;
        }
        input[type="submit"] {
            padding: 10px 20px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover { background: #0056b3; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        th { background-color: #007BFF; color: white; }
        .live { color: green; font-weight: bold; }
        .dead { color: red; font-weight: bold; }
        #loader {
            display: none;
            text-align: center;
            margin: 20px;
        }
        .spinner {
            border: 6px solid #f3f3f3;
            border-top: 6px solid #007BFF;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<h2>🔍 Proxy Checker (Multi-threaded)</h2>

<form method="post" enctype="multipart/form-data">
    <label>Enter Proxies (one per line):</label>
    <textarea name="proxies" rows="10" placeholder="123.123.123.123:8080"></textarea>

    <label>Or Upload .txt File:</label>
    <input type="file" name="proxy_file" accept=".txt">

    <label>Proxy Type:</label>
    <select name="proxy_type">
        <option value="auto">Auto Detect (HTTP/SOCKS5)</option>
        <option value="http">HTTP</option>
        <option value="socks5">SOCKS5</option>
    </select>

    <input type="submit" name="check" value="Check Proxies">
</form>

<div id="loader"><div class="spinner"></div> Checking proxies...</div>

<?php
function checkProxiesMulti($proxies, $type = 'auto', $timeout = 5) {
    $mh = curl_multi_init();
    $curlHandles = [];
    $results = [];

    foreach ($proxies as $i => $proxy) {
        $types = ($type === 'auto') ? ['http', 'socks5'] : [$type];
        $url = "https://www.google.com";

        foreach ($types as $ptype) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
            curl_setopt($ch, CURLOPT_PROXYTYPE, $ptype === 'socks5' ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $curlHandles[$proxy] = $ch;
            curl_multi_add_handle($mh, $ch);

            $results[$proxy] = ['type' => $ptype, 'status' => 'checking'];
            break;
        }
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    foreach ($curlHandles as $proxy => $ch) {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $success = curl_errno($ch) === 0 && $http_code === 200;
        $results[$proxy]['status'] = $success ? 'live' : 'dead';
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);
    return $results;
}

if (isset($_POST['check'])) {
    echo "<script>document.getElementById('loader').style.display = 'block';</script>";
    ob_flush(); flush();

    $proxies = [];

    if (!empty($_POST['proxies'])) {
        $lines = explode("\n", trim($_POST['proxies']));
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) $proxies[] = $line;
        }
    }

    if (isset($_FILES['proxy_file']) && $_FILES['proxy_file']['error'] === 0) {
        $file_data = file_get_contents($_FILES['proxy_file']['tmp_name']);
        $lines = explode("\n", trim($file_data));
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) $proxies[] = $line;
        }
    }

    $proxy_type = $_POST['proxy_type'] ?? 'http';

    echo "<h3>✅ Results:</h3><table><tr><th>#</th><th>Proxy</th><th>Type</th><th>Status</th><th>Checked At</th></tr>";

    $results = checkProxiesMulti($proxies, $proxy_type, 5);
    $liveProxies = [];
    $total = count($results);
    $i = 1;

    foreach ($results as $proxy => $info) {
        $status = $info['status'];
        $type = $info['type'];
        $time = date("Y-m-d H:i:s");
        $color = $status === 'live' ? 'live' : 'dead';

        echo "<tr>
            <td>$i / $total</td>
            <td>$proxy</td>
            <td>$type</td>
            <td class='$color'>" . strtoupper($status) . "</td>
            <td>$time</td>
        </tr>";

        if ($status === 'live') {
            $liveProxies[] = $proxy;
        }
        $i++;
        flush(); ob_flush();
    }

    echo "</table>";
    echo "<script>document.getElementById('loader').style.display = 'none';</script>";

    if (!empty($liveProxies)) {
        file_put_contents("live_proxies.txt", implode("\n", $liveProxies));
        echo "<p><strong>✅ Live proxies saved to:</strong> <a href='live_proxies.txt' download>live_proxies.txt</a></p>";
    } else {
        echo "<p><strong>❌ No live proxies found.</strong></p>";
    }
}
?>

<footer style="text-align: center; margin-top: 40px; padding: 20px; color: #666; font-size: 14px;">
    Developed by <strong>Kaushalsah606 </strong>
</footer>

</body>
</html>
