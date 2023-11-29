<?php

namespace Drupal\content_restrict\Controller;

use Drupal\Core\Controller\ControllerBase;

class RegistrationListController extends ControllerBase {

  public function content() {
    // Query the database to get user registrations.
    $registrations = $this->getRegistrations();

    // Build a render array with a table to display the data.
    $output = [
      '#markup' => $this->buildTable($registrations),
    ];

    return $output;
  }

  private function buildTable($registrations) {
    $header = [
      'User ID',
      'Course ID',
    ];

    $rows = [];
    foreach ($registrations as $registration) {
      $rows[] = [
        'user_id' => $registration['user_id'],
        'course_id' => $registration['course_id'],
      ];
    }

    $table = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No registrations found'),
    ];

    // Render the table.
    $output = \Drupal::service('renderer')->render($table);

    return $output;
  }

  private function getRegistrations() {
    // Query the database to retrieve user registrations.
    // Implement your own query based on your database structure.
    // Example: SELECT uid, course_id FROM your_database_table;
    // ...

    // For demonstration purposes, let's assume $registrations is an array of data.
    $registrations = [
      ['user_id' => 1, 'course_id' => 123],
      ['user_id' => 2, 'course_id' => 456],
      // ...
    ];

    return $registrations;
  }

}
