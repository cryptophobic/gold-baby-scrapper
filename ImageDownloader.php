<?php

use Utils\EasyDownloader;

/**
 * Created by PhpStorm.
 * User: Lutz
 * Date: 17.03.2019
 * Time: 10:45
 */

class ImageDownloader
{
    const DIR_NAME = __DIR__.'/files/';

    /**
     * @var string
     */
    private $_fileHandler;

    /**
     * @var EasyDownloader
     */
    private $_downloader;

    /**
     * ImageDownloader constructor.
     * @param string $feedFilePath
     * @throws Exception
     */
    public function __construct($feedFilePath)
    {
        if (file_exists($feedFilePath))
        {
            $this->_fileHandler = fopen($feedFilePath, 'r');
            $this->_downloader = new EasyDownloader();

        } else {
            throw new Exception("file is absent");
        }
    }

    /**
     * Run Forest Run
     */
    public function run()
    {
        while (($data = fgetcsv($this->_fileHandler, 40000))){
            $url = $data[2];
            $result = parse_url($url, PHP_URL_PATH);
            $extension = pathinfo($result, PATHINFO_EXTENSION);
            $fileName = static::DIR_NAME.$data[6].'.'.$extension;
            print("\n".$url.' ');
            try {
                $this->_downloader->downloadFile($data[2], $fileName);
            }
            catch (\Exception $e)
            {
                print_r("FAILED!!");
            }
            sleep(10);
        }
    }
}