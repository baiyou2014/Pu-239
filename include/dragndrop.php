<?php

use Blocktrail\CryptoJSAES\CryptoJSAES;

/**
 * @param $text
 *
 * @return string
 */
function encrypt($text)
{
    global $PICSALT;

    $encrypted = CryptoJSAES::encrypt($text, $PICSALT);

    return base64_encode($encrypted);
}

/**
 * @param $text
 *
 * @return string
 */
function decrypt($text)
{
    global $PICSALT;

    $str = base64_decode($text);

    return CryptoJSAES::decrypt($str, $PICSALT);
}

/**
 * @param $root
 * @param $input
 *
 * @return bool|string|null
 */
function valid_path($root, $input)
{
    $fullpath = $root . $input;
    $fullpath = realpath($fullpath);
    $root = realpath($root);
    $rl = strlen($root);

    return ($root != substr($fullpath, 0, $rl)) ? null : $fullpath;
}

/**
 * @param $path
 */
function make_year($path)
{
    $dir = $path . '/' . date('Y');
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

/**
 * @param $path
 */
function make_month($path)
{
    $dir = $path . '/' . date('Y/m');
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}
