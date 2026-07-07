<?php

namespace App\Enums;

enum NegotiationStatus: string
{
    case Submitted = 'submitted';
    case CounterOffer = 'counter_offer';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
