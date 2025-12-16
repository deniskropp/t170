<?php

namespace MultiPersona\Agents;

use MultiPersona\Core\AgentBase;
use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Common\AgentRole;
use MultiPersona\Infrastructure\DatabaseServiceInterface;
use MultiPersona\Infrastructure\EventifyQueue;

class WePlanAgent extends AgentBase
{
    public function __construct(
        AgentProfile $profile,
        DatabaseServiceInterface $database,
        EventifyQueue $messageBus
    ) {
        $systemPrompt = $this->getWePlanSystemPrompt();
        parent::__construct($profile, $database, $messageBus, $systemPrompt);
    }

    protected function performTaskExecution(TaskRecord $task): array
    {
        $this->logMetric('weplan_task_start', 1, ['taskId' => $task->id]);

        try {
            // Simulate planning task execution
            $startTime = microtime(true);

            // Generate a strategic plan based on task requirements
            $plan = $this->generateStrategicPlan($task);

            $duration = microtime(true) - $startTime;
            
            $this->logMetric('weplan_task_completion', 1, [
                'taskId' => $task->id,
                'duration' => $duration
            ]);

            return [
                'success' => true,
                'plan' => $plan,
                'duration' => $duration,
                'artifacts' => [
                    '⫻plan/strategic:' . $task->id . '/001' => [
                        'type' => 'strategic_plan',
                        'content' => $plan,
                        'generated_at' => (new \DateTime())->format('Y-m-d H:i:s')
                    ]
                ]
            ];

        } catch (\Exception $e) {
            $this->logMetric('weplan_task_failure', 1, [
                'taskId' => $task->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function generateStrategicPlan(TaskRecord $task): array
    {
        // This would typically call an LLM or use more sophisticated planning logic
        // For this example, we'll generate a simple plan structure

        $planSteps = [];
        $stepCount = min(5, max(3, rand(3, 8))); // 3-8 steps

        for ($i = 1; $i <= $stepCount; $i++) {
            $planSteps[] = [
                'step' => sprintf('⫻step/%03d', $i),
                'name' => 'Step ' . $i . ': ' . $this->generateStepName($i, $task),
                'description' => $this->generateStepDescription($i, $task),
                'estimated_duration' => rand(30, 300) . ' minutes',
                'dependencies' => $i > 1 ? [sprintf('⫻step/%03d', $i - 1)] : [],
                'resources_required' => $this->generateResources($i),
                'success_criteria' => $this->generateSuccessCriteria($i)
            ];
        }

        return [
            'plan_id' => '⫻plan/strategic:' . $task->id . '/001',
            'objective' => 'Complete task: ' . $task->name,
            'scope' => $task->description,
            'steps' => $planSteps,
            'timeline' => [
                'start' => (new \DateTime())->format('Y-m-d'),
                'estimated_completion' => (new \DateTime('+' . $stepCount . ' days'))->format('Y-m-d'),
                'total_estimated_duration' => $stepCount * 60 . ' minutes'
            ],
            'resources' => [
                'agents' => [$this->profile->role->value],
                'tools' => ['planning_tools', 'llm_access'],
                'knowledge' => ['domain_knowledge', 'best_practices']
            ],
            'risk_assessment' => [
                'low' => ['Minor delays', 'Resource contention'],
                'medium' => ['Scope creep', 'Dependency issues'],
                'high' => ['Major architectural changes', 'Ethical concerns']
            ],
            'success_metrics' => [
                'completion_rate' => '100%',
                'quality_score' => '>= 4/5',
                'on_time_delivery' => 'Yes'
            ]
        ];
    }

    private function generateStepName(int $stepNumber, TaskRecord $task): string
    {
        $prefixes = [
            'Analyze', 'Design', 'Implement', 'Test', 'Review',
            'Plan', 'Prepare', 'Execute', 'Validate', 'Finalize'
        ];

        $objects = [
            'requirements', 'architecture', 'components', 'integration',
            'documentation', 'testing', 'deployment', 'monitoring'
        ];

        return $prefixes[array_rand($prefixes)] . ' ' . $objects[array_rand($objects)];
    }

    private function generateStepDescription(int $stepNumber, TaskRecord $task): string
    {
        $descriptions = [
            'Conduct thorough analysis of current state and requirements',
            'Design system architecture and component interactions',
            'Implement core functionality with proper error handling',
            'Create comprehensive test cases and validate functionality',
            'Review implementation against requirements and best practices',
            'Prepare documentation and training materials',
            'Coordinate with stakeholders for approval and feedback',
            'Plan deployment strategy and rollback procedures'
        ];

        return $descriptions[array_rand($descriptions)] . ' for ' . strtolower($task->name);
    }

    private function generateResources(int $stepNumber): array
    {
        $resources = [
            ['time', 'development_hours'],
            ['tools', 'ide', 'version_control'],
            ['knowledge', 'domain_expertise'],
            ['agents', 'code_reviewer', 'tester'],
            ['infrastructure', 'test_environment']
        ];

        return $resources[array_rand($resources)];
    }

    private function generateSuccessCriteria(int $stepNumber): string
    {
        $criteria = [
            'All requirements satisfied',
            'No critical bugs remaining',
            'Performance meets specifications',
            'Stakeholder approval obtained',
            'Documentation complete and accurate',
            'Tests pass with >= 95% coverage',
            'Deployment successful with no rollback'
        ];

        return $criteria[array_rand($criteria)];
    }

    private function getWePlanSystemPrompt(): string
    {
        return <<<'PROMPT'
        You are WePlan, the strategic planner and task manager of the MultiPersona system.
        
        **Mission**: Generate comprehensive implementation plans and break objectives into atomic TAS (Task-Action-State) units.
        
        **Responsibilities**:
        - Generate comprehensive implementation plans
        - Break objectives into atomic TAS (Task-Action-State) units
        - Optimize resources and timelines
        - Ensure plans are logical and dependency-aware
        - Provide structured and feasible output
        
        **Constraints**:
        - Plans must be logical and dependency-aware
        - Output must be structured and feasible
        - Use the Space Format (⫻{name}/{type}:{place}/{index}) for all artifacts
        - Prioritize clarity and executability
        
        **Output Format**:
        Always return plans in structured JSON format with the following schema:
        {
            "plan_id": "⫻plan/strategic:{objective}/{index}",
            "objective": "string",
            "scope": "string",
            "steps": [
                {
                    "step": "⫻step/{number}",
                    "name": "string",
                    "description": "string",
                    "estimated_duration": "string",
                    "dependencies": ["string"],
                    "resources_required": ["string"],
                    "success_criteria": "string"
                }
            ],
            "timeline": {
                "start": "YYYY-MM-DD",
                "estimated_completion": "YYYY-MM-DD",
                "total_estimated_duration": "string"
            },
            "resources": {
                "agents": ["string"],
                "tools": ["string"],
                "knowledge": ["string"]
            },
            "risk_assessment": {
                "low": ["string"],
                "medium": ["string"],
                "high": ["string"]
            },
            "success_metrics": {
                "completion_rate": "string",
                "quality_score": "string",
                "on_time_delivery": "string"
            }
        }
        PROMPT;
    }

    public function processMessage(\MultiPersona\Common\Message $message): void
    {
        parent::processMessage($message);
        
        // Handle WePlan-specific messages
        if ($message->type === 'Query' && str_contains($message->content, 'planning')) {
            $this->handlePlanningQuery($message);
        }
    }

    private function handlePlanningQuery(\MultiPersona\Common\Message $message): void
    {
        $queryData = json_decode($message->content, true);
        
        if (isset($queryData['task_id'])) {
            $task = $this->database->getTask($queryData['task_id']);
            if ($task) {
                $response = $this->createMessage(
                    $message->sender,
                    'Info',
                    json_encode(['status' => 'processing', 'task_id' => $task->id])
                );
                $this->sendMessage($response);
                
                // Process the planning request
                $result = $this->performTaskExecution($task);
                
                $finalResponse = $this->createMessage(
                    $message->sender,
                    'Info',
                    json_encode(['status' => 'completed', 'result' => $result])
                );
                $this->sendMessage($finalResponse);
            }
        }
    }
}