<?php

include 'simplehtmldom/simple_html_dom.php';

$dom = new \simple_html_dom();
$fp = fopen('file_price.csv', 'w');
fputcsv($fp, ['productId', 'Price', 'onStock']);

for ($page = 1; $page <= 96; $page++) {
    $dom->load_file('https://gold-baby.net/product_list/page_'.$page);
    $goods = $dom->find('div.b-product-line_type_gallery');
    var_dump($page);
    foreach ($goods as $product) {
        $id_product = 'data-product-id';
        $productId = $product->$id_product;
        $price = $product->children(2)->children(1)->children(0)->plaintext;
        $onStock = $product->children(2)->children(2)->children(0)->plaintext;
        fputcsv($fp, [$productId, $price, $onStock]);   
        sleep(5);
    }
}
fclose($fp);

