<?php

/**
 * @param $dirs
 */
function checkdir(&$dirs)
{
    foreach ($dirs as $dir => $x) {
        if (file_exists($dir) && is_dir($dir)) {
            $dirs[$dir] = 1;
        } else {
            $dirs[$dir] = 0;
        }
    }
}

/**
 * @return string
 */
function nodecheck()
{
    global $root;
    $dirs = [
        $root . 'node_modules/' => 0,
    ];
    checkdir($dirs);
    $continue = true;
    $out = '<fieldset><legend>Nodejs check</legend>';
    foreach ($dirs as $dir => $state) {
        if (!$state) {
            $continue = false;
        }
        $out .= '<div class="' . ($state ? 'readable' : 'notreadable') . '">' . $dir . '</div>';
    }
    if (!$continue) {
        $out .= '<div class="info" style="text-align:center;">Please run "npm install" from ' . $root . '<br><br><input type="button" value="Reload" onclick="window.location.reload()"></div>';
    }
    $out .= '</fieldset>';
    if ($continue) {
        $out .= '
                <div style="text-align:center;">
                    <input type="button" onclick="onClick(4)" value="Next step">
                </div>';
    }

    $out .= '
    <script>
        var processing = 3;
    </script>';

    return $out;
}
