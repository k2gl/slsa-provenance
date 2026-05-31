<?php

declare(strict_types=1);

namespace K2gl\Slsa\Tests;

use K2gl\InToto\ResourceDescriptor;
use K2gl\InToto\Statement;
use K2gl\InToto\StatementVersion;

use function K2gl\PHPUnitFluentAssertions\fact;

use K2gl\Slsa\Exception\InvalidProvenanceException;
use K2gl\Slsa\Internal\Json;
use K2gl\Slsa\V02\Builder;
use K2gl\Slsa\V02\Completeness;
use K2gl\Slsa\V02\ConfigSource;
use K2gl\Slsa\V02\Invocation;
use K2gl\Slsa\V02\Metadata;
use K2gl\Slsa\V02\Provenance;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Provenance::class)]
#[CoversClass(Builder::class)]
#[CoversClass(Invocation::class)]
#[CoversClass(ConfigSource::class)]
#[CoversClass(Metadata::class)]
#[CoversClass(Completeness::class)]
#[CoversClass(Json::class)]
#[CoversClass(InvalidProvenanceException::class)]
final class ProvenanceV02Test extends TestCase
{
    private function sampleProvenance(): Provenance
    {
        return new Provenance(
            builder: new Builder(id: 'https://github.com/actions/runner'),
            buildType: 'https://github.com/slsa-framework/slsa-github-generator/generic@v1',
            invocation: new Invocation(
                configSource: new ConfigSource(
                    uri: 'git+https://github.com/k2gl/dsse@refs/heads/main',
                    digest: ['sha1' => 'abc123'],
                    entryPoint: '.github/workflows/release.yml',
                ),
                parameters: ['ref' => 'refs/tags/1.0.0'],
                environment: ['github_event_name' => 'push'],
            ),
            metadata: new Metadata(
                buildInvocationId: 'run-42',
                buildStartedOn: '2026-05-30T00:00:00Z',
                completeness: new Completeness(parameters: true, environment: false, materials: false),
                reproducible: false,
            ),
            materials: [
                new ResourceDescriptor(uri: 'git+https://github.com/k2gl/dsse', digest: ['sha1' => 'abc123']),
            ],
        );
    }

    public function testRoundTripsThroughAStatement(): void
    {
        $provenance = $this->sampleProvenance();
        $subject = [new ResourceDescriptor(name: 'app.phar', digest: ['sha256' => 'deadbeef'])];

        $statement = $provenance->toStatement($subject);

        fact($statement->predicateType)->is(Provenance::PREDICATE_TYPE);
        fact($statement->version)->is(StatementVersion::V0_1);
        fact($statement->subject[0]->name)->is('app.phar');

        $parsed = Provenance::fromStatement($statement);

        fact($parsed->builder->id)->is('https://github.com/actions/runner');
        fact($parsed->invocation?->configSource?->uri)->is('git+https://github.com/k2gl/dsse@refs/heads/main');
        fact($parsed->invocation?->configSource?->entryPoint)->is('.github/workflows/release.yml');
        fact($parsed->invocation?->environment)->is(['github_event_name' => 'push']);
        fact($parsed->metadata?->buildInvocationId)->is('run-42');
        fact($parsed->metadata?->completeness?->parameters)->true();
        fact($parsed->metadata?->completeness?->environment)->false();
        fact($parsed->metadata?->reproducible)->false();
        fact($parsed->materials[0]->digest)->is(['sha1' => 'abc123']);

        // JSON is stable across a full re-parse.
        $encode = static fn (Provenance $p): string => json_encode($p->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        fact($encode($parsed))->is($encode($provenance));
    }

    public function testParsesRealWorldV02(): void
    {
        $json = <<<'JSON'
            {
              "_type": "https://in-toto.io/Statement/v0.1",
              "predicateType": "https://slsa.dev/provenance/v0.2",
              "subject": [
                {"name": "app.tar.gz", "digest": {"sha256": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"}}
              ],
              "predicate": {
                "builder": {"id": "https://github.com/slsa-framework/slsa-github-generator/.github/workflows/generator_generic_slsa3.yml@refs/tags/v1.2.0"},
                "buildType": "https://github.com/slsa-framework/slsa-github-generator/generic@v1",
                "invocation": {
                  "configSource": {
                    "uri": "git+https://github.com/octo/repo@refs/heads/main",
                    "digest": {"sha1": "c7a9f0aa1c2b3d4e5f60718293a4b5c6d7e8f900"},
                    "entryPoint": ".github/workflows/release.yml"
                  },
                  "parameters": {},
                  "environment": {"github_run_id": "5827"}
                },
                "metadata": {
                  "buildInvocationId": "5827-1",
                  "completeness": {"parameters": true, "environment": false, "materials": false},
                  "reproducible": false
                },
                "materials": [
                  {"uri": "git+https://github.com/octo/repo@refs/heads/main", "digest": {"sha1": "c7a9f0aa1c2b3d4e5f60718293a4b5c6d7e8f900"}}
                ]
              }
            }
            JSON;

        $statement = Statement::fromJson($json);
        $provenance = Provenance::fromStatement($statement);

        fact($statement->version)->is(StatementVersion::V0_1);
        fact($provenance->buildType)->is('https://github.com/slsa-framework/slsa-github-generator/generic@v1');
        fact($provenance->invocation?->configSource?->entryPoint)->is('.github/workflows/release.yml');
        fact($provenance->invocation?->environment)->is(['github_run_id' => '5827']);
        fact($provenance->metadata?->completeness?->parameters)->true();
        fact($provenance->materials[0]->uri)->is('git+https://github.com/octo/repo@refs/heads/main');
    }

    public function testCanWrapInStatementV1(): void
    {
        $statement = $this->sampleProvenance()->toStatement(
            [new ResourceDescriptor(digest: ['sha256' => 'x'])],
            StatementVersion::V1,
        );

        fact($statement->version)->is(StatementVersion::V1);
        fact($statement->predicateType)->is(Provenance::PREDICATE_TYPE);
    }

    public function testEmptyParametersSerializeAsObject(): void
    {
        $provenance = new Provenance(
            builder: new Builder(id: 'https://ci.example.com/builder'),
            buildType: 'https://example.com/bt',
            invocation: new Invocation(parameters: []),
        );

        $json = json_encode($provenance->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        fact(str_contains($json, '"parameters":{}'))->true();
        fact(str_contains($json, '[]'))->false();
    }

    public function testRejectsEmptyBuildType(): void
    {
        $this->expectException(InvalidProvenanceException::class);
        new Provenance(builder: new Builder(id: 'https://ci.example.com/b'), buildType: '');
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
            'https://slsa.dev/provenance/v1',
            [],
        );

        $this->expectException(InvalidProvenanceException::class);
        Provenance::fromStatement($statement);
    }

    public function testRejectsMissingBuilder(): void
    {
        $this->expectException(InvalidProvenanceException::class);
        Provenance::fromArray(['buildType' => 'https://example.com/bt']);
    }

    public function testRejectsMissingBuildType(): void
    {
        $this->expectException(InvalidProvenanceException::class);
        Provenance::fromArray(['builder' => ['id' => 'https://ci.example.com/b']]);
    }
}
