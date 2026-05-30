# k2gl/slsa-provenance

A faithful, typed PHP implementation of the
[SLSA Provenance v1](https://slsa.dev/spec/v1.0/provenance) predicate
(`https://slsa.dev/provenance/v1`), built on
[`k2gl/in-toto-attestation`](https://github.com/k2gl/in-toto-attestation).

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

## License

MIT — see [LICENSE](LICENSE). Independent, clean-room implementation of the SLSA
Provenance specification.
