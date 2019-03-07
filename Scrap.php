<?php

include 'simplehtmldom/simple_html_dom.php';

class Scrap {

    public $_brands = [];

    public $_features = [];

    public $_categories = [];

    public function run()
    {
        $dom = new \simple_html_dom();
        $fp = fopen('file.csv', 'w');
        fputcsv($fp, ['brand', 'category', 'image', 'title', 'sku', 'instock', 'productId', 'price', 'description', 'specifications']);

        for ($page = 1; $page <= 86; $page++) {
            $dom->load_file('https://gold-baby.net/product_list/page_'.$page);
            $goods = $dom->find('a.b-product-line__buy-button');
            var_dump($page);
            foreach ($goods as $product) {
                $prop = 'data-product-url';
                $this->parseProduct($fp, $product->$prop);
                //$this->parseProduct('https://gold-baby.net/p494918020-konstruktor-lipuchka-bunchemsbanchems.html');
                sleep(10);
            }
        }
        fclose($fp);
        $fp = fopen('stats', 'w');
        $statsString = print_r([$this->_brands, $this->_categories], true);
        fwrite($fp, $statsString);
        fclose($fp);
        var_dump($statsString);
    }

    public function parseProduct($fp, $url)
    {
        var_dump($url);
        $dom = new \simple_html_dom();
        $dom->load_file($url);
        $breadCrumbs = $dom->find('li.b-path__item');
        if (!empty($breadCrumbs[2])) {
            $categoryName = trim($breadCrumbs[2]->plaintext);
        } else {
            $categoryName = 'others';
        }
        $container = $dom->find('div.b-product__zoom-box');
        if (empty($container[0]))
        {
            return;
        }

        $imageUrl = $container[0]->{'data-imagezoom-url'};
        $titleDom = $container[0]->find('a');
        if (!empty($titleDom[0]))
        {
            $title = $titleDom[0]->title;
        } else {
            $title = '';
        }
        $sku = '';
        $skuDom = $dom->find('span.b-product__sku');
        if (!empty($skuDom[0])) {
            $sku = trim(str_replace("Код: ", '', $skuDom[0]->title));
        }

        $inStockDom = $dom->find('span.b-sticky-panel__product-status');
        if (!empty($inStockDom[0])) {
            $inStock = trim($inStockDom[0]->plaintext);
        } else {
            $inStock = '';
        }

        $buttonDom = $dom->find('a.b-button-colored');
        if (!empty($buttonDom[0]))
        {
            $button = $buttonDom[0];
            $productId = $button->{'data-product-id'};
            $sku = empty($sku) ? $productId : $sku;
            $price = trim(preg_replace('/[^0-9.]/', '', $button->{'data-product-price'}), '.');
        } else {
            return;
        }

        $descriptionDom = $dom->find('div.b-user-content');
        if (!empty($descriptionDom[0]))
        {
            $description = nl2br(trim($descriptionDom[0]->plaintext));
        } else {
            $description = '';
        }

        $specs = $this->parseSpecs($dom->find('td.b-product-info__cell'), $categoryName);
        //var_dump($imageUrl, $title, $sku, $inStock, $productId, $price, $description, $specs);
        $supplierName = empty($specs['производитель']) ? 'Active Kids' : $specs['производитель'];
        $specsString = '';
        foreach ($specs as $key => $value)
        {
            $specsString .= "$key = $value,";
        }
        fputcsv($fp, [$supplierName, $categoryName, $imageUrl, $title, $sku, $inStock, $productId, $price, $description, $specsString]);
        unset($dom);
    }

    /**
     * @param simple_html_dom_node[] $specsArray
     * @param $categoryName
     * @return array
     */
    public function parseSpecs($specsArray, $categoryName)
    {
        $specs = [];
        $arrayCount = count($specsArray);
        if ($arrayCount % 2 !== 0)
        {
            $specsArray[] = '';
            $arrayCount++;
        }

        for ($i = 0; $i < $arrayCount; $i += 2)
        {
            $key = mb_strtolower(trim(str_ireplace('&nbsp;', '', $specsArray[$i]->plaintext)));
            $value = trim(str_ireplace('&nbsp;', '', $specsArray[$i+1]->plaintext));
            if ($key === 'производитель') {
                $this->_brands[$value] = empty($this->_brands[$value]) ? 1 : $this->_brands[$value] + 1;
            }
            $specs[$key] = $value;

            $this->_categories[$categoryName] =
                empty($this->_categories[$categoryName])
                    ? $specs
                    : array_merge($this->_categories[$categoryName], $specs);

        }
        return $specs;
    }
}

(new Scrap())->run();