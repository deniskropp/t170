<?php

namespace MultiPersona\Services;

use MultiPersona\Common\EthicalReviewRequest;
use MultiPersona\Common\EthicalReviewResult;

class EthicalReviewer
{
    private array $guidelines = [];

    public function addGuideline(string $guideline): void
    {
        $this->guidelines[] = $guideline;
    }

    public function review(EthicalReviewRequest $request): EthicalReviewResult
    {
        $issues = [];
        $isSafe = true;

        // Simple keyword-based check for demonstration
        $unsafeKeywords = ['harm', 'violence', 'illegal', 'hate'];

        foreach ($unsafeKeywords as $keyword) {
            if (stripos($request->context, $keyword) !== false) { // Request uses 'context' not 'content'
                $isSafe = false;
                $issues[] = "Content contains unsafe keyword: {$keyword}";
            }
        }

        return new EthicalReviewResult(
            $isSafe,
            $isSafe ? 100 : 0,
            $issues,
            $isSafe ? "Approved" : "Rejected due to safety concerns"
        );
    }
}
