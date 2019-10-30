<?php
include(dirname(__FILE__).'/config/config.inc.php');
include(_PS_ROOT_DIR_.'/init.php');

header("Content-Type:text/xml; charset=utf-8");

$store_name = "www.STORE_NAME.gr";

$configuration = Configuration::getMultiple(array(
  'PS_LANG_DEFAULT',
  'PS_SHIPPING_FREE_PRICE',
  'PS_SHIPPING_HANDLING',
  'PS_SHIPPING_METHOD',
  'PS_SHIPPING_FREE_WEIGHT',
  'PS_CARRIER_DEFAULT'));

$id_lang = intval($configuration['PS_LANG_DEFAULT']);
$link = new Link();

/**
* Return available categories.
*
* @param bool|int $idLang Language ID
* @param bool $active Only return active categories
* @param bool $order Order the results
* @param string $sqlFilter Additional SQL clause(s) to filter results
* @param string $orderBy Change the default order by
* @param string $limit Set the limit
*                      Both the offset and limit can be given
*
* @return array Categories
*
* public static function getCategories($idLang = false, $active = true, $order = true, $sqlFilter = '', $orderBy = '', $limit = '')
*
*/
$categories = Category::getCategories($id_lang, false, false);

/**
* Get all available products.
*
* @param int $id_lang Language id
* @param int $start Start number
* @param int $limit Number of products to return
* @param string $order_by Field for ordering
* @param string $order_way Way for ordering (ASC or DESC)
*
* @return array Products details
*
* public static function getProducts($id_lang, $start, $limit, $order_by, $order_way, $id_category = false, $only_active = false, Context $context = null)
*
*/
$products = Product::getProducts($id_lang, 0, 0, 'price', 'ASC', false, true);

$allCategories = array();
foreach ($categories as $category) {
  $allCategories[$category['id_category']] = $category;
}

print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
print "<".$store_name.">";
print "<created_at>".date('Y-m-d G:i:s')."</created_at>";
print "<products>";
foreach ($products as $product){
	
	$quantity = Product::getQuantity($product['id_product']);
    
    //Export only active product with quantity
	if($product['active'] == 1 && $quantity > 0)
	{
 		
   		$product['link'] = $link->getProductLink($product['id_product']);
        	$product['price_inc'] = $product['price'];
	        $product['price_inc'] = number_format(round($product['price_inc'], 2), 2);
	        $cover = Product::getCover($product['id_product']);
        
		$product['imageUrl'] = 'https://'.$link->getImageLink($product['link_rewrite'], $cover['id_image'], 'full_default');
	  
        print "<product>";
        print "<id>".$product['id_product']."</id>";
        print "<name><![CDATA[".$product['manufacturer_name']." ".$product['name']." ".$product["reference"]."]]></name>";
        print "<link><![CDATA[".$product['link']."]]></link>";
        print "<image><![CDATA[". $product['imageUrl']."]]></image>";

        //Start Categories
        $categoryTree = "";
        $currentCategory = $allCategories[$product['id_category_default']];
        while($currentCategory['id_category'] > 2)
        {
            $categoryTree.= $currentCategory['name'].'|';
            $currentCategory = $allCategories[$currentCategory['id_parent']];
        }
        $FullCategoryPath = implode(' > ', array_reverse(explode('|', substr($categoryTree, 0, -1))));
        echo "<category><![CDATA[".$FullCategoryPath."]]></category>";          
        //End Categories

        //Start Price
        $price = Product::getPriceStatic($product['id_product'],true,null,2);
        print "<price><![CDATA[".$price."]]></price>";
        //End Price
        
        //Start Manufacturer
        if($product['manufacturer_name']=='')
	{ 
		$product['manufacturer_name'] = "OEM";
	}
        print "<manufacturer><![CDATA[".$product['manufacturer_name']."]]></manufacturer>";
        //End Manufacturer

        //Start Availability
        $quantity = Product::getQuantity($product['id_product']);
        $out_of_stock = StockAvailable::outOfStock($product['id_product']);
        if($out_of_stock == 1) 
	{
	        print "<instock>Y</instock>";
        } 
	else 
	{
            $instock = ($quantity>=1)? 'Y' :'N' ;
            print "<instock>".$instock."</instock>";
        }        
        if ($quantity >= 1) 	
	{
            $availability = 'Διαθέσιμο';        
	} 
	else if ($out_of_stock==1)
	{
            $availability = ($product['available_later']!='') ? $product['available_later'] :'Διαθέσιμο';
        } 
	else 
	{
            $availability = ($product['available_later']!='') ? $product['available_later'] :'Μη διαθέσιμο';
        }        
        print "<availability>".$availability."</availability>";
        //End Availability

        print "<weight>".ltrim(substr(str_replace(".","",$product["weight"]), 0, -3), '0')."</weight>";
        print "<mpn>".$product["reference"]."</mpn>";
        print "<ean>".$product["ean13"]."</ean>";         
        print "</product>";  		
    }
}
print "</products>";
print "</".$store_name.">";
?>
