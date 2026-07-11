<?php

declare(strict_types=1);

namespace K2gl\Slsa;

use K2gl\InToto\Predicate;
use K2gl\InToto\ResourceDescriptor;
use K2gl\InToto\Statement;
use K2gl\InToto\StatementVersion;
use K2gl\Slsa\Exception\InvalidProvenanceException;
use K2gl\Slsa\Internal\Json;

/**
 * A SLSA Verification Summary Attestation (VSA) v1 predicate: the record a
 * verifier emits after checking an artifact against a policy — which SLSA levels
 * it reached and whether it passed. It is carried as the predicate of an in-toto
 * Statement with predicate type "https://slsa.dev/verification_summary/v1".
 *
 * @see https://slsa.dev/spec/v1.0/verification_summary
 */
final class VerificationSummary implements Predicate
{
    public const PREDICATE_TYPE = 'https://slsa.dev/verification_summary/v1';

    /**
     * @param list<string>              $verifiedLevels    SLSA levels reached, e.g. ["SLSA_BUILD_LEVEL_3"]
     * @param list<ResourceDescriptor>  $inputAttestations attestations the verifier consumed
     * @param array<string, int>|null   $dependencyLevels  count of dependencies at each SLSA level
     */
    public function __construct(
        public readonly Verifier $verifier,
        public readonly string $timeVerified,
        public readonly string $resourceUri,
        public readonly ResourceDescriptor $policy,
        public readonly VerificationResult $verificationResult,
        public readonly array $verifiedLevels,
        public readonly string $slsaVersion,
        public readonly array $inputAttestations = [],
        public readonly ?array $dependencyLevels = null,
    ) {
        if ($timeVerified === '') {
            throw new InvalidProvenanceException('"timeVerified" must be a non-empty RFC 3339 timestamp.');
        }

        if ($resourceUri === '') {
            throw new InvalidProvenanceException('"resourceUri" must be a non-empty URI.');
        }

        if ($slsaVersion === '') {
            throw new InvalidProvenanceException('"slsaVersion" must be a non-empty version string.');
        }
    }

    public function predicateType(): string
    {
        return self::PREDICATE_TYPE;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'verifier' => $this->verifier->toArray(),
            'timeVerified' => $this->timeVerified,
            'resourceUri' => $this->resourceUri,
            'policy' => $this->policy->toArray(),
            'verificationResult' => $this->verificationResult->value,
            'verifiedLevels' => $this->verifiedLevels,
            'slsaVersion' => $this->slsaVersion,
        ];

        if ($this->inputAttestations !== []) {
            $out['inputAttestations'] = array_map(
                static fn (ResourceDescriptor $descriptor): array => $descriptor->toArray(),
                $this->inputAttestations,
            );
        }

        if ($this->dependencyLevels !== null) {
            $out['dependencyLevels'] = Json::jsonObject($this->dependencyLevels);
        }

        return $out;
    }

    /**
     * Wrap this summary as the predicate of an in-toto Statement over the given
     * subjects, ready to sign with k2gl/dsse. Defaults to an in-toto Statement v1.
     *
     * @param list<ResourceDescriptor> $subject
     */
    public function toStatement(array $subject, StatementVersion $statementVersion = StatementVersion::V1): Statement
    {
        return new Statement(
            subject: $subject,
            predicateType: self::PREDICATE_TYPE,
            predicate: $this->toArray(),
            version: $statementVersion,
        );
    }

    /** Parse the VSA predicate out of an in-toto Statement. */
    public static function fromStatement(Statement $statement): self
    {
        if ($statement->predicateType !== self::PREDICATE_TYPE) {
            throw new InvalidProvenanceException(sprintf(
                'Statement predicateType is "%s", expected "%s".',
                $statement->predicateType,
                self::PREDICATE_TYPE,
            ));
        }

        return self::fromArray($statement->predicate);
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            verifier: Verifier::fromArray(Json::requireObject($data, 'verifier')),
            timeVerified: Json::requireString($data, 'timeVerified'),
            resourceUri: Json::requireString($data, 'resourceUri'),
            policy: ResourceDescriptor::fromArray(Json::requireObject($data, 'policy')),
            verificationResult: self::result($data),
            verifiedLevels: Json::stringList($data, 'verifiedLevels'),
            slsaVersion: Json::requireString($data, 'slsaVersion'),
            inputAttestations: Json::descriptors($data, 'inputAttestations'),
            dependencyLevels: Json::intMapOrNull($data, 'dependencyLevels'),
        );
    }

    /** @param array<mixed> $data */
    private static function result(array $data): VerificationResult
    {
        $raw = Json::requireString($data, 'verificationResult');
        $result = VerificationResult::tryFrom($raw);

        if ($result === null) {
            throw new InvalidProvenanceException(sprintf(
                'Unknown "verificationResult" value "%s", expected "PASSED" or "FAILED".',
                $raw,
            ));
        }

        return $result;
    }
}
