<?php

include __DIR__."/autoloader.php";

$shortopts = "sde";
$longopts = ['scrap', 'download', 'exportFile'];

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

if (isset($options['e']))
{
    (new ExportToCsv(__DIR__.'/file.csv'))->run();
}