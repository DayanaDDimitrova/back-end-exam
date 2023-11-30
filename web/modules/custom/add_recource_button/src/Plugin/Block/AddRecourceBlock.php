<?php

namespace Drupal\add_recource_button\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "add_recource_button",
 *   admin_label = @Translation("Add Recource Button"),
 *   category = @Translation("Custom")
 * )
 */
class AddRecourceBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content']['button'] = [
      '#markup' => '<a href="/node/add/recource" class="button">Add Recource</a>',
    ];
    return $build;

  }
}
