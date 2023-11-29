<?php

namespace Drupal\custom_events\Event;

use Drupal\Component\EventDispatcher\Event;

class NewBookingEvent extends Event {
  const NEW_BOOKING = 'custom_events.new_booking';

  protected $booking;

  public function __construct($booking) {
    $this->booking = $booking;
  }

  public function getBooking() {
    return $this->booking;
  }
}
