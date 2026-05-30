# Changelog

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
