<?php

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\system\Controller\ThemeController;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\HttpFoundation\Request;

function drupalrs_form_system_theme_settings_alter(&$form, &$form_state) {

  $theme = \Drupal::theme()->getActiveTheme()->getName();

  $form['drupalrs'] = array(
    '#type' => 'vertical_tabs',
    '#attributes' => array('class' => array('entity-meta')),
    '#weight' => -999,
    '#default_tab' => 'edit-variables',
  );

  $form['debug'] = array(
    '#type' => 'details',
    '#attributes' => array('class' => array('debug')),
    '#title' => t('Debugging & Development'),
    '#weight' => -699,
    '#group' => 'drupalrs',
  );

  $form['drupalrs_block_demo_mode'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable region demo mode <small>(global setting)</small>'),
    '#description' => t('Display demonstration blocks in each theme region to aid in theme development and configuration. When this setting is enabled, ALL site visitors will see the demo blocks. <br /><strong>This should never be enabled on a live site.</strong>'),
    '#default_value' => theme_get_setting('drupalrs_block_demo_mode', $theme),
    '#group' => 'debug',
  );

  $form['twig_cache_status'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable Twig cache<sup style="color: red;"> BETA</sup>'),
    '#default_value' => theme_get_setting('twig_cache_status', $theme),
    '#group' => 'debug',
  );

  $form['#validate'][] = 'drupalrs_disable_twig_cache';

}

function drupalrs_disable_twig_cache(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {

  $real_path = drupal_realpath('');

  $settings_php_path = $real_path . '/sites/default/settings.php';
  $settings_php_path_backup = $real_path . '/sites/default/_settings.php';
  $development_services_yml = $real_path . '/sites/development.services.yml';
  $development_services_yml_backup = $real_path . '/sites/_development.services.yml';
  $example_settings_local_php = $real_path . '/sites/example.settings.local.php';
  $example_settings_local_php_renamed = $real_path . '/sites/default/settings.local.php';

  $cache_status = $form_state->getValue('twig_cache_status');

  chmod($real_path . '/sites/default', 0777);

  if ($cache_status == 0) {
    if (file_exists($example_settings_local_php_renamed)) {
      unlink($example_settings_local_php_renamed);
    }
    drupal_flush_all_caches();
    return NULL;
  }

  // Napravi rezervne kopije postojećih fajlova.
  if (file_exists($settings_php_path) && !file_exists($settings_php_path_backup)) {
    chmod($settings_php_path, 0777);
    copy($settings_php_path, $settings_php_path_backup);
    chmod($settings_php_path_backup, 0777);
  }

  if (file_exists($development_services_yml) && !file_exists($development_services_yml_backup)) {
    chmod($development_services_yml, 0777);
    copy($development_services_yml, $development_services_yml_backup);
    chmod($development_services_yml_backup, 0777);
  }

  // Kopiraj example fajl i preimenuj ga.
  if (file_exists($example_settings_local_php) && !file_exists($example_settings_local_php_renamed)) {
    chmod($example_settings_local_php, 0777);
    copy($example_settings_local_php, $example_settings_local_php_renamed);
    chmod($example_settings_local_php_renamed, 0777);
  }

  // Izmeni settings.php fajl.
  $contents = file_get_contents($settings_php_path);
  $nedles = array(
    "# if (file_exists(__DIR__ . '/settings.local.php')) {",
    "#   include __DIR__ . '/settings.local.php';"
  );
  $haystacks = array(
    "if (file_exists(__DIR__ . '/settings.local.php'))",
    "  include __DIR__ . '/settings.local.php';"
  );
  $contents = str_replace($nedles, $haystacks, $contents);
  file_put_contents($settings_php_path, $contents);

  // Izmeni development.service.yml fajl.
  $contents2 = file_get_contents($development_services_yml);
  if (!preg_match("/twig.config/", $contents2)) {
  // if (strpos($contents2, 'twig.config') !== TRUE) {
    $contents2 .= PHP_EOL;
    $contents2 .= 'parameters:' . PHP_EOL;
    $contents2 .= '  twig.config:' . PHP_EOL;
    $contents2 .= '    debug : true' . PHP_EOL;
    $contents2 .= '    auto_reload: true' . PHP_EOL;
    $contents2 .= '    cache: false' . PHP_EOL;
    file_put_contents($development_services_yml, $contents2);
  }

  // Izmeni settings.php fajl.
  $contents3 = file_get_contents($example_settings_local_php_renamed);
  $nedles = array(
    '# $settings[\'cache\'][\'bins\'][\'render\'] = \'cache.backend.null\';',
    '# $settings[\'cache\'][\'bins\'][\'dynamic_page_cache\'] = \'cache.backend.null\';',
    '$settings[\'extension_discovery_scan_tests\'] = TRUE;'
  );
  $haystacks = array(
    '$settings[\'cache\'][\'bins\'][\'render\'] = \'cache.backend.null\';',
    '$settings[\'cache\'][\'bins\'][\'dynamic_page_cache\'] = \'cache.backend.null\';',
    '$settings[\'extension_discovery_scan_tests\'] = FALSE;'
  );
  $contents3 = str_replace($nedles, $haystacks, $contents3);
  file_put_contents($example_settings_local_php_renamed, $contents3);

  drupal_flush_all_caches();

}
