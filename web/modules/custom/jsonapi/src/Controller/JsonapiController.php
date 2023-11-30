<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Returns responses for JsonApi routes.
 */
class JsonapiController extends ControllerBase {

  /**
   * Returns nodes of the "course" content type in JSON format.
   */

public function getNodes() {

    $query = \Drupal::entityQuery('node')
    ->condition('type', 'course')
    ->accessCheck(TRUE);
    $nids = $query->execute();

  $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

  $data = [];
  foreach ($nodes as $node) {
    $data[] = [
      'id' => $node->id(),
      'courseName' => $node->getTitle(),
      'description' => $node->get('field_description')->value,
      'startDate' => strtotime($node->get('field_start_date')->value),
      'endDate' => strtotime($node->get('field_end_date')->value),
       'instructor' => [
         'name' => $node->get('field_name')->value,
        //  'bio' => $node->get('field_bio')->value,
        //  'contactInfo' => $node->get('field_email')->value,

       ],
      'subject' => $node->get('field_referance')->value,
      'level' => $node->get('field_referance2')->value,
      'department' => $node->get('field_referance3')->value,
      'resources' => $this->getResources($node),
    ];
  }
  $format = 'json';
  $context = ['plugin_id' => 'entity'];
  
  $response = new JsonResponse(
    \Drupal::service('serializer')->serialize($data, $format, $context),
    200,
    ['Content-Type' => 'application/json'],
    true // Allow JSON
  );

  return $response;
}

  private function getResources($node) {
    $resources = $node->get('field_referance_resourses')->getValue();
    $formattedResources = [];
    foreach ($resources as $resource) {
      $formattedResources[] = [
        'title' => $resource['title'],
        'description' => $resource['description'],
      ];
    }
    return $formattedResources;
  }

}
