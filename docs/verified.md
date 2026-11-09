# Verified, and not

- **No live call** has been made to any provider: every test answers from files written by hand.
- **Amadeus**: the endpoints, parameters and bodies follow the Self-Service specifications
  (amadeus4dev/amadeus-open-api-specification: Hotel List 1.0.4, Hotel Search 3.0.9, Hotel Booking
  2.0.1, read on 2026-11-09). Amadeus closed its Self-Service portal on 2026-07-17 (new keys are
  no longer issued; reported by the trade press, February 2026): the same APIs remain under
  Amadeus' Enterprise offer, on contract. The package's `host` option points it at the address
  such a contract gives. To check against a live environment when a key exists: a city's hotels,
  their offers, an offer re-priced, a booking on the test environment with a test card.
- **local**: everything runs in the package's own tests and in omnibase/booking's.
