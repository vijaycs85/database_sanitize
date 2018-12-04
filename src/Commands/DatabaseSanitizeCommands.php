<?php

namespace Drupal\database_sanitize\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class DatabaseSanitizeCommands extends DrushCommands {

  /**
   * Analyze existing yml files.
   *
   * Compares existing database.sanitize.yml files on the site installation
   * against existing database tables.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option file
   *   The full path to a sanitize YML file.
   * @option list
   *   List the table names.
   *
   * @command db:sanitize-analyze
   * @aliases dbsa,db-sanitize-analyze
   *
   * @throws \Exception
   */
  public function sanitizeAnalyze(array $options = ['file' => NULL, 'list' => NULL]) {
    if (empty($options['file'])) {
      $options['file'] = $this->io()->ask('Please provide the full path to a sanitize YML file');
    }

    $file = $options['file'];
    if (!file_exists($file)) {
      throw new \Exception(dt('File @file does not exist', ['@file' => $file]));
    }

    $missing_tables = \Drupal::service('database_sanitize')->getUnspecifiedTables($file);

    if (!$missing_tables) {
      $this->logger()->info(dt('All database tables are already specified in sanitize YML files'), 'ok');
      return;
    }

    $this->logger()->warning(dt('There are @count tables not defined on sanitize YML files', ['@count' => count($missing_tables)]));

    if (!empty($options['list'])) {
      $this->logger()->warning(implode("\n", $missing_tables));
    }
  }

  /**
   * Generates a database.sanitize.yml file.
   *
   * Generate database.sanitize.yml file for tables not specified on sanitize
   * YML files.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option file
   *   The full path to a sanitize YML file.
   * @option machine-name
   *   The machine name to export the tables under.
   *
   * @command db:sanitize-generate
   * @aliases dbsg,db-sanitize-generate
   *
   * @return array|void
   * @throws \Exception
   */
  public function sanitizeGenerate(array $options = ['file' => NULL, 'machine-name' => NULL]) {
    $machine_name = $options['machine-name'];
    if (empty($machine_name)) {
      throw new \Exception(dt('You must specify a machine-name'));
    }

    $yml_file_path = $options['file'];
    $missing_tables = \Drupal::service('database_sanitize')->getUnspecifiedTables($yml_file_path);
    if (!$missing_tables) {
      $this->logger()->info(dt('All database tables are already specified in sanitize YML files'), 'ok');
      return;
    }

    $content = [
      'sanitize' => [
        $machine_name => [],
      ],
    ];
    foreach ($missing_tables as $table) {
      $content['sanitize'][$machine_name][$table] = [
        'description' => "Sanitization entry for {$table}. Generated by drush db-sanitize-generate.",
        'query' => "TRUNCATE TABLE {$table}",
      ];
    }

    return $content;
  }

}
