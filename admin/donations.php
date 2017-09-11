<?php
if (!defined('IN_site_config_ADMIN')) {
    setSessionVar('error', 'Access Not Allowed');
    header("Location: {$site_config['baseurl']}/index.php");
    exit();
}
require_once INCL_DIR . 'user_functions.php';
require_once INCL_DIR . 'pager_functions.php';
require_once INCL_DIR . 'html_functions.php';
require_once CLASS_DIR . 'class_check.php';
$lang = array_merge($lang, load_language('ad_donations'));
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
$HTMLOUT = '';
$count2 = 0;
if (isset($_GET['total_donors'])) {
    $total_donors = (int)$_GET['total_donors'];
    if ($total_donors != '1') {
        stderr($lang['donate_err'], $lang['donate_err1']);
    }
    $res = sql_query("SELECT COUNT(*) FROM users WHERE total_donated != '0.00' AND enabled='yes'") or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_array($res);
    $count = $row[0];
    $perpage = 15;
    $pager = pager($perpage, $count, 'staffpanel.php?tool=donations&amp;action=donations&amp;');
    if (mysqli_num_rows($res) == 0) {
        stderr($lang['donate_sorry'], $lang['donate_nofound']);
    }
    $users = number_format(get_row_count('users', "WHERE total_donated != '0.00'"));
    $HTMLOUT .= begin_frame("{$lang['donate_list_all']} [" . htmlsafechars($users) . ']', true);
    $res = sql_query("SELECT id, username, email, added, donated, donoruntil, total_donated FROM users WHERE total_donated != '0.00' ORDER BY id DESC " . $pager['limit'] . '') or sqlerr(__FILE__, __LINE__);
} // ===end total donors
else {
    $res = sql_query("SELECT COUNT(id) FROM users WHERE donor='yes'") or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_array($res);
    $count = $row[0];
    $perpage = 15;
    $pager = pager($perpage, $count, 'staffpanel.php?tool=donations&amp;action=donations&amp;');
    if (mysqli_num_rows($res) == 0) {
        stderr($lang['donate_sorry'], $lang['donate_nofound']);
    }
    $users = number_format(get_row_count('users', "WHERE donor='yes'"));
    $HTMLOUT .= begin_frame("{$lang['donate_list_curr']} [" . htmlsafechars($users) . ' ]', true);
    $res = sql_query("SELECT id, username, email, added, donated, total_donated, donoruntil FROM users WHERE donor='yes' ORDER BY id DESC " . $pager['limit'] . '') or sqlerr(__FILE__, __LINE__);
}
if ($count > $perpage) {
    $HTMLOUT .= $pager['pagertop'];
}
$HTMLOUT .= begin_table();
$HTMLOUT .= "<tr><td colspan='9' align='center'><a class='altlink' href='{$site_config['baseurl']}/staffpanel.php?tool=donations&amp;action=donations'>{$lang['donate_curr_don']}</a> || <a class='altlink' href='{$site_config['baseurl']}/staffpanel.php?tool=donations&amp;action=donations&amp;total_donors=1'>{$lang['donate_all_don']}</a></td></tr>";
$HTMLOUT .= "<tr><td class='colhead'>{$lang['donate_id']}</td><td class='colhead' align='left'>{$lang['donate_username']}</td><td class='colhead' align='left'>{$lang['donate_email']}</td>" . "<td class='colhead' align='left'>{$lang['donate_joined']}</td><td class='colhead' align='left'>{$lang['donate_until']}</td><td class='colhead' align='left'>" . "{$lang['donate_current']}</td><td class='colhead' align='left'>{$lang['donate_total']}</td><td class='colhead' align='left'>{$lang['donate_pm']}</td></tr>";
while ($arr = mysqli_fetch_assoc($res)) {
    // =======change colors
    if ($count2 == 0) {
        $count2 = $count2 + 1;
        $class = 'one';
    } else {
        $count2 = 0;
        $class = 'two';
    }
    // =======end
    $HTMLOUT .= "<tr><td valign='bottom' class='$class'><a class='altlink' href='{$site_config['baseurl']}/userdetails.php?id=" . htmlsafechars($arr['id']) . "'>" . htmlsafechars($arr['id']) . '</a></td>' . "<td align='left' valign='bottom' class='$class'><a class='altlink' href='{$site_config['baseurl']}/userdetails.php?id=" . htmlsafechars($arr['id']) . "'><b>" . htmlsafechars($arr['username']) . '</b></a>' . "</td><td align='left' valign='bottom' class='$class'><a class='altlink' href='mailto:" . htmlsafechars($arr['email']) . "'>" . htmlsafechars($arr['email']) . '</a>' . "</td><td align='left' valign='bottom' class='$class'><font size=\"-3\"> " . get_date($arr['added'], 'DATE') . '</font>' . "</td><td align='left' valign='bottom' class='$class'>";
    $donoruntil = (int)$arr['donoruntil'];
    if ($donoruntil == '0') {
        $HTMLOUT .= 'n/a';
    } else {
        $HTMLOUT .= '<font size="-3"> ' . get_date($arr['donoruntil'], 'DATE') . ' [ ' . mkprettytime($donoruntil - TIME_NOW) . " ]{$lang['donate_togo']}</font>";
    }
    $HTMLOUT .= "</td><td align='left' valign='bottom' class='$class'><b>&#163;" . htmlsafechars($arr['donated']) . '</b></td>' . "<td align='left' valign='bottom' class='$class'><b>&#163;" . htmlsafechars($arr['total_donated']) . '</b></td>' . "<td align='left' valign='bottom' class='$class'><b><a class='altlink' href='{$site_config['baseurl']}/pm_system.php?action=send_message&amp;receiver=" . (int)$arr['id'] . "'>{$lang['donate_sendpm']}</a></b></td></tr>";
}
$HTMLOUT .= end_table();
$HTMLOUT .= end_frame();
if ($count > $perpage) {
    $HTMLOUT .= $pager['pagerbottom'];
}
echo stdhead($lang['donate_stdhead']) . $HTMLOUT . stdfoot();
