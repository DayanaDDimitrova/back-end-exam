<?php

namespace Drupal\content_restrict\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\custom_events\Event\NewBookingEvent;



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

    // $form['message'] = [
    //   '#type' => 'textarea',
    //   '#title' => $this->t('Message'),
    //   '#required' => TRUE,
    // ];

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
    $selectedNodeId = $form_state->getValue('selected_node');
    $selectedNode = Node::load($selectedNodeId);

    if ($selectedNode && $selectedNode->getType() == 'course') {
      $user = \Drupal::currentUser();

      $selectedNode->field_registered_user[] = ['target_id' => $user->id()];
      $selectedNode->save();

      $student_name = $user->getDisplayName();
      $course_name = $selectedNode->getTitle();

      \Drupal::database()->insert('enrollments')
      ->fields(['student_name' => $student_name, 'course_name' => $course_name])
      ->execute();

      $this->messenger->addMessage($this->t('You have successfully registered for the course.'));
      $url = Url::fromRoute('entity.node.canonical', ['node' => $selectedNodeId]);
      $form_state->setResponse(new RedirectResponse($url->toString()));
    }
    else {
      $this->messenger->addError($this->t('Invalid course selected. Please try again.'));
    }

    $event = new NewBookingEvent('A new Student has registered for a course.:)');
    \Drupal::service('event_dispatcher')->dispatch($event,NewBookingEvent::NEW_BOOKING);
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


  function content_restrict_form_submit($form, &$form_state) {
  $course_name = $form_state->getValue('course_name');
  $student_name = \Drupal::currentUser()->getDisplayName();

  \Drupal::database()->insert('enrollments')
    ->fields([
      'student_name' => $student_name,
      'course_name' => $course_name,
      'enrollment_timestamp' => \Drupal::time()->getRequestTime(),
    ])
    ->execute();
}


}
