<?php

global $CURUSER, $site_config, $lang, $cache, $message_stuffs;

$message = $message_stuffs->get_by_id($pm_id);
if ($message['receiver'] == $CURUSER['id'] && $message['urgent'] === 'yes' && $message['unread'] === 'yes') {
    stderr($lang['pm_error'], '' . $lang['pm_delete_err'] . '<a class="altlink" href="' . $site_config['baseurl'] . '/messages.php?action=view_message&id=' . $pm_id . '">' . $lang['pm_delete_msg'] . '</a> to message.');
}
if (($message['receiver'] == $CURUSER['id'] || $message['sender'] == $CURUSER['id']) && $message['location'] == PM_DELETED) {
    $message_stuffs->delete($pm_id, $CURUSER['id']);
} elseif ($message['receiver'] == $CURUSER['id']) {
    $set = [
        'location' => 0,
        'unread' => 'no',
    ];
    $message_stuffs->update($set, $pm_id);
    $cache->decrement('inbox_' . $CURUSER['id']);
} elseif ($message['sender'] == $CURUSER['id'] && $message['location'] != PM_DELETED) {
    $set = [
        'saved' => 'no',
    ];
    $message_stuffs->update($set, $pm_id);
}

header("Location: {$site_config['baseurl']}/messages.php?action=view_mailbox&deleted=1");
die();
