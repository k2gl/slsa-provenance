# k2gl/slsa-provenance

[![CI](https://img.shields.io/github/actions/workflow/status/k2gl/slsa-provenance/ci.yml?branch=main&label=CI&logo=github)](https://github.com/k2gl/slsa-provenance/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/k2gl/slsa-provenance?logo=packagist&logoColor=white)](https://packagist.org/packages/k2gl/slsa-provenance)
[![Total Downloads](https://img.shields.io/packagist/dt/k2gl/slsa-provenance?logo=packagist&logoColor=white)](https://packagist.org/packages/k2gl/slsa-provenance)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%209-2a5ea7?logo=php&logoColor=white)](https://phpstan.org)
[![License](https://img.shields.io/packagist/l/k2gl/slsa-provenance?color=yellowgreen)](https://packagist.org/packages/k2gl/slsa-provenance)

Typed SLSA Provenance predicates for PHP, both the current v1 and the legacy v0.2 that most
real-world bundles still carry. Built on in-toto attestations.

SLSA Provenance describes *how* an artifact was built — the build definition (inputs)
and the run details (who built it, when, and what came out). This package models that
predicate as typed value objects and plugs it straight into an in-toto Statement, ready
to sign with [`k2gl/dsse`](https://github.com/k2gl/dsse).

## Install

```bash
composer require k2gl/slsa-provenance
```

Requires PHP 8.1+. Pulls in `k2gl/in-toto-attestation` and `k2gl/dsse`.

## Usage

```php
use K2gl\Slsa\Provenance;
use K2gl\Slsa\BuildDefinition;
use K2gl\Slsa\RunDetails;
use K2gl\Slsa\Builder;
use K2gl\Slsa\BuildMetadata;
use K2gl\InToto\ResourceDescriptor;

$provenance = new Provenance(
    new BuildDefinition(
        buildType: 'https://example.com/buildtypes/v1',
        externalParameters: ['repository' => 'https://github.com/k2gl/dsse', 'ref' => 'refs/tags/1.0.0'],
        resolvedDependencies: [
            new ResourceDescriptor(uri: 'git+https://github.com/k2gl/dsse', digest: ['gitCommit' => '…']),
        ],
    ),
    new RunDetails(
        builder: new Builder(id: 'https://github.com/actions/runner', version: ['runner' => '2.x']),
        metadata: new BuildMetadata(invocationId: 'run-42', startedOn: '2026-05-30T00:00:00Z'),
    ),
);

// Wrap as an in-toto Statement over the built artifacts, then sign with k2gl/dsse:
$statement = $provenance->toStatement([
    new ResourceDescriptor(name: 'app.phar', digest: ['sha256' => '…']),
]);
$envelope = $statement->sign($signer);   // K2gl\Dsse\Envelope
```

Parsing back (after verifying the envelope's signatures):

```php
use K2gl\InToto\Statement;
use K2gl\Slsa\Provenance;

$statement  = Statement::fromEnvelope($envelope);
$provenance = Provenance::fromStatement($statement);   // checks predicateType

$provenance->buildDefinition->buildType;          // 'https://example.com/buildtypes/v1'
$provenance->runDetails->builder->id;             // 'https://github.com/actions/runner'
$provenance->runDetails->metadata?->invocationId; // 'run-42'
```

## SLSA Provenance v0.2

Most provenance found in real Sigstore bundles is the older `v0.2` predicate
(`https://slsa.dev/provenance/v0.2`), which has a different shape from v1. It lives
under `K2gl\Slsa\V02` with its own typed value objects:

```php
use K2gl\InToto\Statement;
use K2gl\Slsa\V02\Provenance;

$statement  = Statement::fromEnvelope($envelope);   // verify signatures first
$provenance = Provenance::fromStatement($statement);

$provenance->builder->id;                              // 'https://github.com/…'
$provenance->buildType;                                // 'https://…/generic@v1'
$provenance->invocation?->configSource?->uri;          // 'git+https://github.com/…'
$provenance->metadata?->completeness?->parameters;     // true
$provenance->materials[0]->digest;                     // ['sha1' => '…']
```

Building one wraps it in an in-toto Statement **v0.1** by default — the version
real-world v0.2 provenance is paired with. The two versions are orthogonal, so the
Statement version can be overridden:

```php
use K2gl\InToto\StatementVersion;
use K2gl\Slsa\V02\Builder;
use K2gl\Slsa\V02\Provenance;

$provenance = new Provenance(
    builder: new Builder(id: 'https://github.com/actions/runner'),
    buildType: 'https://github.com/slsa-framework/slsa-github-generator/generic@v1',
);

$statement = $provenance->toStatement([$subject]);                       // in-toto Statement v0.1
$statement = $provenance->toStatement([$subject], StatementVersion::V1); // …or v1
```

## Predicate registry

Register the SLSA predicate types so in-toto's `Statement::predicate()` returns a typed
`Provenance` instead of a raw array:

```php
use K2gl\InToto\Statement;
use K2gl\Slsa\Predicates;
use K2gl\Slsa\Provenance;

Predicates::register();                  // registers v1 + v0.2 in the shared registry

$statement = Statement::fromEnvelope($envelope);
$predicate = $statement->predicate();    // a K2gl\Slsa\Provenance (or V02\Provenance), else the raw array

if ($predicate instanceof Provenance) {
    echo $predicate->buildDefinition->buildType;
}
```

## License

MIT — see [LICENSE](LICENSE). Independent, clean-room implementation of the SLSA
Provenance specification.
