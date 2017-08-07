<?php
if (!defined('IN_INSTALLER09_ADMIN')) {
    setSessionVar('error', 'Access Not Allowed');
    header("Location: {$INSTALLER09['baseurl']}/index.php");
    die();
}
require_once INCL_DIR . 'user_functions.php';
require_once INCL_DIR . 'html_functions.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
$lang = array_merge($lang, load_language('ad_nameblacklist'));
$blacklist = file_exists($INSTALLER09['nameblacklist']) && is_array(unserialize(file_get_contents($INSTALLER09['nameblacklist']))) ? unserialize(file_get_contents($INSTALLER09['nameblacklist'])) : [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $badnames = isset($_POST['badnames']) && !empty($_POST['badnames']) ? trim($_POST['badnames']) : '';
    if (empty($badnames)) {
        stderr($lang['name_hmm'], $lang['name_think']);
    }
    if (strpos($badnames, ',')) {
        foreach (explode(',', $badnames) as $badname) {
            $blacklist[$badname] = (int)1;
        }
    } else {
        $blacklist[$badnames] = (int)1;
    }
    if (file_put_contents($INSTALLER09['nameblacklist'], serialize($blacklist))) {
        header('Refresh:2; url=staffpanel.php?tool=nameblacklist');
        stderr($lang['name_success'], $lang['name_file']);
    } else {
        stderr($lang['name_err'], ' ' . $lang['name_hmm'] . '<b>' . $INSTALLER09['nameblacklist'] . '</b>' . $lang['name_is'] . '');
    }
} else {
    $out = begin_main_frame();
    $out .= stdmsg($lang['name_curr'], count($blacklist) ? join(', ', array_keys($blacklist)) : $lang['name_no']);
    $out .= stdmsg($lang['name_add'], '<form action="staffpanel.php?tool=nameblacklist&amp;action=nameblacklist" method="post"><table width="90%" cellspacing="2" cellpadding="5" align="center" style="border-collapse:separate">
	<tr><td align="center"><textarea rows="3" cols="100" name="badnames"></textarea></td></tr>
    <tr><td align="center">' . $lang['name_note'] . '</td></tr>
	<tr> <td align="center"><input type="submit" value="' . $lang['name_update'] . '"/></td></tr>
	</table></form>');
    $out .= end_main_frame();
    echo stdhead($lang['name_stdhead']) . $out . stdfoot();
}
