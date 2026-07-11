<?php

declare(strict_types=1);

namespace K2gl\Slsa\Tests;

use K2gl\InToto\PredicateRegistry;
use K2gl\InToto\ResourceDescriptor;
use K2gl\InToto\Statement;
use K2gl\InToto\StatementVersion;
use K2gl\Slsa\Exception\InvalidProvenanceException;
use K2gl\Slsa\Internal\Json;
use K2gl\Slsa\Predicates;
use K2gl\Slsa\VerificationResult;
use K2gl\Slsa\VerificationSummary;
use K2gl\Slsa\Verifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function K2gl\PHPUnitFluentAssertions\fact;

#[CoversClass(VerificationSummary::class)]
#[CoversClass(Verifier::class)]
#[CoversClass(VerificationResult::class)]
#[CoversClass(Predicates::class)]
#[CoversClass(Json::class)]
#[CoversClass(InvalidProvenanceException::class)]
final class VerificationSummaryTest extends TestCase
{
    private function sampleVsa(): VerificationSummary
    {
        return new VerificationSummary(
            verifier: new Verifier(
                id: 'https://github.com/slsa-framework/slsa-verifier',
                version: ['slsa-verifier' => 'v2.4.1'],
            ),
            timeVerified: '2026-07-11T12:00:00Z',
            resourceUri: 'pkg:composer/k2gl/dsse@1.3.0',
            policy: new ResourceDescriptor(
                uri: 'https://example.com/policy.yaml',
                digest: ['sha256' => 'abc123'],
            ),
            verificationResult: VerificationResult::Passed,
            verifiedLevels: ['SLSA_BUILD_LEVEL_3', 'SLSA_SOURCE_LEVEL_2'],
            slsaVersion: '1.0',
            inputAttestations: [
                new ResourceDescriptor(uri: 'https://example.com/prov.intoto.jsonl', digest: ['sha256' => 'def456']),
            ],
            dependencyLevels: ['SLSA_BUILD_LEVEL_3' => 5, 'SLSA_BUILD_LEVEL_2' => 2],
        );
    }

    public function testRoundTripsThroughAStatement(): void
    {
        $vsa = $this->sampleVsa();
        $subject = [new ResourceDescriptor(name: 'dsse.zip', digest: ['sha256' => 'deadbeef'])];

        $statement = $vsa->toStatement($subject);

        fact($statement->predicateType)->is(VerificationSummary::PREDICATE_TYPE);
        fact($statement->version)->is(StatementVersion::V1);
        fact($statement->subject[0]->name)->is('dsse.zip');

        $parsed = VerificationSummary::fromStatement($statement);

        fact($parsed->verifier->id)->is('https://github.com/slsa-framework/slsa-verifier');
        fact($parsed->verifier->version)->is(['slsa-verifier' => 'v2.4.1']);
        fact($parsed->timeVerified)->is('2026-07-11T12:00:00Z');
        fact($parsed->resourceUri)->is('pkg:composer/k2gl/dsse@1.3.0');
        fact($parsed->policy->digest)->is(['sha256' => 'abc123']);
        fact($parsed->verificationResult)->is(VerificationResult::Passed);
        fact($parsed->verifiedLevels)->is(['SLSA_BUILD_LEVEL_3', 'SLSA_SOURCE_LEVEL_2']);
        fact($parsed->slsaVersion)->is('1.0');
        fact($parsed->inputAttestations[0]->uri)->is('https://example.com/prov.intoto.jsonl');
        fact($parsed->dependencyLevels)->is(['SLSA_BUILD_LEVEL_3' => 5, 'SLSA_BUILD_LEVEL_2' => 2]);

        // JSON is stable across a full re-parse.
        $encode = static fn (VerificationSummary $v): string => json_encode($v->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        fact($encode($parsed))->is($encode($vsa));
    }

    public function testParsesRealWorldVsa(): void
    {
        $json = <<<'JSON'
            {
              "_type": "https://in-toto.io/Statement/v1",
              "predicateType": "https://slsa.dev/verification_summary/v1",
              "subject": [
                {"name": "app.tar.gz", "digest": {"sha256": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"}}
              ],
              "predicate": {
                "verifier": {"id": "https://github.com/slsa-framework/slsa-verifier"},
                "timeVerified": "2026-07-01T09:30:00Z",
                "resourceUri": "pkg:composer/octo/repo@2.0.0",
                "policy": {"uri": "https://example.com/policies/composer.yaml", "digest": {"sha256": "c0ffee"}},
                "verificationResult": "PASSED",
                "verifiedLevels": ["SLSA_BUILD_LEVEL_3"],
                "slsaVersion": "1.0"
              }
            }
            JSON;

        $statement = Statement::fromJson($json);
        $vsa = VerificationSummary::fromStatement($statement);

        fact($statement->version)->is(StatementVersion::V1);
        fact($vsa->verifier->id)->is('https://github.com/slsa-framework/slsa-verifier');
        fact($vsa->verificationResult)->is(VerificationResult::Passed);
        fact($vsa->verifiedLevels)->is(['SLSA_BUILD_LEVEL_3']);
        fact($vsa->policy->digestFor('sha256'))->is('c0ffee');
        fact($vsa->inputAttestations)->is([]);
        fact($vsa->dependencyLevels)->null();
    }

    public function testMinimalSummaryOmitsOptionalKeys(): void
    {
        $vsa = new VerificationSummary(
            verifier: new Verifier(id: 'https://ci.example.com/verifier'),
            timeVerified: '2026-07-11T00:00:00Z',
            resourceUri: 'pkg:composer/octo/repo@1.0.0',
            policy: new ResourceDescriptor(uri: 'https://example.com/policy.yaml'),
            verificationResult: VerificationResult::Failed,
            verifiedLevels: [],
            slsaVersion: '1.0',
        );

        $json = json_encode($vsa->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        fact(str_contains($json, '"verificationResult":"FAILED"'))->true();
        fact(str_contains($json, 'inputAttestations'))->false();
        fact(str_contains($json, 'dependencyLevels'))->false();
        fact(str_contains($json, '"version"'))->false();
    }

    public function testEmptyDependencyLevelsSerializeAsObject(): void
    {
        $vsa = new VerificationSummary(
            verifier: new Verifier(id: 'https://ci.example.com/verifier'),
            timeVerified: '2026-07-11T00:00:00Z',
            resourceUri: 'pkg:composer/octo/repo@1.0.0',
            policy: new ResourceDescriptor(uri: 'https://example.com/policy.yaml'),
            verificationResult: VerificationResult::Passed,
            verifiedLevels: ['SLSA_BUILD_LEVEL_2'],
            slsaVersion: '1.0',
            dependencyLevels: [],
        );

        $json = json_encode($vsa->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        fact(str_contains($json, '"dependencyLevels":{}'))->true();
    }

    public function testRegistryResolvesVsaToTypedSummary(): void
    {
        $registry = new PredicateRegistry;
        Predicates::register($registry);

        $statement = $this->sampleVsa()->toStatement([new ResourceDescriptor(name: 'app', digest: ['sha256' => 'deadbeef'])]);
        $predicate = $statement->predicate($registry);

        fact($predicate instanceof VerificationSummary)->true();
        fact($predicate->predicateType())->is(VerificationSummary::PREDICATE_TYPE);
    }

    public function testFromStatementRejectsWrongPredicateType(): void
    {
        // arrange
        $statement = new Statement(
            [new ResourceDescriptor(digest: ['sha256' => 'x'])],
            'https://slsa.dev/provenance/v1',
            [],
        );

        // act + assert
        fact(static fn () => VerificationSummary::fromStatement($statement))->throws(InvalidProvenanceException::class);
    }

    public function testRejectsEmptyTimeVerified(): void
    {
        // act + assert
        fact(static fn () => new VerificationSummary(
            verifier: new Verifier(id: 'https://ci.example.com/v'),
            timeVerified: '',
            resourceUri: 'pkg:composer/octo/repo@1.0.0',
            policy: new ResourceDescriptor(uri: 'https://example.com/policy.yaml'),
            verificationResult: VerificationResult::Passed,
            verifiedLevels: [],
            slsaVersion: '1.0',
        ))->throws(InvalidProvenanceException::class);
    }

    public function testRejectsEmptyVerifierId(): void
    {
        // act + assert
        fact(static fn () => new Verifier(id: ''))->throws(InvalidProvenanceException::class);
    }

    public function testRejectsUnknownVerificationResult(): void
    {
        // act + assert
        fact(static fn () => VerificationSummary::fromArray([
            'verifier' => ['id' => 'https://ci.example.com/v'],
            'timeVerified' => '2026-07-11T00:00:00Z',
            'resourceUri' => 'pkg:composer/octo/repo@1.0.0',
            'policy' => ['uri' => 'https://example.com/policy.yaml'],
            'verificationResult' => 'MAYBE',
            'verifiedLevels' => ['SLSA_BUILD_LEVEL_2'],
            'slsaVersion' => '1.0',
        ]))->throws(InvalidProvenanceException::class);
    }

    public function testRejectsMissingVerifier(): void
    {
        // act + assert
        fact(static fn () => VerificationSummary::fromArray([
            'timeVerified' => '2026-07-11T00:00:00Z',
            'resourceUri' => 'pkg:composer/octo/repo@1.0.0',
            'policy' => ['uri' => 'https://example.com/policy.yaml'],
            'verificationResult' => 'PASSED',
            'verifiedLevels' => ['SLSA_BUILD_LEVEL_2'],
            'slsaVersion' => '1.0',
        ]))->throws(InvalidProvenanceException::class);
    }

    public function testRejectsNonListVerifiedLevels(): void
    {
        // act + assert
        fact(static fn () => VerificationSummary::fromArray([
            'verifier' => ['id' => 'https://ci.example.com/v'],
            'timeVerified' => '2026-07-11T00:00:00Z',
            'resourceUri' => 'pkg:composer/octo/repo@1.0.0',
            'policy' => ['uri' => 'https://example.com/policy.yaml'],
            'verificationResult' => 'PASSED',
            'verifiedLevels' => ['level' => 'SLSA_BUILD_LEVEL_2'],
            'slsaVersion' => '1.0',
        ]))->throws(InvalidProvenanceException::class);
    }
}
