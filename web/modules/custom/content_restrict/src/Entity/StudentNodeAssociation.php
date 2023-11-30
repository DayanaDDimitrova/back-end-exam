<?php

// namespace Drupal\content_restrict\Entity;

// use Drupal\Core\Config\Entity\ConfigEntityBase;

// /**
//  * Defines the Student Node Association entity.
//  *
//  * @ConfigEntityType(
//  *   id = "student_node_association",
//  *   label = @Translation("Student Node Association"),
//  *   config_prefix = "student_node_association",
//  *   entity_keys = {
//  *     "id" = "id",
//  *     "label" = "label",
//  *   },
//  * )
//  */
// class StudentNodeAssociation extends ConfigEntityBase {


//   public function getNodeAssociations() {
//     return $this->get('associations') ?: [];
//   }

//   public function addNodeAssociation($student_id, $node_id) {
//     $associations = $this->getNodeAssociations();
//     $associations[$student_id] = $node_id;
//     $this->set('associations', $associations);
//     return $this;
//   }

// }
