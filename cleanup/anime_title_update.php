<?php

function anime_titles_update($data)
{
    $time_start = microtime(true);
    global $BLOCKS, $fluent, $site_config, $cache;

    set_time_limit(1200);
    if (!$BLOCKS['anime_api_on']) {
        return;
    }

    $anime_data = $cache->get('anime_data_');
    if ($anime_data === false || is_null($anime_data)) {
        $url = 'https://raw.githubusercontent.com/manami-project/anime-offline-database/master/anime-offline-database.json';
        $json = fetch($url);
        if (!empty($json)) {
            $anime_data = json_decode($json, true);
            $cache->set('anime_data_', TIME_NOW, 43200);

            $values = [];
            $types = ['TV', 'Movie', 'OVA', 'ONA', 'Special', 'Music'];
            if (!empty($anime_data)) {
                foreach ($anime_data['data'] as $anime_data) {
                    if (!empty($anime_data['title'])) {
                        $titles['title'] = trim(preg_replace('/\s+/', ' ' , $anime_data['title']));
                        $titles['image'] = !empty($anime_data['picture']) ? trim($anime_data['picture']) : '';
                        $titles['type'] = !empty($anime_data['type']) && in_array($anime_data['type'], $types) ? $anime_data['type'] : '';
                        $titles['anilist_id'] = $titles['anidb_id'] = $titles['kitsu_id'] = $titles['myanimelist_id'] = 0;
                        foreach ($anime_data['sources'] as $source) {
                            preg_match("/https?:\/\/anilist.co\/anime\/(\d+)/", $source, $anilist);
                            if (!empty($anilist[1])) {
                                $titles['anilist_id'] = $anilist[1];
                                continue;
                            }
                            preg_match("/https?:\/\/anidb.net\/a(\d+)/", $source, $anidb);
                            if (!empty($anidb[1])) {
                                $titles['anidb_id'] = $anidb[1];
                                continue;
                            }
                            preg_match("/https?:\/\/kitsu.io\/anime\/(\d+)/", $source, $kitsu);
                            if (!empty($kitsu[1])) {
                                $titles['kitsu_id'] = $kitsu[1];
                                continue;
                            }
                            preg_match("/https?:\/\/myanimelist.net\/anime\/(\d+)/", $source, $myanimelist);
                            if (!empty($myanimelist[1])) {
                                $titles['myanimelist_id'] = $myanimelist[1];
                                continue;
                            }
                        }
                        $values[] = $titles;
                    }
                }
            }

            if (!empty($values)) {
                $count = floor($site_config['query_limit'] / 2 / max(array_map('count', $values)));
                $update = [
                    'image' => new Envms\FluentPDO\Literal('VALUES(image)'),
                    'type' => new Envms\FluentPDO\Literal('VALUES(type)'),
                    'myanimelist_id' => new Envms\FluentPDO\Literal('VALUES(myanimelist_id)'),
                    'kitsu_id' => new Envms\FluentPDO\Literal('VALUES(kitsu_id)'),
                    'anidb_id' => new Envms\FluentPDO\Literal('VALUES(anidb_id)'),
                    'anilist_id' => new Envms\FluentPDO\Literal('VALUES(anilist_id)'),
                ];

                foreach (array_chunk($values, $count) as $t) {
                    $fluent->insertInto('anime_titles', $t)
                        ->onDuplicateKeyUpdate($update)
                        ->execute();
                }
            }

            $time_end = microtime(true);
            $run_time = $time_end - $time_start;
            $text = " Run time: $run_time seconds";
            echo $text . "\n";
            if ($data['clean_log']) {
                write_log('ANIME Titles Cleanup: completed.' . $text);
            }
        }
    }
}
