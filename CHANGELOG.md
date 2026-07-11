# Changelog

## 1.3.0

- **SLSA Verification Summary Attestation (VSA) v1** predicate
  (`https://slsa.dev/verification_summary/v1`) — the attestation a verifier emits
  after checking an artifact against a policy: which SLSA levels it reached and
  whether it passed. New `VerificationSummary` predicate with the same
  `fromArray()`/`toArray()`/`toStatement()`/`fromStatement()` shape as the provenance
  predicates, wrapping into an in-toto Statement v1 by default. Typed `Verifier`
  value object and a `VerificationResult` enum (`PASSED`/`FAILED`); `policy` and
  `inputAttestations` reuse `k2gl/in-toto-attestation`'s `ResourceDescriptor`.
- `Predicates::register()` now also registers the VSA type, so
  `Statement::predicate()` resolves a VSA statement to the typed object. The v1/v0.2
  API is unchanged.

## 1.2.0

- `Provenance` (v1) and `V02\Provenance` now implement the `Predicate` interface from
  `k2gl/in-toto-attestation`, and `Predicates::register()` registers both with a
  `PredicateRegistry`. After registering, `Statement::predicate()` resolves a SLSA
  provenance statement to the typed `Provenance` object. Requires
  `k2gl/in-toto-attestation ^1.2`.

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
