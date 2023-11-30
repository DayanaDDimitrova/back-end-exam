<?php

namespace Drupal\enrollments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Returns responses for enrollments routes.
 */
class EnrollmentsController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new EnrollmentsController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Page callback for displaying students and their chosen courses.
   */
  public function studentsCourses(array &$form, FormStateInterface $form_state) {
    $selectedCourse = $form_state->getValue('course_filter');
    $results = $this->fetchEnrollmentsData($selectedCourse);

    $formatted_data = $this->formatEnrollmentsData($results);    $results = $this->fetchEnrollmentsData();

    $formatted_data = $this->formatEnrollmentsData($results);
    $build = [
      'course_filter_form' => $this->getCourseFilterForm(),
      'students_courses' => [
        '#markup' => '<ul>' . implode('', array_map(function ($item) {
          if (is_array($item)) {
            // Assuming $item contains 'student_node_id' and 'course_node_id' keys.
            $student_link = Link::fromTextAndUrl($item['student_name'], Url::fromRoute('entity.node.canonical', ['node' => $item['student_node_id']]))->toString();
            $course_link = Link::fromTextAndUrl($item['course_name'], Url::fromRoute('entity.node.canonical', ['node' => $item['course_node_id']]))->toString();

            return '<li>' . $student_link . ' - ' . $course_link . '</li>';
          } else {
            // Handle the case where $item is a string.
            return '<li>' . $item . '</li>';
          }
        }, $formatted_data)) . '</ul>',
      ],
    ];

    return $build;
  }

  /**
   * Fetch data from the 'enrollments' table.
   */
  private function fetchEnrollmentsData($selectedCourse = NULL) {
    $query = $this->database->select('enrollments', 'e')
    ->fields('e', ['student_name', 'course_name']);

  if ($selectedCourse) {
    $query->condition('e.course_name', $selectedCourse);
  }

  $results = $query->execute()->fetchAll();

  return $results;
}

  private function fetchRecentEnrollmentsData() {
    $query = $this->database->select('enrollments', 'e')
      ->fields('e', ['student_name', 'course_name'])
      ->orderBy('enrollment_timestamp', 'DESC')
      ->execute();

    return $query->fetchAll();
  }

  /**
   * Format enrollment data for display.
   */
  private function formatEnrollmentsData($results) {
    $formatted_data = [];
    foreach ($results as $result) {
      $formatted_data[] = $result->student_name . ' - ' . $result->course_name;
    }
    return $formatted_data;
  }

  /**
   * Build the course filter form.
   */
  private function getCourseFilterForm() {
    $form = [];

    $form['course_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter by Course'),
      '#options' => $this->getCourseOptions(), // Implement this function to get course options.
      '#default_value' => isset($_GET['course_filter']) ? $_GET['course_filter'] : '',
      '#submit' => ['::submitFilterForm'],
    ];

    $form['time_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter by Time'),
      '#options' => [
        'most_recent' => $this->t('Most Recent'),
      ],
      '#default_value' => isset($_GET['time_filter']) ? $_GET['time_filter'] : '',
      '#submit' => ['::submitFilterForm'],
    ];

    return $form;
  }

  /**
   * Submit handler for the course filter form.
   */
  public function submitFilterForm(array &$form, FormStateInterface $form_state) {
    $selectedCourse = $form_state->getValue('course_filter');
    // Perform any actions based on the selected course.
  }

  /**
   * Get course options for the filter form.
   */
  private function getCourseOptions() {
    $query = $this->database->select('enrollments', 'e')
    ->distinct()
    ->fields('e', ['course_name'])
    ->orderBy('course_name')
    ->execute();

  $options = [];
  foreach ($query as $row) {
    $options[$row->course_name] = $row->course_name;
  }

  return $options;
}


}








// namespace Drupal\enrollments\Controller;

// use Drupal\Core\Controller\ControllerBase;
// use Drupal\Core\Database\Connection;
// use Drupal\Core\Link;
// use Drupal\Core\Url;
// use Symfony\Component\DependencyInjection\ContainerInterface;

// use Drupal\Core\Form\FormBuilderInterface;
// use Drupal\Core\Form\FormStateInterface;


// /**
//  * Returns responses for enrollments routes.
//  */
// class EnrollmentsController extends ControllerBase {

//   /**
//    * The database service.
//    *
//    * @var \Drupal\Core\Database\Connection
//    */
//   protected $database;

//   /**
//    * Constructs a new EnrollmentsController object.
//    *
//    * @param \Drupal\Core\Database\Connection $database
//    *   The database service.
//    */
//   public function __construct(Connection $database) {
//     $this->database = $database;
//   }

//   /**
//    * {@inheritdoc}
//    */
//   public static function create(ContainerInterface $container) {
//     return new static(
//       $container->get('database')
//     );
//   }

//   /**
//    * Page callback for displaying students and their chosen courses.
//    */
//   public function studentsCourses(array &$form, FormStateInterface $form_state) {
//     // Fetch data from the 'enrollments' table.
//     $results = $this->fetchEnrollmentsData();

//     $formatted_data = $this->formatEnrollmentsData($results);


//     // Build the render array.
//     // $build = [
//     //   'students_courses' => [
//     //     '#theme' => 'item_list',
//     //     '#results' => $formatted_data,
//     //   ],
//     // ];
// /*-------------------------------------------------------------------------- */
//     // $build = [
//     //   '#markup' => '<ul>' . implode('', array_map(function ($item) {
//     //     return '<li>' . $item . '</li>';
//     //   }, $formatted_data)) . '</ul>',
//     // ];

//     // \Drupal::logger('enrollments')->notice('<pre>' . print_r($formatted_data, TRUE) . '</pre>');

// /*-------------------------------------------------------------------------- */

// $build = [
//   'course_filter_form' => $this->getCourseFilterForm(),
//   'students_courses' => [
//     '#markup' => '<table><thead><tr><th>Student Name</th><th>Course Name</th></tr></thead><tbody>' . implode('', array_map(function ($item) {
//       if (is_array($item)) {
//         // Assuming $item contains 'student_node_id' and 'course_node_id' keys.
//         $student_link = Link::fromTextAndUrl($item['student_name'], Url::fromRoute('entity.node.canonical', ['node' => $item['student_node_id']]))->toString();
//         $course_link = Link::fromTextAndUrl($item['course_name'], Url::fromRoute('entity.node.canonical', ['node' => $item['course_node_id']]))->toString();

//         return '<tr><td>' . $student_link . '</td><td>' . $course_link . '</td></tr>';
//       } else {
//         // Handle the case where $item is a string.
//         return '<tr><td colspan="2">' . $item . '</td></tr>';
//       }
//     }, $formatted_data)) . '</tbody></table>',
//   ],
// ];



// /*-------------------------------------------------------------------------- */


//     return $build;
//   }

//   /**
//    * Fetch data from the 'enrollments' table.
//    */
//   private function fetchEnrollmentsData() {
//     // Use Drupal's database service to execute a select query.
//     $query = $this->database->select('enrollments', 'e')
//       ->fields('e', ['student_name', 'course_name'])
//       ->execute();

//     return $query->fetchAll();
//   }

//   /*--------------------Below is new for the view------------------------------------ */

//    /**
//    * Format enrollment data for display.
//    */
//   private function formatEnrollmentsData($results) {
//     $formatted_data = [];
//     foreach ($results as $result) {
//       $formatted_data[] = $result->student_name . ' - ' . $result->course_name;
//     }
//     return $formatted_data;
//   }
// /*-------------------------------------------------------------------------- */

//   private function getCourseFilterForm() {
//     $form = [];

//     $form['course_filter'] = [
//       '#type' => 'select',
//       '#title' => $this->t('Filter by Course'),
//       '#options' => $this->getCourseOptions(), // Implement this function to get course options.
//       '#default_value' => isset($_GET['course_filter']) ? $_GET['course_filter'] : '',
//       '#submit' => ['::submitFilterForm'],
//     ];

//     return $form;
// }

// public function submitFilterForm(array &$form, FormStateInterface $form_state) {
//   $selectedCourse = $form_state->getValue('course_filter');

// }

// private function getCourseOptions() {
//   // Implement this function to query the courses and return an array of options.
//   // Example: ['course1' => 'Course 1', 'course2' => 'Course 2', ...]
//   return [];
// }



// /*-------------------------------------------------------------------------- */

// }
