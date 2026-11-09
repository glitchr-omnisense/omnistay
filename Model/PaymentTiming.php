<?php

namespace Omnistay\Model;

/** When the guest pays: at booking, at the property (a card only guarantees), or a deposit now and the rest there. */
enum PaymentTiming: string
{
    case Now = 'now';
    case AtProperty = 'at_property';
    case Deposit = 'deposit';
}
