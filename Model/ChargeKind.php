<?php

namespace Omnistay\Model;

/** What a line of a price is for. */
enum ChargeKind: string
{
    /** The nights themselves (Rate::$nights add up to it). */
    case Nights = 'nights';
    case Cleaning = 'cleaning';
    /** The site's own fee, paid by the guest. */
    case Service = 'service';
    /** VAT, a tourist tax per person and night... */
    case Tax = 'tax';
    /** Money off: a week's discount, a promotion (a negative amount). */
    case Discount = 'discount';
    case Other = 'other';
}
