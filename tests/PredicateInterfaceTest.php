<?php

declare(strict_types=1);

namespace K2gl\Slsa\Tests;

use K2gl\InToto\Predicate;
use K2gl\InToto\PredicateRegistry;
use K2gl\InToto\ResourceDescriptor;
use K2gl\Slsa\BuildDefinition;
use K2gl\Slsa\Builder;
use K2gl\Slsa\Predicates;
use K2gl\Slsa\Provenance;
use K2gl\Slsa\RunDetails;
use K2gl\Slsa\V02\Builder as BuilderV02;
use K2gl\Slsa\V02\Provenance as ProvenanceV02;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function K2gl\PHPUnitFluentAssertions\fact;

#[CoversClass(Provenance::class)]
#[CoversClass(ProvenanceV02::class)]
#[CoversClass(Predicates::class)]
final class PredicateInterfaceTest extends TestCase
{
    public function testProvenanceImplementsPredicate(): void
    {
        $provenance = $this->sampleV1();

        fact($provenance instanceof Predicate)->true();
        fact($provenance->predicateType())->is(Provenance::PREDICATE_TYPE);
    }

    public function testV02ProvenanceImplementsPredicate(): void
    {
        $provenance = $this->sampleV02();

        fact($provenance instanceof Predicate)->true();
        fact($provenance->predicateType())->is(ProvenanceV02::PREDICATE_TYPE);
    }

    public function testRegistryResolvesV1ToTypedProvenance(): void
    {
        $registry = new PredicateRegistry;
        Predicates::register($registry);

        $statement = $this->sampleV1()->toStatement([new ResourceDescriptor(name: 'app', digest: ['sha256' => 'deadbeef'])]);
        $predicate = $statement->predicate($registry);

        fact($predicate instanceof Provenance)->true();
        fact($predicate->predicateType())->is(Provenance::PREDICATE_TYPE);
    }

    public function testRegistryResolvesV02ToTypedProvenance(): void
    {
        $registry = new PredicateRegistry;
        Predicates::register($registry);

        $statement = $this->sampleV02()->toStatement([new ResourceDescriptor(name: 'app', digest: ['sha256' => 'deadbeef'])]);
        $predicate = $statement->predicate($registry);

        fact($predicate instanceof ProvenanceV02)->true();
        fact($predicate->predicateType())->is(ProvenanceV02::PREDICATE_TYPE);
    }

    private function sampleV1(): Provenance
    {
        return new Provenance(
            new BuildDefinition(
                buildType: 'https://example.com/buildtypes/v1',
                externalParameters: ['repository' => 'https://github.com/k2gl/dsse'],
            ),
            new RunDetails(
                builder: new Builder(id: 'https://ci.example.com/runner'),
            ),
        );
    }

    private function sampleV02(): ProvenanceV02
    {
        return new ProvenanceV02(
            builder: new BuilderV02(id: 'https://github.com/actions/runner'),
            buildType: 'https://example.com/buildtypes/v0.2',
        );
    }
}
