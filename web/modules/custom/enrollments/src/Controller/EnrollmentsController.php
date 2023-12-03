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
   public function studentsCourses() {
     $selectedCourse = \Drupal::request()->query->get('course_filter');
     $results = $this->fetchEnrollmentsData($selectedCourse);
     $formatted_data = $this->formatEnrollmentsData($results);    $results = $this->fetchEnrollmentsData();
     $formatted_data = $this->formatEnrollmentsData($results);
     $build = [
       'course_filter_form' => $this->getCourseFilterForm(),
       'students_courses' => [
         '#markup' => '<ul>' . implode('', array_map(function ($item) {
           if (is_array($item)) {
             $student_link = Link::fromTextAndUrl($item['student_name'], Url::fromRoute('entity.node.canonical', ['node' => $item['student_node_id']]))->toString();
             $course_link = Link::fromTextAndUrl($item['course_name'], Url::fromRoute('entity.node.canonical', ['node' => $item['course_node_id']]))->toString();
             return '<li>' . $student_link . ' - ' . $course_link . '</li>';
           } else {
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
       '#options' => $this->getCourseOptions(),
       '#default_value' => isset($_GET['course_filter']) ? $_GET['course_filter'] : '',
       '#submit' => ['::submitFilterForm'],
     ];
     $form['time_filter'] = [
       '#type' => 'select',
       '#title' => $this->t('Filter by Time'),
       '#options' => [
         'most_recent' => $this->t('Most Recent'),
       ],
       '#default_value' => \Drupal::request()->query->get('time_filter'),
       '#submit' => ['::submitFilterForm'],
     ];

     $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

     return $form;
   }


   /**
    * Submit handler for the course filter form.
    */
    public function submitFilterForm(array &$form, FormStateInterface $form_state) {
      $selectedCourse = $form_state->getValue('course_filter');
      $selectedTime = $form_state->getValue('time_filter');

      $url = Url::fromRoute('your.route.name')
      ->setOption('query', ['course_filter' => $selectedCourse, 'time_filter' => $selectedTime]);
      $form_state->setRedirectUrl($url);
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
