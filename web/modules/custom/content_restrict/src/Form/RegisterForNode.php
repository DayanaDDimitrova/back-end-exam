<?php

namespace Drupal\content_restrict\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;



class RegisterForNode extends FormBase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new RegisterForNode object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }
/*---------------------------------------------------------------------------------*/
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_restrict_register_for_node';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node_options = $this->getNodeOptions('course');
    $form['selected_node'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Course'),
      '#options' => $node_options,
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the selected node ID.
    $selectedNodeId = $form_state->getValue('selected_node');

    // Load the selected node.
    $selectedNode = Node::load($selectedNodeId);

    // Check if the selected node exists and is of the expected type.
    if ($selectedNode && $selectedNode->getType() == 'course') {
      // Get the currently logged-in user (assuming the user is a student).
      $user = \Drupal::currentUser();

      // Add the student to the course. This is just an example; you may need to implement your own logic.
      // This could involve creating a reference field on the course node to store the students.
      $selectedNode->field_students[] = ['target_id' => $user->id()];
      $selectedNode->save();

      // Display a confirmation message.
      $this->messenger->addMessage($this->t('You have successfully registered for the course.'));
      $url = Url::fromRoute('entity.node.canonical', ['node' => $selectedNodeId]);
      $form_state->setResponse(new RedirectResponse($url->toString()));
    }
    else {
      // Handle the case where the selected node is not found or is not a course.
      $this->messenger->addError($this->t('Invalid course selected. Please try again.'));
    }
  }

  private function getNodeOptions($content_type) {
    $options = [];

    $query = \Drupal::entityQuery('node')
      ->condition('type', $content_type)
      ->accessCheck(TRUE)
      ->execute();


    $nodes = Node::loadMultiple($query);

    foreach ($nodes as $node) {
      $options[$node->id()] = $node->label();
    }

    return $options;

  }
}

