<?php
function mow_update($data)
{
    global $site_config, $queries, $mc1;
    set_time_limit(1200);
    ignore_user_abort(true);
    //== Movie of the week
    $res_tor = sql_query('SELECT id, name FROM torrents WHERE times_completed > 0 AND category IN (' . join(', ', $site_config['movie_cats']) . ') ORDER BY times_completed DESC LIMIT 1') or sqlerr(__FILE__, __LINE__);
    if (mysqli_num_rows($res_tor) > 0) {
        $arr = mysqli_fetch_assoc($res_tor);
        sql_query('UPDATE avps SET value_u=' . sqlesc($arr['id']) . ', value_i=' . sqlesc(TIME_NOW) . " WHERE avps.arg='bestfilmofweek'") or sqlerr(__FILE__, __LINE__);
        $mc1->delete_value('top_movie_2');
        write_log('Torrent [' . (int)$arr['id'] . ']&#160;[' . htmlentities($arr['name']) . "] was set 'Best Film of the Week' by system");
    }
    //==End
    if ($queries > 0) {
        write_log("Movie of the Week Cleanup: Completed using $queries queries");
    }
    if (false !== mysqli_affected_rows($GLOBALS['___mysqli_ston'])) {
        $data['clean_desc'] = mysqli_affected_rows($GLOBALS['___mysqli_ston']) . ' items deleted/updated';
    }
    if ($data['clean_log']) {
        cleanup_log($data);
    }
}
