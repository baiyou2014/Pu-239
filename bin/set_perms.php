<?php

require_once dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php';
$user = get_current_user();
$group = posix_getgrgid(filegroup(__FILE__))['name'];

$paths = [
    ROOT_DIR,
];

$exts = [
    'php',
    'js',
    'txt',
    'css',
    'md',
    'json',
    'gz',
    'example',
];

$folders = [
    ROOT_DIR . 'dir_list/',
    ROOT_DIR . 'cache/',
    ROOT_DIR . 'torrents/',
    ROOT_DIR . 'uploads/',
    ROOT_DIR . 'include/backup/',
    ROOT_DIR . 'sqlerr_logs/',
    PUBLIC_DIR . 'install/',
    PUBLIC_DIR . 'install/extra/',
    ROOT_DIR . 'logs/',
    CHAT_DIR . 'css/',
    CHAT_DIR . 'js/',
    TEMPLATE_DIR . '1/css/',
    PUBLIC_DIR . 'images/proxy/',
    BITBUCKET_DIR,
];

foreach ($folders as $folder) {
    chmod_r($folder);
}

$i = 0;
foreach ($paths as $path) {
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $name => $object) {
        if (is_file($name)) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            if (in_array($ext, $exts)) {
                if (chmod($name, 0664)) {
                    chown($name, $user);
                    chgrp($name, $group);
                    ++$i;
                }
            }
        }
    }
}

function chmod_r($path)
{
    $dir = new DirectoryIterator($path);
    foreach ($dir as $item) {
        chmod($item->getPathname(), 0775);
        if ($item->isDir() && !$item->isDot()) {
            chmod_r($item->getPathname());
        }
    }
}

echo "$i files processed\n";
