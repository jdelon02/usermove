<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all environments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to ensure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * Place the config directory outside of the Drupal root.
 */
// $config_directories = array(
//   CONFIG_SYNC_DIRECTORY => dirname(DRUPAL_ROOT) . '/config',
// );
// /**
//  * Drupal 8.8 workaround
//  */
$settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config';

/**
 * Define appropriate location for tmp directory
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/114
 *
 */
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  $config['system.file']['path']['temporary'] = $_SERVER['HOME'] .'/tmp';
}



# For Migrations, connect to a D7 database.
$migrate_settings = __DIR__ . "/settings.migrate-on-pantheon.php";
if ( (file_exists($migrate_settings)) && ($_ENV['PANTHEON_ENVIRONMENT'] == 'dev') ) {
    include $migrate_settings;
}

// Require HTTPS across all Pantheon environments
// Check if Drupal or WordPress is running via command line
if (isset($_SERVER['PANTHEON_ENVIRONMENT']) && ($_SERVER['HTTPS'] === 'OFF') && (php_sapi_name() != "cli")) {
  if (!isset($_SERVER['HTTP_USER_AGENT_HTTPS']) || (isset($_SERVER['HTTP_USER_AGENT_HTTPS']) && $_SERVER['HTTP_USER_AGENT_HTTPS'] != 'ON')) {

    header('HTTP/1.0 301 Moved Permanently');
    header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

    // Name transaction "redirect" in New Relic for improved reporting (optional).
    if (extension_loaded('newrelic')) {
      newrelic_name_transaction("redirect");
    }

    exit();
  }
}

// Configure Redis

if (file_exists( __DIR__ . DIRECTORY_SEPARATOR . 'settings.eapps.php')) {
  // Include the Redis services.yml file. Adjust the path if you installed to a contrib or other subdirectory.
  $settings['container_yamls'][] = '/modules/contrib/redis/example.services.yml';

  //phpredis is built into the Pantheon application container.
  $settings['redis.connection']['interface'] = 'PhpRedis';

  if (defined('PANTHEON_ENVIRONMENT')) {
  // These are dynamic variables handled by Pantheon.
    $settings['redis.connection']['host']      = $_ENV['CACHE_HOST'];
    $settings['redis.connection']['port']      = $_ENV['CACHE_PORT'];
    $settings['redis.connection']['password']  = $_ENV['CACHE_PASSWORD'];
    $settings['cache_prefix']['default'] = 'pantheon-redis';
  } else {
      // These are variables for eApps.
      $settings['redis.connection']['host']      = '127.0.0.1';
      $settings['redis.connection']['port']      = 6379;
      $settings['redis.connection']['password']  = 'ceacivid8';
      $settings['cache_prefix']['default'] = 'eapps-redis';
  }
  $settings['cache']['default'] = 'cache.backend.redis'; // Use Redis as the default cache.
  
  // Set Redis to not get the cache_form (no performance difference).
  $settings['cache']['bins']['form']      = 'cache.backend.database';
}

/**
 * If there is a eapps settings file, then include it
 */
$eapps_settings = __DIR__ . "/settings.eapps.php";
if (file_exists($eapps_settings)) {
  include $eapps_settings;
}

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

/**
 * Always install the 'standard' profile to stop the installer from
 * modifying settings.php.
 */
$settings['install_profile'] = 'standard';
