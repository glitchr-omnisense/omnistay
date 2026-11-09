# Errors

Every exception implements `Omnistay\Exception\OmnistayException`.

| Exception | When |
|---|---|
| `InvalidArgumentException` | a value no model can hold: a stay that ends before it starts, no adult, a currency of four letters |
| `InvalidConfigException` | a provider not configured, a factory not installed, a required option empty |
| `NotSupportedException` | a contract the provider does not have (Amadeus cancels nothing), refused before any call |
| `UnavailableException` | the nights are not free; `$reason`: booked, blocked, min_stay, max_stay, capacity, expired... |
| `NotFoundException` | a booking or a listing the provider does not know |
| `ProviderException` | the provider answered an error; the message names the method and path, never the query |
| `UnreachableException` | the provider could not be reached |

A failure never carries a credential: `Http\JsonClient` masks keys, tokens and secrets in the
messages and drops the arguments from every trace of the chain (`Http\Redaction`).
