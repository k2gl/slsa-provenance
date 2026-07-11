<?php

declare(strict_types=1);

namespace K2gl\Slsa;

/**
 * The outcome of a verification recorded by a {@see VerificationSummary}: whether
 * the artifact passed or failed the verifier's policy.
 *
 * @see https://slsa.dev/spec/v1.0/verification_summary#verificationResult
 */
enum VerificationResult: string
{
    case Passed = 'PASSED';
    case Failed = 'FAILED';
}
