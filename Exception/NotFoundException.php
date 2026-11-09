<?php

namespace Omnistay\Exception;

/** A booking, an offer or a property the provider does not know. */
final class NotFoundException extends \RuntimeException implements OmnistayException
{
}
