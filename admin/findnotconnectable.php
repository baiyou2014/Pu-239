<?php

require_once INCL_DIR . 'user_functions.php';
require_once INCL_DIR . 'html_functions.php';
require_once INCL_DIR . 'bbcode_functions.php';
require_once INCL_DIR . 'torrenttable_functions.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $CURUSER, $lang, $message_stuffs;

$lang = array_merge($lang, load_language('non_con'));
$HTMLOUT = '';
//dd($_POST);
if (isset($_GET['action1']) && htmlsafechars($_GET['action1']) === 'list') {
    $res2 = sql_query("SELECT userid, seeder, torrent, agent FROM peers WHERE connectable='no' ORDER BY userid DESC") or sqlerr(__FILE__, __LINE__);

    $HTMLOUT .= "
    <ul class='level-center bg-06'>
        <li class='altlink margin10'>
            <a href='{$site_config['baseurl']}/staffpanel.php?tool=findnotconnectable&amp;action=findnotconnectable&amp;action1=sendpm'>{$lang['non_con_sendall']}</a>
        </li>
        <li class='altlink margin10'>
            <a href='{$site_config['baseurl']}/staffpanel.php?tool=findnotconnectable&amp;action=findnotconnectable'>{$lang['non_con_view']}</a>
        </li>
    </ul>
    <h1 class='has-text-centered'>{$lang['non_con_peers']}</h1>";
    $result = sql_query("SELECT DISTINCT userid FROM peers WHERE connectable = 'no'");
    $count = mysqli_num_rows($result);
    @((mysqli_free_result($result) || (is_object($result) && (get_class($result) === 'mysqli_result'))) ? true : false);
    if (mysqli_num_rows($res2) == 0) {
        $HTMLOUT .= stdmsg($lang['non_con_sorry'], $lang['non_con_all']);
    } else {
        $HTMLOUT .= "
        {$lang['non_con_this']}<br>
        <p>
            <span class='has-text-danger'>*</span> {$lang['non_con_means']}<br>
            $count {$lang['non_con_unique']}
        </p>";
        $heading = "
            <tr>
                <th>{$lang['non_con_name']}</th>
                <th>{$lang['non_con_tor']}</th>
                <th>{$lang['non_con_client']}</th>
            </tr>";
        $body = '';
        while ($arr2 = mysqli_fetch_assoc($res2)) {
            $body .= '
            <tr>
                <td>' . format_username($arr2['userid']) . "</td>
                <td><a href='{$site_config['baseurl']}/details.php?id={$arr2['torrent']}&amp;dllist=1#seeders'>{$arr2['torrent']}</a>";
            if ($arr2['seeder'] === 'yes') {
                $body .= "<span class='has-text-danger'>*</span>";
            }
            $body .= '
                </td>
                <td>' . htmlsafechars($arr2['agent']) . '</td>
            </tr>';
        }
        $HTMLOUT .= main_table($body, $heading);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dt = TIME_NOW;
    $msg = htmlsafechars($_POST['body']);
    if (!$msg) {
        stderr('Error', 'Please Type In Some Text');
    }
    $users = $fluent->from('peers')
        ->select(null)
        ->select('DISTINCT userid AS userid')
        ->where('connectable = "no"');

    foreach ($users as $user) {
        $values[] = [
            'sender' => 0,
            'receiver' => $user['userid'],
            'added' => $dt,
            'msg' => $msg,
            'subject' => 'Connectability',
        ];
    }

    if (!empty($values)) {
        $message_stuffs->insert($values);
        $values = [
            'user' => $CURUSER['id'],
            'date' => $dt,
        ];
        $fluent->insertInto('notconnectablepmlog')
            ->values($values)
            ->execute();
        $session->set('is-success', 'PM Sent to all non connectable peers');
    } else {
        $session->set('is-warning', 'No non connectable peers');
    }
}
if (isset($_GET['action1']) && htmlsafechars($_GET['action1']) === 'sendpm') {
    $HTMLOUT .= "
    <ul class='level-center bg-06'>
        <li class='altlink margin10'>
            <a href='{$site_config['baseurl']}/staffpanel.php?tool=findnotconnectable&amp;action=findnotconnectable'>{$lang['non_con_view']}</a>
        </li>
        <li class='altlink margin10'>
            <a href='{$site_config['baseurl']}/staffpanel.php?tool=findnotconnectable&amp;action=findnotconnectable&amp;action1=list'>{$lang['non_con_list']}</a>
        </li>
    </ul>
    <div>
        <h1 class='has-text-centered'>{$lang['non_con_mass']}</h1>
        <form method='post' action='{$site_config['baseurl']}/staffpanel.php?tool=findnotconnectable&amp;action=findnotconnectable'>";
    if (isset($_GET['returnto']) || isset($_SERVER['HTTP_REFERER'])) {
        $HTMLOUT .= "<input type='hidden' name='returnto' value='" . (isset($_GET['returnto']) ? htmlsafechars($_GET['returnto']) : htmlsafechars($_SERVER['HTTP_REFERER'])) . "'>";
    }
    $receiver = '';
    $body = "{$lang['non_con_body']}";
    $HTMLOUT .= main_div(BBcode($body, null, 250) . "
            <div class='has-text-centered'>
                <input type='submit' value='Send' class='button is-small'>
            </div>") . '
        </form>
    </div>';
}
if (isset($_GET['action1']) == '') {
    $getlog = sql_query('SELECT * FROM `notconnectablepmlog` ORDER BY date DESC LIMIT 20') or sqlerr(__FILE__, __LINE__);
    $HTMLOUT .= "
    <ul class='level-center bg-06'>
        <li class='altlink margin10'>
            <a href='{$site_config['baseurl']}/staffpanel.php?tool=findnotconnectable&amp;action=findnotconnectable&amp;action1=sendpm'>{$lang['non_con_sendall']}</a>
        </li>
        <li class='altlink margin10'>
            <a href='{$site_config['baseurl']}/staffpanel.php?tool=findnotconnectable&amp;action=findnotconnectable&amp;action1=list'>{$lang['non_con_list']}</a>
        </li>
    </ul>
    <h1 class='has-text-centered'>{$lang['non_con_uncon']}</h1>";
    if (mysqli_num_rows($getlog) > 0) {
        $HTMLOUT .= "
    <p>{$lang['non_con_please1']}</p>";
        $heading = "
        <tr>
            <th>{$lang['non_con_by']}</th>
            <th>{$lang['non_con_date']}</th>
            <th>{$lang['non_con_elapsed']}</th>
        </tr>";
        $body = '';
        while ($arr2 = mysqli_fetch_assoc($getlog)) {
            $elapsed = get_date($arr2['date'], '', 0, 1);
            $body .= '
        <tr>
            <td>' . format_username($arr2['user']) . '</td>
            <td>' . get_date($arr2['date'], '') . "</td>
            <td>$elapsed</td>
        </tr>";
        }
        $HTMLOUT .= main_table($body, $heading);
    } else {
        $HTMLOUT .= stdmsg($lang['non_con_sorry'], $lang['non_con_all']);
    }
}
echo stdhead() . wrapper($HTMLOUT) . stdfoot();
die();
