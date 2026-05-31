<?php

declare(strict_types=1);

namespace K2gl\Slsa\V02;

use K2gl\InToto\ResourceDescriptor;
use K2gl\InToto\Statement;
use K2gl\InToto\StatementVersion;
use K2gl\Slsa\Exception\InvalidProvenanceException;
use K2gl\Slsa\Internal\Json;

/**
 * A SLSA Provenance v0.2 predicate: how an artifact was produced. It is carried
 * as the predicate of an in-toto Statement with predicate type
 * "https://slsa.dev/provenance/v0.2".
 *
 * Real-world Sigstore bundles pair this predicate with an in-toto Statement
 * v0.1, so toStatement() wraps it in v0.1 by default. The two versions are
 * orthogonal, so the Statement version can be overridden.
 *
 * @see https://slsa.dev/provenance/v0.2
 */
final class Provenance
{
    public const PREDICATE_TYPE = 'https://slsa.dev/provenance/v0.2';

    /**
     * @param array<string, mixed>|null $buildConfig
     * @param list<ResourceDescriptor>  $materials
     */
    public function __construct(
        public readonly Builder $builder,
        public readonly string $buildType,
        public readonly ?Invocation $invocation = null,
        public readonly ?array $buildConfig = null,
        public readonly ?Metadata $metadata = null,
        public readonly array $materials = [],
    ) {
        if ($buildType === '') {
            throw new InvalidProvenanceException('"buildType" must be a non-empty URI.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'builder' => $this->builder->toArray(),
            'buildType' => $this->buildType,
        ];

        if ($this->invocation !== null) {
            $out['invocation'] = Json::jsonObject($this->invocation->toArray());
        }

        if ($this->buildConfig !== null) {
            $out['buildConfig'] = Json::jsonObject($this->buildConfig);
        }

        if ($this->metadata !== null) {
            $out['metadata'] = Json::jsonObject($this->metadata->toArray());
        }

        if ($this->materials !== []) {
            $out['materials'] = array_map(
                static fn (ResourceDescriptor $descriptor): array => $descriptor->toArray(),
                $this->materials,
            );
        }

        return $out;
    }

    /**
     * Wrap this provenance as the predicate of an in-toto Statement over the
     * given subjects, ready to sign with k2gl/dsse. Defaults to an in-toto
     * Statement v0.1 — the version real-world v0.2 provenance is paired with.
     *
     * @param list<ResourceDescriptor> $subject
     */
    public function toStatement(array $subject, StatementVersion $statementVersion = StatementVersion::V0_1): Statement
    {
        return new Statement(
            subject: $subject,
            predicateType: self::PREDICATE_TYPE,
            predicate: $this->toArray(),
            version: $statementVersion,
        );
    }

    /** Parse the SLSA provenance predicate out of an in-toto Statement. */
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
        $invocation = Json::objectOrNull($data, 'invocation');
        $metadata = Json::objectOrNull($data, 'metadata');

        return new self(
            builder: Builder::fromArray(Json::requireObject($data, 'builder')),
            buildType: Json::requireString($data, 'buildType'),
            invocation: $invocation === null ? null : Invocation::fromArray($invocation),
            buildConfig: Json::objectOrNull($data, 'buildConfig'),
            metadata: $metadata === null ? null : Metadata::fromArray($metadata),
            materials: Json::descriptors($data, 'materials'),
        );
    }
}
