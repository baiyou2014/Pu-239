<?php

require_once dirname(__FILE__, 3) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php';
require_once INCL_DIR . 'bbcode_functions.php';
global $session, $cache, $torrent_stuffs;

extract($_POST);
header('content-type: application/json');
if (empty($csrf) || !$session->validateToken($csrf)) {
    echo json_encode(['fail' => 'csrf']);
    die();
}

$tid = is_numeric($tid) ? (int) $tid : '';
if (!empty($tid)) {
    $torrent = $torrent_stuffs->get($tid);
    if (!empty($torrent)) {
        $descr = $torrent['descr'];
        if (!preg_match('/\[pre\].*\[\/pre\]/isU', $descr)) {
            $descr = '[pre]' . $descr . '[/pre]';
        }
        $descr = format_comment($descr);
        $cache->set('torrent_descr_' . $tid, $descr, 86400);

        echo json_encode(['descr' => $descr]);
        die();
    }
}
echo json_encode([
    'fail' => 'invalid',
]);
die();
