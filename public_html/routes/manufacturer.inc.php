<?php
  if (empty($_GET['manufacturer_id'])) {
    header('Location: '. document::ilink('manufacturers'));
    exit;
  }
  
  if (empty($_GET['page'])) $_GET['page'] = 1;
  if (empty($_GET['sort'])) $_GET['sort'] = 'popularity';
  
  document::$snippets['head_tags']['canonical'] = '<link rel="canonical" href="'. document::href_ilink(null, array(), array('manufacturer_id')) .'" />';
  
  functions::draw_fancybox("a.fancybox[data-fancybox-group='product-listing']");
  
  include vqmod::modcheck(FS_DIR_HTTP_ROOT . WS_DIR_INCLUDES . 'column_left.inc.php');
  
  $manufacturer = new ref_manufacturer($_GET['manufacturer_id']);
  
  if (empty($manufacturer->status)) {
    notices::add('errors', language::translate('error_page_not_found', 'The requested page could not be found'));
    header('HTTP/1.1 404 Not Found');
    header('Location: '. document::ilink('manufacturers'));
    exit;
  }
  
  breadcrumbs::add(language::translate('title_manufacturers', 'Manufacturers'), document::ilink('manufacturers'));
  breadcrumbs::add($manufacturer->name);
  
  //document::$snippets['title'] = array(); // reset
  document::$snippets['title'][] = $manufacturer->head_title[language::$selected['code']] ? $manufacturer->head_title[language::$selected['code']] : $manufacturer->name;
  document::$snippets['keywords'] = $manufacturer->meta_keywords[language::$selected['code']] ? $manufacturer->meta_keywords[language::$selected['code']] : $manufacturer->keywords;
  document::$snippets['description'] = $manufacturer->meta_description[language::$selected['code']] ? $manufacturer->meta_description[language::$selected['code']] : $manufacturer->short_description[language::$selected['code']];

  $manufacturer_cache_id = cache::cache_id('box_manufacturer', array('basename', 'get', 'language', 'currency', 'account', 'prices'));
  if (cache::capture($manufacturer_cache_id, 'file')) {
  
    $page = new view();
    
    $page->snippets = array(
      'name' => $manufacturer->name,
      'description' => $manufacturer->description[language::$selected['code']],
      'h1_title' => $manufacturer->h1_title[language::$selected['code']] ? $manufacturer->h1_title[language::$selected['code']] : $manufacturer->name,
      'sort_alternatives' => array(
        'popularity' => language::translate('title_popularity', 'Popularity'),
        'name' => language::translate('title_name', 'Name'),
        'price' => language::translate('title_price', 'Price'),
        'date' => language::translate('title_date', 'Date'),
      ),
      'products' => array(),
      'pagination' => null,
    );
    
    $products_query = functions::catalog_products_query(array(
      'manufacturer_id' => $manufacturer->id,
      'product_groups' => !empty($_GET['product_groups']) ? $_GET['product_groups'] : null,
      'sort' => $_GET['sort']
    ));
    
    if (database::num_rows($products_query) > 0) {
      if ($_GET['page'] > 1) database::seek($products_query, (settings::get('items_per_page', 20) * ($_GET['page']-1)));
      
      $page_items = 0;
      while ($listing_item = database::fetch($products_query)) {
        $page->snippets['products'][] = functions::draw_listing_product($listing_item, 'column');
        
        if (++$page_items == settings::get('items_per_page', 20)) break;
      }
    }
    
    $page->snippets['pagination'] = functions::draw_pagination(ceil(database::num_rows($products_query)/settings::get('items_per_page', 20)));
    
    
    echo $page->stitch('box_manufacturer');
    
    cache::end_capture($manufacturer_cache_id);
  }
?>