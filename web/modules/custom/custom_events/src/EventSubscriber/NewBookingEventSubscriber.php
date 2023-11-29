<?php

namespace Drupal\custom_events\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\custom_events\Event\NewBookingEvent;

class NewBookingEventSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [NewBookingEvent::NEW_BOOKING => 'onNewBooking'];
  }

  public function onNewBooking(NewBookingEvent $event) {
   // \Drupal::messenger()->addMessage('A new student has made a registration for a course. (This is the Subscriber!)');
    \Drupal::logger('custom_events')->notice($event->getBooking());
  }
}



