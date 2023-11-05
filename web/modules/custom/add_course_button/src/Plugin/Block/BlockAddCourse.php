<?php

namespace Drupal\add_course_button\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "add_course_button",
 *   admin_label = @Translation("Add Course Button"),
 *   category = @Translation("Custom")
 * )
 */
class BlockAddCourse extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content']['button'] = [
      '#markup' => '<a href="/node/add/course" class="button">Add Course</a>',
    ];
    return $build;
  }

}
