# Changelog

## 1.1.0

- **SLSA Provenance v0.2** predicate (`https://slsa.dev/provenance/v0.2`) modelled
  alongside v1 under the `K2gl\Slsa\V02` namespace — the version carried by most
  real-world Sigstore bundles. Typed value objects `Provenance`, `Builder`,
  `Invocation`, `ConfigSource`, `Metadata` and `Completeness`; `materials` reuse
  `k2gl/in-toto-attestation`'s `ResourceDescriptor`.
- `V02\Provenance::toStatement()` wraps the predicate in an in-toto Statement v0.1 by
  default (the version v0.2 provenance is paired with), with an optional argument to
  choose another; `fromStatement()` parses it regardless of the Statement version.
- Requires `k2gl/in-toto-attestation:^1.1`. BC-safe: the v1 API is unchanged.

## 1.0.0

First public release. A faithful, typed implementation of the **SLSA Provenance v1**
predicate (`https://slsa.dev/provenance/v1`), built on
[`k2gl/in-toto-attestation`](https://github.com/k2gl/in-toto-attestation):

- **`Provenance`** — the predicate (`buildDefinition` + `runDetails`) with lossless
  `fromArray()` / `toArray()`, plus `toStatement()` (wrap as an in-toto Statement over
  given subjects) and `fromStatement()` (parse it back, checking the predicate type).
- **`BuildDefinition`**, **`RunDetails`**, **`Builder`**, **`BuildMetadata`** — typed
  value objects for the full v1 shape; resolved dependencies, builder dependencies and
  byproducts reuse `k2gl/in-toto-attestation`'s `ResourceDescriptor`.
- Required object fields serialize as JSON objects (`{}` when empty); every error
  implements `SlsaException`.
