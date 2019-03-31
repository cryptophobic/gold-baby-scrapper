<?php

include __DIR__."/autoloader.php";

$shortopts = "sd";
$longopts = ['scrap', 'download'];

$options = getopt($shortopts, $longopts);

if (isset($options['s']))
{
    (new Scrap())->run();
}

if (isset($options['d']))
{
    try {
        (new ImageDownloader('./file.csv'))->run();
    } catch (Exception $e) {
        print_r($e);
    }
}

if (isset($options['k']))
{
    (new Scrap())->run_price();
}