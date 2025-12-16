<?php

namespace MultiPersona\Core;

use MultiPersona\Common\AgentProfile;
use MultiPersona\Common\AgentRole;
use MultiPersona\Infrastructure\DatabaseServiceInterface;
use MultiPersona\Infrastructure\EventifyQueue;

class AgentRegistry
{
    private DatabaseServiceInterface $database;
    private EventifyQueue $messageBus;
    private array $agentCache = [];

    public function __construct(DatabaseServiceInterface $database, EventifyQueue $messageBus)
    {
        $this->database = $database;
        $this->messageBus = $messageBus;
    }

    public function register(AgentProfile $agent): AgentProfile
    {
        $this->agentCache[$agent->id] = $agent;
        return $this->database->registerAgent($agent);
    }

    public function getAgent(string $agentId): ?AgentProfile
    {
        if (isset($this->agentCache[$agentId])) {
            return $this->agentCache[$agentId];
        }

        $agent = $this->database->getAgent($agentId);
        if ($agent) {
            $this->agentCache[$agentId] = $agent;
        }

        return $agent;
    }

    public function update(AgentProfile $agent): AgentProfile
    {
        $this->agentCache[$agent->id] = $agent;
        return $this->database->updateAgent($agent);
    }

    public function getAvailableAgents(AgentRole $role): array
    {
        return $this->database->getAvailableAgents($role);
    }

    public function getAllAgents(): array
    {
        $agents = [];
        foreach (AgentRole::cases() as $role) {
            $agents = array_merge($agents, $this->database->getAvailableAgents($role));
        }
        return $agents;
    }

    public function getAgentsByRole(AgentRole $role): array
    {
        $allAgents = $this->getAllAgents();
        return array_filter($allAgents, function ($agent) use ($role) {
            return $agent->role === $role;
        });
    }

    public function getAgentsByCapability(string $capability): array
    {
        $allAgents = $this->getAllAgents();
        return array_filter($allAgents, function ($agent) use ($capability) {
            return in_array($capability, $agent->capabilities);
        });
    }

    public function getBusyAgents(): array
    {
        $allAgents = $this->getAllAgents();
        return array_filter($allAgents, function ($agent) {
            return $agent->status === 'Busy';
        });
    }

    public function getIdleAgents(): array
    {
        $allAgents = $this->getAllAgents();
        return array_filter($allAgents, function ($agent) {
            return $agent->status === 'Idle';
        });
    }

    public function getAgentCountByRole(): array
    {
        $counts = [];
        foreach (AgentRole::cases() as $role) {
            $agents = $this->getAgentsByRole($role);
            $counts[$role->value] = count($agents);
        }
        return $counts;
    }

    public function getAgentStatusSummary(): array
    {
        return [
            'total' => count($this->getAllAgents()),
            'busy' => count($this->getBusyAgents()),
            'idle' => count($this->getIdleAgents()),
            'by_role' => $this->getAgentCountByRole()
        ];
    }

    public function findAgentForTask(array $taskRequirements): ?AgentProfile
    {
        $candidates = [];

        // Find agents with required capabilities
        foreach ($taskRequirements['capabilities'] ?? [] as $capability) {
            $agents = $this->getAgentsByCapability($capability);
            $candidates = array_merge($candidates, $agents);
        }

        // If specific role is required
        if (isset($taskRequirements['role'])) {
            $role = AgentRole::from($taskRequirements['role']);
            $agents = $this->getAgentsByRole($role);
            // Custom intersection for AgentProfile objects
            $candidates = array_filter($candidates, function ($agent) use ($agents) {
                foreach ($agents as $roleAgent) {
                    if ($agent->id === $roleAgent->id) {
                        return true;
                    }
                }
                return false;
            });
        }

        // Filter for available agents
        $availableCandidates = array_filter($candidates, function ($agent) {
            return $agent->status === 'Idle' && $agent->currentTaskId === null;
        });

        if (!empty($availableCandidates)) {
            // Return the agent that was least recently active
            usort($availableCandidates, function ($a, $b) {
                return $a->lastActive->getTimestamp() <=> $b->lastActive->getTimestamp();
            });

            return $availableCandidates[0];
        }

        return null;
    }

    public function createEphemeralAgent(AgentRole $role, array $capabilities = []): AgentProfile
    {
        $agentId = 'ephemeral-' . uniqid() . '-' . $role->value;
        
        $profile = new AgentProfile(
            $agentId,
            $role,
            array_merge($capabilities, $this->getDefaultCapabilitiesForRole($role)),
            'Idle',
            null,
            new \DateTime(),
            true
        );

        return $this->register($profile);
    }

    private function getDefaultCapabilitiesForRole(AgentRole $role): array
    {
        $defaultCapabilities = [
            AgentRole::Orchestrator->value => ['task_management', 'coordination', 'monitoring'],
            AgentRole::RoleDefiner->value => ['role_definition', 'boundary_management'],
            AgentRole::PromptEngineer->value => ['prompt_optimization', 'context_refinement'],
            AgentRole::ProtocolEstablisher->value => ['protocol_definition', 'format_standardization'],
            AgentRole::SystemMonitor->value => ['system_analysis', 'anomaly_detection'],
            AgentRole::MetaCommunicator->value => ['meta_communication', 'progress_summarization'],
            AgentRole::FizzLaMetta->value => ['external_communication', 'status_representation'],
            AgentRole::KickLaMetta->value => ['kicklang_translation', 'schema_validation'],
            AgentRole::Dima->value => ['ethical_review', 'bias_detection'],
            AgentRole::AR00L->value => ['visual_creation', 'concept_translation'],
            AgentRole::QllickBuzzFizz->value => ['rule_definition', 'cli_generation'],
            AgentRole::WePlan->value => ['strategic_planning', 'task_management'],
            AgentRole::Codein->value => ['code_implementation', 'debugging', 'refactoring']
        ];

        return $defaultCapabilities[$role->value] ?? ['general_execution'];
    }

    public function cleanupEphemeralAgents(): int
    {
        $allAgents = $this->getAllAgents();
        $ephemeralAgents = array_filter($allAgents, function ($agent) {
            return $agent->isEphemeral;
        });

        $cleanedUp = 0;
        foreach ($ephemeralAgents as $agent) {
            if ($agent->status === 'Idle' && $agent->currentTaskId === null) {
                // Remove from database
                $this->database->getConnection()->exec("DELETE FROM agents WHERE id = ?", [$agent->id]);
                unset($this->agentCache[$agent->id]);
                $cleanedUp++;
            }
        }

        return $cleanedUp;
    }

    public function getAgentActivityReport(): array
    {
        $report = [];
        $allAgents = $this->getAllAgents();

        foreach ($allAgents as $agent) {
            $report[$agent->id] = [
                'role' => $agent->role->value,
                'status' => $agent->status,
                'last_active' => $agent->lastActive->format('Y-m-d H:i:s'),
                'current_task' => $agent->currentTaskId,
                'is_ephemeral' => $agent->isEphemeral,
                'capabilities' => $agent->capabilities
            ];
        }

        return $report;
    }
}