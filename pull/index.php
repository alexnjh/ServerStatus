<?php

// ================================================================================================================
//													ServerStatus Pull
// ================================================================================================================
//
//	by: Cameron Munroe ~ Mun
// 	Website: https://www.qwdsa.com/converse/threads/serverstatus-rebuild.43/ 
// 	rewritten from the original server status script by Bluevm and @mojeda
//	Ver. 1.1b
//
//
// 	https://raw.githubusercontent.com/Munroenet/ServerStatus/master/uptime.php
//
// ================================================================================================================
//													Get Config
// ================================================================================================================

include('../includes/config.php');
$time = time();

// ================================================================================================================
//													Sanity Checks
// ================================================================================================================
if (!isset($_GET['url']) || !is_numeric($_GET['url']) || !isset($servers[$_GET['url']])) {
    exit("404 Error, that couldn't be found");
}

// ================================================================================================================
//													Core!
// ================================================================================================================
elseif (isset($servers[$_GET['url']]['break'])) {
    exit("null");
} elseif (is_numeric($_GET['url'])) {
    $id = $_GET['url']; //make nice IDs
    $data = get_cache($id, $cache, $time);
    if ($data['uptime'] == null) {
        $data = get_data($servers[$id]['url']);
        $data['time'] = $time;
        if ($no_stripe == 1 || !isset($no_stripe)) {
            $data = no_stripe($data);
        }
        cache($id, $data);
    }
    if (!isset($data['uptime'])) {
        // The server is down
        downfile($id, $servers[$id], $failafter, $mailme, $emailto, $emailfrom, $time);
        downinfo();
    } else {
        // The server is up
        checkdown($id, $mailme, $emailto, $emailfrom, $time, $failafter, $servers[$id]);
        checkload($data['load'], $emailto, $emailfrom, $servers[$id], $id, $mailme, $time);
        echo json_encode($data);
    }
}

// ================================================================================================================
//														Functions
// ================================================================================================================
// This function checks if your load is to high and emails you
function checkload($load, $emailto, $emailfrom, $server, $id, $mailme, $time) {
    $path = '../cache/' . $id . '.load';
    if (isset($load) && $mailme == 1 && isset($server['maxload'])) {
        if ($server['maxload'] <= $load && !file_exists($path)) {
            $message = "Node: " . $server['name'] . " on host " . $server['host'] . " has an alarming load of " . $load . " at " . date("H:i | d M Y", $time);
            $message = wordwrap($message, 70, "\r\n");
            mail($emailto, "ServerStatus: " . $server['name'] . " has an alarming load!", $message, 'From: ServerStatus <' . $emailfrom . '>' . "\r\n");
            file_put_contents($path, json_encode(array('load' => $load, 'time' => $time, 'name' => $server['name'])));
        } elseif ($server['maxload'] > $load && file_exists($path)) {
            ob_start();
            echo file_get_contents_curl_curl($path);
            $path_url = ob_get_contents();
            ob_end_clean();
            $data = json_decode($path_url, true);
            unlink($path);
            if ($data['name'] == $server['name']) {
                $message = "Node: " . $server['name'] . " on host " . $server['host'] . " has returned to a normal load at " . date("H:i | d M Y", $time) . ". It was in a critical load for " . number_format((($time - $data['time']) / 60), 0, '.', '') . " Minutes.";
                $message = wordwrap($message, 70, "\r\n");
                mail($emailto, "ServerStatus: " . $server['name'] . " load has returned to normal!", $message, 'From: ServerStatus <' . $emailfrom . '>' . "\r\n");
            }
        }
    }
}

// This file checks if the server was down!
function checkdown($id, $mailme, $emailto, $emailfrom, $time, $failafter, $server) {
    $path = '../cache/' . $id . '.down';
    if (file_exists($path)) {
        ob_start();
        echo file_get_contents_curl($path);
        $path_url = ob_get_contents();
        ob_end_clean();
        $data = json_decode($path_url, true);
        unlink($path);
        $totalfail = $time - $data['time'];
        if ($totalfail > $failafter && $data['name'] == $server['name']) {
            $failures = array();
            $failures[] = array('down' => $data['time'], 'upagain' => $time, 'name' => $server['name'], 'host' => $server['host'], 'type' => $server['type'], 'uptime' => $data['uptime']);
            ob_start();
            echo file_get_contents_curl("../cache/outages.db");
            $path_url = ob_get_contents();
            ob_end_clean();
            $oldfails = json_decode($path_url, true);
            if ($mailme == 1) {
                $message = "Node: " . $server['name'] . " on host " . $server['host'] . " is up as of: " . date("H:i | d M Y", $time) . ". It was down for: " . number_format((($time - $data['time']) / 60), 0, '.', '') . " Minutes.";
                $message = wordwrap($message, 70, "\r\n");
                mail($emailto, "ServerStatus: " . $server['name'] . " is up!", $message, 'From: ServerStatus <' . $emailfrom . '>' . "\r\n");
            }
            foreach ($oldfails as $fail) {
                $failures[] = $fail;
            }
            file_put_contents("../cache/outages.db", json_encode($failures));
        }
    }
}

// This function creates .down files when a server goes offline and manages 
// email alerts
function downfile($id, $server, $failafter, $mailme, $emailto, $emailfrom, $time) {
    $path = '../cache/' . $id . '.down';
    if (!file_exists($path)) {
        file_put_contents($path, json_encode(array('time' => $time, 'name' => $server['name'])));
    } else {
        ob_start();
        echo file_get_contents_curl($path);
        $path_url = ob_get_contents();
        ob_end_clean();
        $down = json_decode($path_url, true);
        if (($down['time'] + $failafter) <= $time && !isset($down['mailed']) && $mailme == 1 && $down['time'] != null) {
            $message = "Node: " . $server['name'] . " on host " . $server['host'] . " is down as of: " . date("H:i | d M Y", $down['time']) . ". It has currently been down for " . $failafter . " seconds.";
            $message = wordwrap($message, 70, "\r\n");
            mail($emailto, "ServerStatus: " . $server['name'] . " is down!", $message, 'From: ServerStatus <' . $emailfrom . '>' . "\r\n");
            $down['mailed'] = 'yes';
            file_put_contents($path, json_encode($down));
        }
    }
}

// This function is for posting the down array info
function downinfo() {
    $array = array();
    $array['uptime'] = '
	<div class="progress">
		<div class="progress-bar progress-bar-danger progress-bar-striped" style="width: 100%;"><small>Down</small></div>
	</div>
	';
    $array['load'] = '
	<div class="progress">
		<div class="progress-bar progress-bar-danger progress-bar-striped" style="width: 100%;"><small>Down</small></div>
	</div>
	';
    $array['online'] = '
	<div class="progress">
		<div class="progress-bar progress-bar-danger progress-bar-striped" style="width: 100%;"><small>Down</small></div>
	</div>
	';
    echo json_encode($array);
}

// This gets data from our cache 
function get_cache($id, $cache, $time) {
    $path = '../cache/' . $id . '.raw';
    if (!file_exists($path)) {
        return;
    } else {
        ob_start();
        echo file_get_contents_curl($path);
        $path_url = ob_get_contents();
        ob_end_clean();
        $data = json_decode($path_url, true);
    }
    if (($data['time'] + $cache) < $time) {
        unlink($path);
        return;
    } else {
        return $data;
    }
}

// This puts data into a cache
function cache($id, $data) {
    $path = '../cache/' . $id . '.raw';
    if (isset($data['uptime'])) {
        file_put_contents($path, json_encode($data));
    } elseif (file_exists($path)) {
        unlink($path);
    }
}

// This pulls data from the backend servers!
function get_data($url) {
    $opts = array(
        'http' => array(
            'method' => "GET",
            'timeout' => 10,
            'header' => "Accept-language: en\r\n" .
            "Cookie: foo=bar\r\n" .
            "User-Agent: ServerStatus @  " . $_SERVER['SERVER_NAME']
        )
    );
    $context = stream_context_create($opts);
    ob_start();
    echo file_get_contents_curl($url, false, $context);
    $path_url = ob_get_contents();
    ob_end_clean();
    $data = json_decode($path_url, true);
    if (isset($data['load'])) {
        $data["load"] = number_format($data["load"], 2);
    }
    return $data;
}

// turns off animations to improve performance on clients
function no_stripe($data) {
    $data['memory'] = str_replace("progress-striped", '', $data['memory']);
    $data['hdd'] = str_replace("progress-striped", '', $data['hdd']);

    return $data;
}

function file_get_contents_curl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}
