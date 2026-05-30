<?php

declare(strict_types=1);

namespace K2gl\Slsa\Tests;

use K2gl\InToto\ResourceDescriptor;
use K2gl\InToto\Statement;

use function K2gl\PHPUnitFluentAssertions\fact;

use K2gl\Slsa\BuildDefinition;
use K2gl\Slsa\Builder;
use K2gl\Slsa\BuildMetadata;
use K2gl\Slsa\Exception\InvalidProvenanceException;
use K2gl\Slsa\Internal\Json;
use K2gl\Slsa\Provenance;
use K2gl\Slsa\RunDetails;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Provenance::class)]
#[CoversClass(BuildDefinition::class)]
#[CoversClass(RunDetails::class)]
#[CoversClass(Builder::class)]
#[CoversClass(BuildMetadata::class)]
#[CoversClass(Json::class)]
#[CoversClass(InvalidProvenanceException::class)]
final class ProvenanceTest extends TestCase
{
    private function sampleProvenance(): Provenance
    {
        return new Provenance(
            new BuildDefinition(
                buildType: 'https://example.com/buildtypes/v1',
                externalParameters: ['repository' => 'https://github.com/k2gl/dsse'],
                resolvedDependencies: [
                    new ResourceDescriptor(uri: 'git+https://github.com/k2gl/dsse', digest: ['gitCommit' => 'abc']),
                ],
            ),
            new RunDetails(
                builder: new Builder(id: 'https://ci.example.com/runner', version: ['runner' => '1.2.3']),
                metadata: new BuildMetadata(invocationId: 'run-42', startedOn: '2026-05-30T00:00:00Z'),
            ),
        );
    }

    public function testRoundTripsThroughAStatement(): void
    {
        $provenance = $this->sampleProvenance();
        $subject = [new ResourceDescriptor(name: 'app.phar', digest: ['sha256' => 'deadbeef'])];

        $statement = $provenance->toStatement($subject);
        fact($statement->predicateType)->is(Provenance::PREDICATE_TYPE);
        fact($statement->subject[0]->name)->is('app.phar');

        $parsed = Provenance::fromStatement($statement);
        fact($parsed->buildDefinition->buildType)->is('https://example.com/buildtypes/v1');
        fact($parsed->buildDefinition->resolvedDependencies[0]->uri)->is('git+https://github.com/k2gl/dsse');
        fact($parsed->runDetails->builder->id)->is('https://ci.example.com/runner');
        fact($parsed->runDetails->builder->version)->is(['runner' => '1.2.3']);
        fact($parsed->runDetails->metadata?->invocationId)->is('run-42');

        // JSON is stable across a full re-parse.
        $encode = static fn (Provenance $p): string => json_encode($p->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        fact($encode($parsed))->is($encode($provenance));
    }

    public function testEmptyExternalParametersSerializeAsObject(): void
    {
        $provenance = new Provenance(
            new BuildDefinition(buildType: 'https://example.com/bt'),
            new RunDetails(builder: new Builder(id: 'https://ci.example.com/builder')),
        );

        $json = json_encode($provenance->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        fact(str_contains($json, '"externalParameters":{}'))->true();
        fact(str_contains($json, '[]'))->false();
    }

    public function testRejectsEmptyBuildType(): void
    {
        $this->expectException(InvalidProvenanceException::class);
        new BuildDefinition(buildType: '');
    }

    public function testRejectsEmptyBuilderId(): void
    {
        $this->expectException(InvalidProvenanceException::class);
        new Builder(id: '');
    }

    public function testFromStatementRejectsWrongPredicateType(): void
    {
        $statement = new Statement(
            [new ResourceDescriptor(digest: ['sha256' => 'x'])],
            'https://example.com/other-predicate',
            [],
        );

        $this->expectException(InvalidProvenanceException::class);
        Provenance::fromStatement($statement);
    }

    public function testRejectsMissingBuilder(): void
    {
        $this->expectException(InvalidProvenanceException::class);
        Provenance::fromArray([
            'buildDefinition' => ['buildType' => 'https://example.com/bt'],
            'runDetails' => [],
        ]);
    }
}
