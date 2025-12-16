<?php

namespace MultiPersona\Common;

class FeedbackRecord
{
    public function __construct(
        public string $taskId,
        public int $rating, // 1-5
        public string $comment,
        public \DateTime $timestamp
    ) {}
}