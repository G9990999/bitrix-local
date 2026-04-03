<?php
namespace Local\RoleModel;

/**
 * Calls the Python prediction service (REST microservice or CLI).
 *
 * The Python model can be run as a microservice:
 *   uvicorn api:app --host 127.0.0.1 --port 8765
 *
 * Or configured to use CLI mode for simple integrations.
 */
class PredictionService
{
    /** Base URL of the Python FastAPI microservice */
    private string $serviceUrl;

    public function __construct(string $serviceUrl = 'http://127.0.0.1:8765')
    {
        $this->serviceUrl = rtrim($serviceUrl, '/');
    }

    /**
     * Predict role permissions for a given position.
     *
     * @param string $position  Job title / position description
     * @return array<string, bool>  Map of role_name => enabled
     * @throws \RuntimeException on communication error
     */
    public function predict(string $position): array
    {
        $url = $this->serviceUrl . '/predict';
        $payload = json_encode(['position' => $position], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("Role model service error: $error");
        }
        if ($httpCode !== 200) {
            throw new \RuntimeException("Role model service returned HTTP $httpCode: $response");
        }

        $data = json_decode($response, true);
        if (!isset($data['permissions'])) {
            throw new \RuntimeException("Invalid response from role model service");
        }

        return $data['permissions'];
    }

    /**
     * Predict and return as CSV string.
     */
    public function predictCsv(string $position): string
    {
        $permissions = $this->predict($position);
        $lines = ["role,enabled"];
        foreach ($permissions as $role => $enabled) {
            $lines[] = '"' . addslashes($role) . '",' . ($enabled ? 'true' : 'false');
        }
        return implode("\n", $lines);
    }

    /**
     * Get list of all available roles.
     */
    public function getRoles(): array
    {
        $url = $this->serviceUrl . '/roles';
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        return $data['roles'] ?? [];
    }
}
