<?php
  if (!settings::get('box_popular_products_num_items')) return;

  functions::draw_lightbox();

  $box_popular_products_cache_token = cache::token('box_popular_products', array('language', 'currency', 'prices'), 'file');
  if (cache::capture($box_popular_products_cache_token)) {

    $products_query = functions::catalog_products_query(array(
      'sort' => 'popularity',
      'limit' => settings::get('box_popular_products_num_items')*2,
    ));

    if (database::num_rows($products_query)) {

      $listing_products = array();
      while ($listing_product = database::fetch($products_query)) {
        $listing_products[] = $listing_product;
      }

      shuffle($listing_products);

      $listing_products = array_slice($listing_products, 0, settings::get('box_popular_products_num_items'));

      $box_popular_products = new ent_view();

      $box_popular_products->snippets['products'] = array();
      foreach ($listing_products as $listing_product) {
        $box_popular_products->snippets['products'][] = $listing_product;
      }

      echo $box_popular_products->stitch('views/box_popular_products');
    }

    cache::end_capture($box_popular_products_cache_token);
  }
