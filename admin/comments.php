<?php

require_once INCL_DIR . 'user_functions.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $lang;

$lang = array_merge($lang, load_language('ad_comments'));
$view = isset($_GET['view']) ? htmlsafechars($_GET['view']) : '';
switch ($view) {
    case 'allComments':
        $sql = 'SELECT c.id, c.user, c.torrent, c.text, c.ori_text, c.added, t.name, u.username FROM comments AS c JOIN users AS u ON u.id = c.user JOIN torrents AS t ON  c.torrent = t.id ORDER BY c.id DESC';
        $query = sql_query($sql) or sqlerr(__FILE__, __LINE__);
        $rows = mysqli_num_rows($query);

        $Row_Count = 0;

        //==== HTML Output
        $HTMLOUT = "<h3><a href='staffpanel.php?tool=comments'>{$lang['text_overview']}</a>" . " - <a href='staffpanel.php?tool=comments&amp;view=allComments'>{$lang['text_all']}</a>" . " - <a href='staffpanel.php?tool=comments&amp;view=search'>{$lang['text_search']}</a>" . '</h3>' . "<hr class='separator'>" . '<br>' . "<table width='950px'>" . "<tr><td colspan='9'><strong><em>{$lang['text_all_comm']}</em></strong></td></tr>" . '<tr>' . "<td class='colhead'>{$lang['text_comm_id']}</td>" . "<td class='colhead'>{$lang['text_user_id']}</td>" . "<td class='colhead'>{$lang['text_torr_id']}</td>" . "<td class='colhead'>{$lang['text_comm']}</td>" . "<td class='colhead'>{$lang['text_comm_ori']}</td>" . "<td class='colhead'>{$lang['text_user']}</td>" . "<td class='colhead'>{$lang['text_torr']}</td>" . "<td class='colhead'>{$lang['text_added']}</td>" . "<td class='colhead'>{$lang['text_actions']}</td>" . '</tr>';

        while ($comment = mysqli_fetch_assoc($query)) {
            //==== Begin an array that will sanitize all the variables from the MySQLI query
            $comment = [
                'user' => (int) $comment['user'],
                'torrent' => (int) $comment['torrent'],
                'id' => (int) $comment['id'],
                'text' => htmlsafechars($comment['text']),
                'ori_text' => htmlsafechars($comment['ori_text']),
                'username' => htmlsafechars($comment['username']),
                'name' => htmlsafechars($comment['name']),
                'added' => (int) $comment['added'],
            ];
            //==== Alternate colors in table rows generated using MySQLI
            $Row_Class = $Row_Count % 2 ? 'regular' : 'alternate';

            //==== HTML Output
            $HTMLOUT .= "<tr class='{$Row_Class}'>" . "<td><a href='{$site_config['baseurl']}/details.php?id={$comment['torrent']}#comm{$comment['id']}'>{$comment['id']}</a>" . " (<a href='{$site_config['baseurl']}/comment.php?action=vieworiginal&amp;cid={$comment['id']}'>{$lang['text_view_ori_comm']}</a>)</td>" . "<td>{$comment['user']}</td>" . "<td>{$comment['torrent']}</td>" . "<td>{$comment['text']}</td>" . "<td>{$comment['ori_text']}</td>" . '<td>' . format_username($comment['user']) . " [<a href='{$site_config['baseurl']}/messages.php?action=send_message&amp;receiver={$comment['user']}'>{$lang['text_msg']}</a>]</td>" . "<td><a href='{$site_config['baseurl']}/details.php?id={$comment['torrent']}'>{$comment['name']}</a></td>" . '<td>' . get_date($comment['added'], 'DATE') . '</td>' . "<td><a href='{$site_config['baseurl']}/comment.php?action=edit&amp;cid={$comment['id']}'>{$lang['text_edit']}</a>" . " - <a href='{$site_config['baseurl']}/comment.php?action=delete&amp;cid={$comment['id']}'>{$lang['text_delete']}</a></td>" . '</tr>';

            //==== Increase the count for every row generated
            ++$Row_Count;
        }

        if ($rows == 0) {
            //==== Display an error if there are no rows in the MySQLI table
            $HTMLOUT .= "<tr><td colspan='9'>{$lang['text_no_rows']}</td></tr>";
        }

        $HTMLOUT .= '</table>';

        //==== Display Everything
        echo stdhead("{$lang['text_all_comm']}") . wrapper($HTMLOUT) . stdfoot();
        die();
        break;

    //==== Page: Search
    case 'search':
        $HTMLOUT = "<form method='post' action='staffpanel.php?tool=comments&amp;view=results'>" . '<table>' . '<tr>' . "<td class='colhead' colspan='2'>{$lang['text_search']}</td>" . '</tr>' . "<tr><td>{$lang['text_keywords']}</td><td><input type='text' name='keywords' size='40'></td></tr>" . "<tr><td colspan='2'><input type='submit' value='{$lang['text_submit']}'></td></tr>" . '</table>' . '</form>';

        //==== Display Everything
        echo stdhead("{$lang['text_search']}") . wrapper($HTMLOUT) . stdfoot();
        die();
        break;

    //==== Page: Search Results
    case 'results':
        $sql = 'SELECT c.id, c.user, c.torrent, c.text, c.added, t.name, u.username FROM comments AS c JOIN users AS u ON u.id = c.user JOIN torrents AS t ON c.torrent = t.id WHERE c.text LIKE ' . sqlesc("%{$_POST['keywords']}%") . ' ORDER BY c.added DESC';
        $query = sql_query($sql) or sqlerr(__FILE__, __LINE__);
        $rows = mysqli_num_rows($query);

        $Row_Count = 0;

        //==== HTML Output
        $HTMLOUT = '<table>' . "<tr><td colspan='8'><strong><em>{$lang['text_results']} " . htmlsafechars($_POST['keywords']) . '</em>' . '</strong></td></tr>' . "<tr><td class='colhead'>{$lang['text_comm_id']}</td>" . "<td class='colhead'>{$lang['text_user_id']}</td>" . "<td class='colhead'>{$lang['text_torr_id']}</td>" . "<td class='colhead'>{$lang['text_comm']}</td>" . "<td class='colhead'>{$lang['text_user']}</td>" . "<td class='colhead'>{$lang['text_torr']}</td>" . "<td class='colhead'>{$lang['text_added']}</td>" . "<td class='colhead'>{$lang['text_actions']}</td>" . '</tr>';

        while ($comment = mysqli_fetch_assoc($query)) {
            //==== Begin an array that will sanitize all variables from the MySQLI query
            $comment = [
                'id' => (int) $comment['id'],
                'user' => (int) $comment['user'],
                'torrent' => (int) $comment['torrent'],
                'text' => htmlsafechars($comment['text']),
                'added' => (int) $comment['added'],
                'name' => htmlsafechars($comment['name']),
                'username' => htmlsafechars($comment['username']),
            ];

            //==== Alternate colors in table rows generated using MySQLI
            $Row_Class = $Row_Count % 2 ? 'regular' : 'alternate';

            //==== HTML Output
            $HTMLOUT .= "<tr class='{$Row_Class}'>" . "<td>{$comment['id']}</td>" . "<td>{$comment['user']}</td>" . "<td>{$comment['torrent']}</td>" . "<td>{$comment['text']}</td>" . '<td>' . format_username($comment['user']) . "<td><a href='{$site_config['baseurl']}/details.php?id={$comment['torrent']}'>{$comment['name']}</a></td>" . '<td>' . get_date($comment['added'], 'DATE') . '</td>' . "<td><a href='{$site_config['baseurl']}/comment.php?action=edit&amp;cid={$comment['id']}'>{$lang['text_edit']}</a>" . " - <a href='{$site_config['baseurl']}/comment.php?action=delete&amp;cid={$comment['id']}'>{$lang['text_delete']}</a>" . '</td></tr>';
            ++$Row_Count;
        }

        $HTMLOUT .= '</table>';

        //==== Display Everything
        echo stdhead("{$lang['text_results']}{$_POST['keywords']}") . wrapper($HTMLOUT) . stdfoot();
        die();
        break;
}

$sql = 'SELECT c.id, c.user, c.torrent, c.text, c.ori_text, c.added, c.checked_by, c.checked_when, t.name, u.username FROM comments AS c JOIN users AS u ON u.id = c.user JOIN torrents AS t ON  c.torrent = t.id ORDER BY c.id DESC LIMIT 10';
$query = sql_query($sql) or sqlerr(__FILE__, __LINE__);
$rows = mysqli_num_rows($query);
$Row_Count = 0;

//==== HTML Output
$HTMLOUT = "<h3><a href='staffpanel.php?tool=comments'>{$lang['text_overview']}</a>" . " - <a href='staffpanel.php?tool=comments&amp;view=allComments'>{$lang['text_all']}</a>" . " - <a href='staffpanel.php?tool=comments&amp;view=search'>{$lang['text_search']}</a>" . '</h3>' . "<hr class='separator'>" . '<br>' . "<table width='950px'>" . "<tr><td colspan='9'><strong><em>{$lang['text_recent']}</em></strong></td></tr>" . '<tr>' . "<td class='colhead'>{$lang['text_comm_id']}</td>" . "<td class='colhead'>{$lang['text_user_id']}</td>" . "<td class='colhead'>{$lang['text_torr_id']}</td>" . "<td class='colhead'>{$lang['text_comm']}</td>" . "<td class='colhead'>{$lang['text_comm_ori']}</td>" . "<td class='colhead'>{$lang['text_user']}</td>" . "<td class='colhead'>{$lang['text_torr']}</td>" . "<td class='colhead'>{$lang['text_added']}</td>" . "<td class='colhead'>{$lang['text_actions']}</td>" . '</tr>';

while ($comment = mysqli_fetch_assoc($query)) {
    //==== Begin an array that will sanitize all the variables from the MySQLI query
    $comment = [
        'user' => (int) $comment['user'],
        'torrent' => (int) $comment['torrent'],
        'id' => (int) $comment['id'],
        'text' => htmlsafechars($comment['text']),
        'ori_text' => htmlsafechars($comment['ori_text']),
        'username' => htmlsafechars($comment['username']),
        'name' => htmlsafechars($comment['name']),
        'added' => (int) $comment['added'],
    ];
    //==== Alternate colors in table rows generated using MySQLI
    $Row_Class = $Row_Count % 2 ? 'regular' : 'alternate';

    //==== HTML Output
    $HTMLOUT .= "<tr class='{$Row_Class}'>" . "<td><a href='{$site_config['baseurl']}/details.php?id={$comment['torrent']}#comm{$comment['id']}'>{$comment['id']}</a>" . " (<a href='{$site_config['baseurl']}/comment.php?action=vieworiginal&amp;cid={$comment['id']}'>{$lang['text_view_ori_comm']}</a>)</td>" . "<td>{$comment['user']}</td>" . "<td>{$comment['torrent']}</td>" . "<td>{$comment['text']}</td>" . "<td>{$comment['ori_text']}</td>" . '<td>' . format_username($comment['user']) . "[<a href='{$site_config['baseurl']}/messages.php?action=send_message&amp;receiver={$comment['user']}'>{$lang['text_msg']}</a>]</td>" . "<td><a href='{$site_config['baseurl']}/details.php?id={$comment['torrent']}'>{$comment['name']}</a></td>" . '<td>' . get_date($comment['added'], 'DATE') . '</td>' . "<td><a href='{$site_config['baseurl']}/comment.php?action=edit&amp;cid={$comment['id']}'>{$lang['text_edit']}</a>" . " - <a href='{$site_config['baseurl']}/comment.php?action=delete&amp;cid={$comment['id']}'>{$lang['text_delete']}</a></td>" . '</tr>';

    //==== Increase the count for every row generated
    ++$Row_Count;
}

if ($rows == 0) {
    $HTMLOUT .= "<tr><td colspan='9'>{$lang['text_no_rows']}</td></tr>";
}

$HTMLOUT .= '</table>';

//==== Display Everything
echo stdhead("{$lang['text_overview']}") . wrapper($HTMLOUT) . stdfoot();
