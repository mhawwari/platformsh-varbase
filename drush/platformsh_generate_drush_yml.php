<?php

/**
 * @file
 * A script that creates the .drush/drush.yml file.
 */

use Platformsh\ConfigReader\Config;

// This file should only be executed as a PHP-CLI script.
if (PHP_SAPI !== 'cli') {
  exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Returns a site URL to use with Drush, if possible.
 *
 * @return string|null
 */
function _platformsh_drush_site_url() {
  $platformsh = new Config();

  if (!$platformsh->inRuntime()) {
    return;
  }

  $routes = $platformsh->getUpstreamRoutes($platformsh->applicationName);

  // Sort URLs, with the primary route first, then by HTTPS before HTTP, then by length.
  usort($routes, function (array $a, array $b) {
    // false sorts before true, normally, so negate the comparison.
    return [!$a['primary'], strpos($a['url'], 'https://') !== 0, strlen($a['url'])]
      <=>
      [!$b['primary'], strpos($b['url'], 'https://') !== 0, strlen($b['url'])];
  });

  // Return the url of the first one.
  return reset($routes)['url'] ?: NULL;
}

$appRoot = dirname(__DIR__);
$filename = $appRoot . '/.drush/drush.yml';

$siteUrl = _platformsh_drush_site_url();

if (empty($siteUrl)) {
  echo "Failed to find a site URL\n";

  if (file_exists($filename)) {
    echo "The file exists but may be invalid: $filename\n";
  }

  exit(1);
}

$siteUrlYamlEscaped = json_encode($siteUrl, JSON_UNESCAPED_SLASHES);
$scriptPath = __FILE__;

$success = file_put_contents($filename, <<<EOF
# Drush configuration file.
# This was automatically generated by the script:
# $scriptPath

options:
  # Set the default site URL.
  uri: $siteUrlYamlEscaped

EOF
);
if (!$success) {
  echo "Failed to write file: $filename\n";
  exit(1);
}

if (!chmod($filename, 0600)) {
  echo "Failed to modify file permissions: $filename\n";
  exit(1);
}

echo "Created Drush configuration file: $filename\n";
