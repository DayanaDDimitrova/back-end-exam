<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for JsonApi routes.
 */
class JsonapiController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * JsonapiController constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   */
  public function __construct(ContainerInterface $container) {
    $this->database = $container->get('database');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

  /**
   * Builds the response.
   */
  public function build() {
    $query = $this->database->select('your_course_table', 'c')
      ->fields('c')
      ->execute();

    $courses = [];
    foreach ($query as $row) {
      $courses[] = [
        "courseName" => $row->course_name,
        "description" => $row->description,
        "startDate" => strtotime($row->start_date),
        "endDate" => strtotime($row->end_date),
        "instructor" => [
          "name" => $row->instructor_name,
          "bio" => $row->instructor_bio,
          "contactInfo" => $row->instructor_contact,
        ],
        "subject" => $row->subject,
        "level" => $row->level,
        "department" => $row->department,
        "resources" => [
          [
            "title" => $row->resource_title,
            "description" => $row->resource_description,
            "url" => $row->resource_url,
          ],
          // Add more resources as needed
        ],
      ];
    }

    return new JsonResponse($courses);
  }
}
