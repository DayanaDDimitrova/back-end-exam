<?php

namespace Drupal\add_instructor_button\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "add_instructor_button",
 *   admin_label = @Translation("Add Instructor Button"),
 *   category = @Translation("Custom")
 * )
 */
class AddInsructorBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content']['button'] = [
      '#markup' => '<a href="/node/add/instructor" class="button">Add Instructor</a>',
    ];
    return $build;

  }
}
