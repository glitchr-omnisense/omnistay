<?php

namespace Omnistay\Model;

/** The meals a rate holds. */
enum Board: string
{
    case RoomOnly = 'room_only';
    case Breakfast = 'breakfast';
    case HalfBoard = 'half_board';
    case FullBoard = 'full_board';
    case AllInclusive = 'all_inclusive';
}
