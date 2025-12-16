<?php

namespace MultiPersona\Services;

class SchemaRegistry
{
    private array $schemas = [];

    public function registerSchema(string $name, array $schema): void
    {
        $this->schemas[$name] = $schema;
    }

    public function getSchema(string $name): ?array
    {
        return $this->schemas[$name] ?? null;
    }

    public function validate(string $schemaName, array $data): bool
    {
        $schema = $this->getSchema($schemaName);
        if (!$schema) {
            return false;
        }

        // Simple validation: check if all keys in schema exist in data
        foreach ($schema as $key => $type) {
            if (!array_key_exists($key, $data)) {
                return false;
            }
            // Basic type checking could go here
        }

        return true;
    }
}
