<?php

require_once INCL_DIR . 'function_fanart.php';
require_once INCL_DIR . 'function_tmdb.php';
require_once INCL_DIR . 'function_imdb.php';
require_once INCL_DIR . 'function_details.php';
require_once INCL_DIR . 'html_functions.php';

use Imdb\Config;

/**
 * @param      $imdb_id
 * @param bool $title
 * @param bool $data_only
 * @param bool $tid
 * @param bool $poster
 *
 * @return array|bool
 *
 * @throws Exception
 */
function get_imdb_info($imdb_id, $title = true, $data_only = false, $tid = false, $poster = false)
{
    global $cache, $BLOCKS, $torrent_stuffs, $image_stuffs, $site_config, $fluent;

    if (!$BLOCKS['imdb_api_on']) {
        return false;
    }

    $imdbid = $imdb_id;
    $imdb_id = str_replace('tt', '', $imdb_id);
    $imdb_data = $cache->get('imdb_' . $imdb_id);
    if ($imdb_data === false || is_null($imdb_data)) {
        $config = new Config();
        $config->language = 'en-US';
        $config->cachedir = IMDB_CACHE_DIR;
        $config->throwHttpExceptions = 0;
        $config->default_agent = get_random_useragent();

        $movie = new \Imdb\Title($imdb_id, $config);
        if (empty($movie->title())) {
            $cache->set('imdb_' . $imdb_id, 'failed', 86400);

            return false;
        }
        $imdb_data = [
            'title' => $movie->title(),
            'director' => $movie->director(),
            'writing' => $movie->writing(),
            'producer' => $movie->producer(),
            'composer' => $movie->composer(),
            'cast' => $movie->cast(),
            'genres' => $movie->genres(),
            'plotoutline' => $movie->plotoutline(true),
            'trailers' => $movie->trailers(true, true),
            'language' => $movie->language(),
            'rating' => is_numeric($movie->rating()) ? $movie->rating() : 0,
            'year' => $movie->year(),
            'runtime' => $movie->runtime(),
            'votes' => $movie->votes(),
            'critics' => $movie->metacriticRating(),
            'poster' => $movie->photo(false),
            'country' => $movie->country(),
            'vote_count' => $movie->votes(),
            'mpaa' => $movie->mpaa(),
            'mpaa_reason' => $movie->mpaa_reason(),
            'id' => $imdbid,
            'aspect_ratio' => $movie->aspect_ratio(),
            'plot' => $movie->plot(),
            'top250' => $movie->top250(),
            'movietype' => $movie->movietype(),
            'storyline' => $movie->storyline(),
            'updated' => get_date(TIME_NOW, 'LONG', 1, 0),
        ];

        if (count($imdb_data['genres']) > 0) {
            $temp = implode(', ', array_map('strtolower', $imdb_data['genres']));
            $imdb_data['genres'] = explode(', ', $temp);
            $imdb_data['newgenre'] = implode(', ', array_map('ucwords', $imdb_data['genres']));
        }

        $members = [
            'director',
            'writing',
            'producer',
            'composer',
            'cast',
        ];

        $cast = $persons = [];
        foreach ($members as $member) {
            if (count($imdb_data[$member]) > 0) {
                foreach ($imdb_data[$member] as $person) {
                    $all_people[] = $person;
                    $persons[] = [
                        'name' => $person['name'],
                        'imdb_id' => $person['imdb'],
                    ];
                    $cast[] = [
                        'imdb_id' => $imdb_id,
                        'person_id' => $person['imdb']
                    ];
                }
            }
        }

        if (!empty($persons)) {
            $fluent->insertInto('person')
                ->values($persons)
                ->ignore()
                ->execute();
        }

        if (!empty($cast)) {
            $fluent->insertInto('imdb_person')
                ->values($cast)
                ->ignore()
                ->execute();
        }
        unset($cast, $persons);

        if (!empty($imdb_data['plotoutline'])) {
            $values = [
                'imdb_id' => $imdb_id,
                'plot' => $imdb_data['plotoutline'],
            ];
            $fluent->insertInto('imdb_info')
                ->values($values)
                ->ignore()
                ->execute();
        }

        $cache->set('imdb_' . $imdb_id, $imdb_data, 604800);
    }
    if ($tid) {
        $set = [];
        if (!empty($imdb_data['newgenre'])) {
            $set = [
                'newgenre' => $imdb_data['newgenre'],
            ];
        }
        $set = array_merge($set, [
            'year' => $imdb_data['year'],
            'rating' => $imdb_data['rating'],
        ]);

        $torrent_stuffs->update($set, $tid);
    }
    if (empty($imdb_data)) {
        $cache->set('imdb_' . $imdb_id, 'failed', 86400);

        return false;
    }
    if ($data_only) {
        return $imdb_data;
    }
    if (!empty($imdb_data['poster']) && empty($poster)) {
        $poster = $imdb_data['poster'];
        $values = [
            'imdb_id' => $imdbid,
            'url' => $poster,
            'type' => 'poster',
        ];
        $image_stuffs->insert($values);
    }
    if (empty($poster)) {
        $poster = get_poster($imdbid);
    }

    $imdb = [
        'title' => 'Title',
        'mpaa_reason' => 'MPAA',
        'country' => 'Country',
        'language' => 'Language',
        'director' => 'Directors',
        'writing' => 'Writers',
        'producer' => 'Producer',
        'plot' => 'Description',
        'composer' => 'Music',
        'plotoutline' => 'Plot Outline',
        'storyline' => 'Stroyline',
        'trailers' => 'Trailers',
        'genres' => 'All genres',
        'rating' => 'Rating',
        'top250' => 'Top 250',
        'aspect_ratio' => 'Aspect Ratio',
        'year' => 'Year',
        'runtime' => 'Runtime',
        'votes' => 'Votes',
        'critics' => 'Critic Rating',
        'movietype' => 'Type',
        'updated' => 'Last Updated',
        'cast' => 'Cast',
    ];
    $imdb_data['cast'] = array_slice($imdb_data['cast'], 0, 25);
    foreach ($imdb_data['cast'] as $pp) {
        if (!empty($pp['name']) && !empty($pp['photo'])) {
            $realname = $birthday = $died = $birthplace = $history = '';
            $bio = get_imdb_person($pp['imdb']);
            if (!empty($bio['realname'])) {
                $realname =  "
                                                        <div class='column padding5 is-4'>
                                                            <span class='size_4 has-text-primary'>Real Name:</span>
                                                        </div>
                                                        <div class='column padding5 is-8'>
                                                            <span class='size_4'>{$bio['realname']}</span>
                                                        </div>" ;
            }
            if (!empty($bio['birthday'])) {
				$birthdate = date("F j, Y", strtotime($bio['birthday']));
				$birthday = "
                                                        <div class='column padding5 is-4'>
                                                            <span class='size_4 has-text-primary'>Birthdate:</span>
                                                        </div>
                                                        <div class='column padding5 is-8'>
                                                            <span class='size_4'>{$birthdate}</span>
                                                        </div>";
			}
            if (!empty($bio['died'])) {
				$died = date("F j, Y", strtotime($bio['died']));
				$died = "
                                                        <div class='column padding5 is-4'>
                                                            <span class='size_4 has-text-primary'>Died On:</span>
                                                        </div>
                                                        <div class='column padding5 is-8'>
                                                            <span class='size_4'>{$died}</span>
                                                        </div>";
			}
            if (!empty($bio['birthplace'])) {
                $birthplace = "
                                                        <div class='column padding5 is-4'>
                                                            <span class='size_4 has-text-primary'>Birth Place:</span>
                                                        </div>
                                                        <div class='column padding5 is-8'>
                                                            <span class='size_4'>{$bio['birth_place']}</span>
                                                        </div>";
            }
            if (!empty($bio['bio'])) {
                $stripped = strip_tags($bio['bio']);
                $text = strlen($stripped) > 500 ? substr($stripped, 0, 500) . '...' : $stripped;
                $history = "
                                                        <div class='column padding5 is-4'>
                                                            <span class='size_4 has-text-primary'>Bio:</span>
                                                        </div>
                                                        <div class='column padding5 is-8'>
                                                            <span class='size_4'>{$text}</span>
                                                        </div>";
            }

            $cast[] = "
                            <span class='padding5'>
                                <a href='" . url_proxy("https://www.imdb.com/name/nm{$pp['imdb']}") . "' target='_blank'>
                                    <span class='dt-tooltipper-large' data-tooltip-content='#cast_{$pp['imdb']}_tooltip'>
                                        <span class='cast'>
                                            <img src='" . url_proxy(strip_tags($pp['photo']), true, null, 110) . "' class='round5'>
                                        </span>
                                        <div class='tooltip_templates'>
                                            <div id='cast_{$pp['imdb']}_tooltip'>
                                                <div class='tooltip-torrent padding10'>
													<div class='columns is-marginless is-paddingless'>
														<div class='column padding10 is-4'>
                                                            <span>
                                                                <img src='" . url_proxy(strip_tags($pp['photo']), true, 250) . "' class='tooltip-poster'>
                                                            </span>
														</div>
														<div class='column paddin10 is-8'>
                                                            <span>
                                                                <div class='columns is-multiline'>
                                                                    <div class='column padding5 is-4'>
                                                                        <span class='size_4 has-text-primary'>Name:</span>
                                                                    </div>
                                                                    <div class='column padding5 is-8'>
                                                                        <span class='size_4'>{$pp['name']}</span>
                                                                    </div>
                                                                    <div class='column padding5 is-4'>
                                                                        <span class='size_4 has-text-primary'>Role:</span>
                                                                    </div>
                                                                    <div class='column padding5 is-8'>
                                                                        <span class='size_4'>{$pp['role']}</span>
                                                                    </div>{$realname}{$birthday}{$died}{$birthplace}{$history}
                                                                </div>
                                                            </span>
														</div>
													</div>
                                                </div>
                                            </div>
                                        </div>
                                    </span>
                                </a>
                            </span>";
        }
    }

    $imdb_info = '';
    foreach ($imdb as $foo => $boo) {
        if (!empty($imdb_data[$foo])) {
            if (!is_array($imdb_data[$foo])) {
                $imdb_data[$foo] = $boo === 'Title' ? "<a href='{$site_config['baseurl']}/browse.php?search_imdb={$imdbid}' class='tooltipper' title='Browse by IMDb'>{$imdb_data[$foo]}</a>" : $imdb_data[$foo];
                if ($boo === 'Rating') {
                    $percent = $imdb_data['rating'] * 10;
                    $imdb_data[$foo] = "
                        <span class='is-flex'>
                            <div class='right10'>{$imdb_data['rating']}</div>
                            <div class='star-ratings-css tooltipper' title='{$percent}% out of {$imdb_data['votes']} votes!'>
                                <div class='star-ratings-css-top' style='width: {$percent}%'><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
                                <div class='star-ratings-css-bottom'><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
                            </div>
                        </span>";
                } elseif ($boo === 'Year') {
                    $year = 'Search by year: ' . $imdb_data['year'];
                    $imdb_data[$foo] = "<a href='{$site_config['baseurl']}/browse.php?search_year_start={$imdb_data['year']}&amp;search_year_end={$imdb_data['year']}' target='_blank' class='tooltipper' title='$year'>{$imdb_data['year']}</a>";
                } elseif ($boo === 'MPAA') {
                    if (empty($imdb_data['mpaa_reason']) && !empty($imdb_data['mpaa']['United States'])) {
                        $imdb_data['mpaa_reason'] = $imdb_data['mpaa']['United States'];
                    }
                } elseif ($boo === 'Runtime') {
                    $imdb_data['runtime'] = date('G:i', mktime(0, $imdb_data['runtime']));
                }
                $imdb_info .= "
                    <div class='columns'>
                        <span class='has-text-primary column is-2 size_5 padding5'>$boo: </span>
                        <span class='column padding5'>{$imdb_data[$foo]}</span>
                    </div>";
            } elseif (is_array($imdb_data[$foo]) && in_array($foo, [
                    'director',
                    'writing',
                    'producer',
                    'composer',
                    'cast',
                    'trailers',
                ])) {
                foreach ($imdb_data[$foo] as $pp) {
                    if ($foo === 'cast' && !empty($cast)) {
                        $imdb_tmp[] = implode(' ', $cast);
                        unset($cast);
                    }
                    if ($foo === 'trailers') {
                        $imdb_tmp[] = "<a href='" . url_proxy($pp['url']) . "' target='_blank' class='tooltipper' title='IMDb: {$pp['title']}'>{$pp['title']}</a>";
                    } elseif ($foo != 'cast') {
                        $role = !empty($pp['role']) ? ucwords($pp['role']) : 'unidentified';
                        $imdb_tmp[] = "<a href='" . url_proxy("https://www.imdb.com/name/nm{$pp['imdb']}") . "' target='_blank' class='tooltipper' title='$role'>{$pp['name']}</a>";
                    }
                }
            } elseif ($foo === 'genres') {
                foreach ($imdb_data[$foo] as $genre) {
                    $genre_title = 'Search by genre: ' . ucwords($genre);
                    $imdb_tmp[] = "<a href='{$site_config['baseurl']}/browse.php?search_genre=" . urlencode(strtolower($genre)) . "' target='_blank' class='tooltipper' title='$genre_title'>" . ucwords($genre) . '</a>';
                }
            }
            if (!empty($imdb_tmp)) {
                $imdb_info .= "
                    <div class='columns'>
                        <span class='has-text-primary column is-2 size_5 padding5'>$boo: </span>
                        <span class='column padding5'>" . implode(', ', $imdb_tmp) . '</span>
                    </div>';
                unset($imdb_tmp);
            }
        }
    }

    $imdb_info = preg_replace('/&(?![A-Za-z0-9#]{1,7};)/', '&amp;', $imdb_info);
    if ($title) {
        $imdb_info = "
        <div class='padding20'>
            <div class='columns bottom20'>
                <div class='column is-one-third'>
                    <img src='" . placeholder_image('450') . "' data-src='" . url_proxy($poster, true, 450) . "' class='lazy round10 img-polaroid'>
                </div>
                <div class='column'>
                    $imdb_info
                </div>
            </div>
        </div>";
        $cache->set('imdb_fullset_title_' . $imdbid, $imdb_info, 604800);
    } else {
        $imdb_info = "<div class='padding20'>$imdb_info</div>";
    }

    return [
        $imdb_info,
        $poster,
    ];
}

/**
 * @param $imdb_id
 *
 * @return bool|mixed
 *
 * @throws Exception
 */
function get_imdb_title($imdb_id)
{
    global $BLOCKS;

    if (!$BLOCKS['imdb_api_on']) {
        return false;
    }

    $imdbid = $imdb_id;
    $imdb_id = str_replace('tt', '', $imdb_id);
    $imdb_data = get_imdb_info($imdb_id, true, true);
    if (empty($imdb_data['title'])) {
        return false;
    }

    return $imdb_data['title'];
}

/**
 * @param $imdb_id
 *
 * @return bool|null|string|string[]
 *
 * @throws Exception
 */
function get_imdb_info_short($imdb_id)
{
    global $cache, $BLOCKS, $site_config, $image_stuffs;

    if (!$BLOCKS['imdb_api_on']) {
        return false;
    }

    $imdbid = $imdb_id;
    $imdb_id = str_replace('tt', '', $imdb_id);
    $imdb_data = get_imdb_info($imdb_id, true, true);
    if (empty($imdb_data)) {
        return false;
    }
    $poster = $placeholder = '';

    if (empty($imdb_data['poster'])) {
        $poster = getMovieImagesByID($imdbid, 'movieposter');
        $imdb_data['poster'] = $poster;
    }
    if (empty($imdb_data['poster'])) {
        $tmdbid = get_movie_id($imdbid, 'tmdb_id');
        if (!empty($tmdbid)) {
            $poster = getMovieImagesByID($tmdbid, 'movieposter');
            $imdb_data['poster'] = $poster;
        }
    }
    if (empty($imdb_data['poster'])) {
        $omdb = get_omdb_info($imdbid, true, true);
        if (!empty($omdb['Poster']) && $omdb['Poster'] != 'N/A') {
            $imdb_data['poster'] = $omdb['Poster'];
        }
    }
    if (!empty($imdb_data['poster'])) {
        $image = url_proxy($imdb_data['poster'], true, 250);
        if ($image) {
            $imdb_data['poster'] = $image;
            $imdb_data['placeholder'] = url_proxy($imdb_data['poster'], true, 250, null, 20);
        }
        $values = [];
        if (!empty($tmdbid)) {
            $values = [
                'tmdb_id' => $tmdbid,
            ];
        }
        $values = array_merge($values, [
            'imdb_id' => $imdbid,
            'url' => $poster,
            'type' => 'poster',
        ]);
        $image_stuffs->insert($values);
    }
    if (empty($imdb_data['poster'])) {
        $poster = $site_config['pic_baseurl'] . 'noposter.png';
        $imdb_data['poster'] = $poster;
        $imdb_data['placeholder'] = $poster;
    }
    if (!empty($imdb_data['critics'])) {
        $imdb_data['critics'] .= '%';
    } else {
        $imdb_data['critics'] = '?';
    }
    if (empty($imdb_data['vote_count'])) {
        $imdb_data['vote_count'] = '?';
    }
    if (empty($imdb_data['rating'])) {
        $imdb_data['rating'] = '?';
    }
    if (empty($imdb_data['mpaa_reason']) && !empty($imdb_data['mpaa']['United States'])) {
        $imdb_data['mpaa_reason'] = $imdb_data['mpaa']['United States'];
    }

    $imdb_info = "
            <div class='padding10 round10 bg-00 margin10'>
                <div class='dt-tooltipper-large has-text-centered' data-tooltip-content='#movie_{$imdb_data['id']}_tooltip'>
                    <img src='{$imdb_data['placeholder']}' data-src='{$imdb_data['poster']}' alt='Poster' class='lazy tooltip-poster'>
                    <div class='has-text-centered top10'>{$imdb_data['title']}</div>
                    <div class='tooltip_templates'>
                        <div id='movie_{$imdb_data['id']}_tooltip' class='round10 tooltip-background'>
                            <div class='is-flex tooltip-torrent bg-09'>
                                <span class='padding10 w-40'>
                                    <img src='{$imdb_data['placeholder']}' data-src='{$imdb_data['poster']}' alt='Poster' class='lazy tooltip-poster'>
                                </span>
                                <span class='padding10'>
                                    <div>
                                        <span class='size_5 right10 has-text-primary has-text-bold'>Title: </span>
                                        <span>" . htmlsafechars($imdb_data['title']) . "</span>
                                    </div>
                                    <div>
                                        <span class='size_5 right10 has-text-primary'>MPAA: </span>
                                        <span>" . htmlsafechars($imdb_data['mpaa_reason']) . "</span>
                                    </div>
                                    <div>
                                        <span class='size_5 right10 has-text-primary'>Critics: </span>
                                        <span>" . htmlsafechars($imdb_data['critics']) . "</span>
                                    </div>
                                    <div>
                                        <span class='size_5 right10 has-text-primary'>Rating: </span>
                                        <span>" . htmlsafechars($imdb_data['rating']) . "</span>
                                    </div>
                                    <div>
                                        <span class='size_5 right10 has-text-primary'>Votes: </span>
                                        <span>" . htmlsafechars($imdb_data['vote_count']) . "</span>
                                    </div>
                                    <div>
                                        <span class='size_5 right10 has-text-primary'>Overview: </span>
                                        <span>" . htmlsafechars(strip_tags($imdb_data['plotoutline'])) . '</span>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';

    $imdb_info = preg_replace('/&(?![A-Za-z0-9#]{1,7};)/', '&amp;', $imdb_info);

    return $imdb_info;
}

/**
 * @return array|bool
 *
 * @throws Exception
 */
function get_upcoming()
{
    global $cache, $BLOCKS;

    if (!$BLOCKS['imdb_api_on']) {
        return false;
    }

    $imdb_data = $cache->get('imdb_upcoming_');
    if ($imdb_data === false || is_null($imdb_data)) {
        $url = 'https://www.imdb.com/movies-coming-soon/';
        $imdb_data = fetch($url);
        if ($imdb_data) {
            $cache->set('imdb_upcoming_', $imdb_data, 86400);
        } else {
            $cache->set('imdb_upcoming_', 'failed', 3600);
        }
    }

    if (empty($imdb_data)) {
        return false;
    }

    preg_match_all('/<h4.*<a name=.*>(.*)&nbsp;/i', $imdb_data, $timestamp);
    $dates = $timestamp[1];
    $regex = '';
    foreach ($dates as $date) {
        $regex .= '<a name(.*)';
    }
    $regex .= 'see-more';
    preg_match("/$regex/isU", $imdb_data, $datemovies);
    $temp = [];
    foreach ($datemovies as $key => $value) {
        preg_match_all('/<table(.*)<\/table/isU', $value, $out);
        if ($key != 0) {
            $temp[$dates[$key - 1]] = $out[1];
        }
    }
    $imdbs = [];
    foreach ($dates as $date) {
        foreach ($temp[$date] as $code) {
            preg_match('/title\/(tt[\d]{7})/i', $code, $imdb);
            if (!empty($imdb[1])) {
                $imdbs[$date][] = $imdb[1];
            }
        }
    }

    if (!empty($imdbs)) {
        foreach ($imdbs as $day) {
            foreach ($day as $imdb) {
                get_imdb_info($imdb);
            }
        }

        return $imdbs;
    }

    return false;
}

/**
 * @return bool|mixed
 *
 * @throws \Envms\FluentPDO\Exception
 */
function get_random_useragent()
{
    global $fluent, $cache, $site_config, $BLOCKS;

    if (!$BLOCKS['imdb_api_on']) {
        return false;
    }

    $browser = $cache->get('browser_user_agents_');
    if ($browser === false || is_null($browser)) {
        $results = $fluent->from('users')
            ->select(null)
            ->select('DISTINCT browser')
            ->limit(100);
        $browser = [];
        foreach ($results as $result) {
            preg_match('/Agent : (.*)/', $result['browser'], $match);
            if (!empty($match[1])) {
                $browser[] = $match[1];
            }
        }
        $cache->set('browser_user_agents_', $browser, $site_config['expires']['browser_user_agent']);
    }

    if (!empty($browser)) {
        shuffle($browser);
    } else {
        $browser[] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36';
    }

    return $browser[0];
}

function update_torrent_data(string $imdb_id)
{
    global $BLOCKS, $fluent, $cache, $site_config;

    if (!$BLOCKS['imdb_api_on']) {
        return false;
    }

    $imdb_id = str_replace('tt', '', $imdb_id);
    $imdb_data = get_imdb_info($imdb_id, true, true);
    $set = [];
    if (!empty($imdb_data['newgenre'])) {
        $set = [
            'newgenre' => $imdb_data['newgenre'],
        ];
    }
    $set = array_merge($set, [
        'year' => $imdb_data['year'],
        'rating' => $imdb_data['rating'],
    ]);
    $result = $fluent->update('torrents')
        ->set($set)
        ->where('imdb_id = ?', 'tt' . $imdb_id)
        ->execute();

    if ($result) {
        $torrents = $fluent->from('torrents')
            ->select(null)
            ->select('id')
            ->where('imdb_id = ?', 'tt' . $imdb_id)
            ->fetchAll();

        foreach ($torrents as $torrent) {
            $cache->update_row('torrent_details_' . $torrent['id'], $set, $site_config['expires']['torrent_details']);
        }
    }
}

function get_imdb_person($person_id)
{
    global $BLOCKS, $cache, $fluent, $site_config;

    if (!$BLOCKS['imdb_api_on']) {
        return false;
    }

    $imdb_person = $cache->get('imdb_person_' . $person_id);
    if ($imdb_person === false || is_null($imdb_person)) {
        $imdb_person = $fluent->from('person')
            ->where('imdb_id = ?', $person_id)
            ->where('updated + 2592000 > ?', TIME_NOW)
            ->fetch();

        if (!empty($imdb_person)) {
            return $imdb_person;
        }

        $config = new Config();
        $config->language = 'en-US';
        $config->cachedir = IMDB_CACHE_DIR;
        $config->throwHttpExceptions = 0;
        $config->default_agent = get_random_useragent();

        $person = new \Imdb\Person($person_id, $config);
        $update = $imdb_person = [];
        if (!empty($person->name())) {
            $imdb_person['name'] = $person->name();
        } else {
            return false;
        }

        if (!empty($person->birthname())) {
            $imdb_person['realname'] = $person->birthname();
        }

        if (!empty($person->born())) {
            $data = $person->born();
            if (!empty($data['year']) && !empty($data['mon']) && !empty($data['day'])) {
                $imdb_person['birthday'] = $data['year'] . '-' . str_pad($data['mon'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($data['day'], 2, '0', STR_PAD_LEFT);
            }
            if (!empty($data['place'])) {
                $imdb_person['birth_place'] = $data['place'];
            }
        }

        if (!empty($person->bio())) {
            $data = $person->bio();
            $imdb_person['bio'] = str_replace(['<br />', 'href="'], ['<br>', 'href="' . $site_config['anonymizer_url']], $data[0]['desc']);
        }

        if (!empty($person->died())) {
            $data = $person->died();
            if (!empty($data['year']) && !empty($data['mon']) && !empty($data['day'])) {
                $imdb_person['died'] = $data['year'] . '-' . str_pad($data['mon'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($data['day'], 2, '0', STR_PAD_LEFT);
            }
        }

        if (!empty($person->photo(false))) {
            $data = $person->photo(false);
            $imdb_person['photo'] = $data;
        }

        $imdb_person['updated'] = TIME_NOW;
        $update = $imdb_person;
        unset($update['name']);

        $fluent->insertInto('person', $imdb_person)
            ->onDuplicateKeyUpdate($update)
            ->execute();

        $cache->set('imdb_person_' . $person_id, $imdb_person, 604800);
    }

    return $imdb_person;
}

function get_top_movies()
{
    global $cache;

    $top = $cache->get('imdb_top_movies_');
    if ($top === false || is_null($top)) {
        $top = [];
        for ($i = 1; $i <= 1000; $i += 50) {
            $url = 'https://www.imdb.com/search/title?groups=top_1000&sort=user_rating,desc&start=' . $i;
            $html = fetch($url);
            preg_match_all('/(tt\d{7})/', $html, $matches);
            foreach ($matches[1] as $match) {
                if (!in_array($match, $top)) {
                    $top[] = $match;
                }
            }
        }
        if (!empty($top)) {
            $cache->set('imdb_top_movies_', $top, 604800);
        }
    }

    return $top;
}

function get_top_tvshows()
{
    global $cache;

    $top = $cache->get('imdb_top_tvshows_');
    if ($top === false || is_null($top)) {
        $top = [];
        for ($i = 1; $i <= 350; $i += 50) {
            $url = 'https://www.imdb.com/search/title?title_type=tv_series&num_votes=30000,&countries=us&sort=user_rating,desc&start=' . $i;
            $html = fetch($url);
            preg_match_all('/(tt\d{7})/', $html, $matches);
            foreach ($matches[1] as $match) {
                if (!in_array($match, $top)) {
                    $top[] = $match;
                }
            }
        }
        if (!empty($top)) {
            $cache->set('imdb_top_tvshows_', $top, 604800);
        }
    }

    return $top;
}

function get_top_anime()
{
    global $cache;

    $top = $cache->get('imdb_top_anime_');
    if ($top === false || is_null($top)) {
        $top = [];
        for ($i = 1; $i <= 350; $i += 50) {
            $url = 'https://www.imdb.com/search/title?genres=drama&keywords=anime&num_votes=2000,sort=user_rating,desc&start=' . $i;
            $html = fetch($url);
            preg_match_all('/(tt\d{7})/', $html, $matches);
            foreach ($matches[1] as $match) {
                if (!in_array($match, $top)) {
                    $top[] = $match;
                }
            }
        }
        if (!empty($top)) {
            $cache->set('imdb_top_anime_', $top, 604800);
        }
    }

    return $top;
}
