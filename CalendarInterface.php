<?php

namespace Omnistay;

use Omnistay\Model\CalendarDay;
use Omnistay\Model\PropertyRef;

interface CalendarInterface
{
    /**
     * Each night from $from to $to (excluded): free or not, its price,
     * the shortest stay starting on it.
     *
     * @return list<CalendarDay>
     */
    public function calendar(PropertyRef $property, \DateTimeInterface $from, \DateTimeInterface $to): array;
}
