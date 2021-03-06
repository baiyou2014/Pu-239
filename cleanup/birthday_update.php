<?php

/**
 * @param $data
 *
 * @throws \Envms\FluentPDO\Exception
 */
function birthday_update($data)
{
    $time_start = microtime(true);
    require_once INCL_DIR . 'user_functions.php';
    global $site_config, $cache, $message_stuffs, $fluent;

    set_time_limit(1200);
    ignore_user_abort(true);
    $dt = TIME_NOW;
    $date = getdate();

    $users = $fluent->from('users')
        ->select(null)
        ->select('id')
        ->select('class')
        ->select('username')
        ->select('uploaded')
        ->where('MONTH(birthday) = ?', $date['mon'])
        ->where('DAYOFMONTH(birthday) = ?', $date['mday']);

    $count = 0;
    $msgs = [];
    if (!empty($users)) {
        $subject = "It's your birthday!!";
        foreach ($users as $arr) {
            $msg = 'Hey there <span class="' . get_user_class_name($arr['class'], true) . '">' . htmlsafechars($arr['username']) . "</span> happy birthday, hope you have a good day. We awarded you 10 gig...Njoi.\n";
            $msgs[] = [
                'sender' => 0,
                'poster' => 0,
                'receiver' => $arr['id'],
                'added' => $dt,
                'msg' => $msg,
                'subject' => $subject,
            ];
        }
        $count = count($msgs);
        if ($count > 0) {
            if ($count > 100) {
                foreach (array_chunk($msgs, 150) as $t) {
                    echo 'Inserting ' . count($t) . " messages\n";
                    $message_stuffs->insert($t);
                }
            } else {
                $message_stuffs->insert($msgs);
            }

            $set = [
                'uploaded' => new Envms\FluentPDO\Literal('uploaded + 10737418240'),
            ];
            $fluent->update('users')
                ->set($set)
                ->where('MONTH(birthday) = ?', $date['mon'])
                ->where('DAYOFMONTH(birthday) = ?', $date['mday'])
                ->execute();
        }
    }
    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log("Birthday Cleanup: Pm'd' " . $count . ' member(s) and awarded a birthday prize' . $text);
    }
}
