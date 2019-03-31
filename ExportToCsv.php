<?php

/**
 * Class ExportToCsv
 */
class ExportToCsv
{

    const MANDATORY = ['Brand', 'Category', 'Product name', 'Price', 'Description long', 'Description short', 'Stock', 'Public'];

    const EXTRA_SPECS = ['Specifications', 'Производитель'];

    /**
     * @var resource
     */
    private $_filePath;

    /**
     * ExportToCsv constructor.
     * @param $feedFilePath
     * @throws Exception
     */
    public function __construct($feedFilePath)
    {
        if (file_exists($feedFilePath))
        {
            $this->_filePath = $feedFilePath;
        } else {
            throw new Exception("file is absent");
        }
    }

    /**
     * Collect and filter all specs
     */
    private function _getAllSpecs()
    {
        $keyValue = [];
        $fileHandler = fopen($this->_filePath, 'r');
        while (($data = fgetcsv($fileHandler, 40000))) {
            $keyValue[] = $this->_parseSpecifications($data[9]);
        }
        $keys = array_merge(...$keyValue);
        fclose($fileHandler);
        return array_keys($keys);
    }

    /**
     * @param $specsString
     * @return array
     */
    private function _parseSpecifications($specsString)
    {
        $result = [];
        $specs = $specsString;
        $specsArray = explode(",", $specs);
        foreach ($specsArray as $specsPair) {
            $keyValue = explode('=', $specsPair);
            if (count($keyValue) === 2) {
                $key = mb_convert_case(trim($keyValue[0]), MB_CASE_TITLE, "UTF-8");
                if (!empty($key) && !in_array($key, static::EXTRA_SPECS)) {
                    $value = trim($keyValue[1]);
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     *
     */
    public function run()
    {
        $specs = $this->_getAllSpecs();
        $fileHandler = fopen($this->_filePath, 'r');
        $exportFileHandler = fopen(__DIR__.'/fileResult.csv', 'w');

        $offset = count(static::MANDATORY);

        $specsFlipped = array_flip($specs);
        $specsFlipped = array_map(function ($item) use ($offset) {
            return $item + $offset;
        }, $specsFlipped);

        $specs = array_map(function ($item) {
            return "Feature input $item";
        }, $specs);

        $header = array_merge(static::MANDATORY, $specs, ['Image 1']);
        fputcsv($exportFileHandler, $header);
        $countColumns = count($header);
        $data = fgetcsv($fileHandler, 40000);
        while (($data = fgetcsv($fileHandler, 40000))) {

            $row = array_fill(0, $countColumns-1, '');
            $keyValue = $this->_parseSpecifications($data[9]);

            $row[0] = $data[0];
            $row[1] = $data[1];
            $row[2] = $data[3];
            $row[3] = $data[7];
            $row[4] = $data[8];
            $row[6] = $data[5] === 'В наличии'?1:0;
            $row[7] = 1;

            foreach ($keyValue as $key => $value)
            {
                $offset = $specsFlipped[$key];
                $row[$offset] = $value;
            }
            $row[$countColumns-1] = 'https://active-kids.com.ua/upload/images/'.$data[6].'.jpg';

            fputcsv($exportFileHandler, $row);
        }
        fclose($exportFileHandler);
    }
}