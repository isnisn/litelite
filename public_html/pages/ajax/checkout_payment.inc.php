<?php

  header('X-Robots-Tag: noindex');

  if (empty(cart::$items)) return;

  if (empty(customer::$data['country_code'])) customer::$data['country_code'] = settings::get('default_country_code');

  $payment = new mod_payment();
  $options = $payment->options(cart::$items, currency::$selected['code'], customer::$data);

  if (file_get_contents('php://input') != '' && !empty($_POST['payment'])) {
    list($module_id, $option_id) = explode(':', $_POST['payment']['option_id']);
    $result = $payment->run('before_select', $module_id, $option_id, $_POST);
    if (!empty($result) && (is_string($result) || !empty($result['error']))) {
      notices::add('errors', is_string($result) ? $result : $result['error']);
    } else {
      $payment->select($module_id, $option_id, $_POST);
    }
  }

  if (!empty($payment->data['selected']['id'])) {
    list($module_id, $option_id) = explode(':', $payment->data['selected']['id']);
    if (!isset($options[$module_id]['options'][$option_id]) || !empty($options[$module_id]['options'][$option_id]['error'])) {
      $payment->data['selected'] = array(); // Clear because option is no longer present
    } else {
      $payment->select($module_id, $option_id); // Reinstate a present option
    }
  }

  if (empty($options)) return;

  if (empty($payment->data['selected'])) {
    if ($cheapest_payment = $payment->cheapest(cart::$items, currency::$selected['code'], customer::$data)) {
      $payment->select($cheapest_payment['module_id'], $cheapest_payment['option_id']);
    }
  }

/*
// Hide
  if (count($options) == 1
  && count($options[key($options)]['options']) == 1
  && empty($options[key($options)]['options'][key($options[key($options)]['options'])]['error'])
  && empty($options[key($options)]['options'][key($options[key($options)]['options'])]['fields'])
  && $options[key($options)]['options'][key($options[key($options)]['options'])]['cost'] == 0) return;
*/

  $box_checkout_payment = new ent_view();

  $box_checkout_payment->snippets = array(
    'selected' => !empty($payment->data['selected']) ? $payment->data['selected'] : array(),
    'options' => $options,
  );

  echo $box_checkout_payment->stitch('views/box_checkout_payment');
