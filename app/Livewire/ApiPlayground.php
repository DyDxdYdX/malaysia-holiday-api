<?php

namespace App\Livewire;

use App\Support\MalaysiaStates;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ApiPlayground extends Component
{
    public string $endpoint = 'holidays';

    public string $year = '2026';

    public string $state = '';

    public string $scope = '';

    public string $type = '';

    public string $date = '';

    public bool $includeSource = false;

    public ?string $responseJson = null;

    public ?int $responseStatus = null;

    public bool $isLoading = false;

    public ?string $errorMessage = null;

    /**
     * Reset parameters when the endpoint changes so stale inputs don't bleed across.
     */
    public function updatedEndpoint(): void
    {
        $this->year = '2026';
        $this->state = '';
        $this->scope = '';
        $this->type = '';
        $this->date = '';
        $this->includeSource = false;
        $this->responseJson = null;
        $this->responseStatus = null;
        $this->errorMessage = null;
    }

    /**
     * Build the query parameters for the current endpoint configuration.
     *
     * @return array<string, string|bool>
     */
    #[Computed]
    public function queryParams(): array
    {
        return match ($this->endpoint) {
            'holidays' => array_filter([
                'year' => $this->year,
                'state' => $this->state ?: null,
                'scope' => $this->scope ?: null,
                'type' => $this->type ?: null,
                'include_source' => $this->includeSource ? '1' : null,
            ]),
            'holidays/check' => array_filter([
                'date' => $this->date ?: null,
                'state' => $this->state ?: null,
            ]),
            default => [],
        };
    }

    /**
     * Build the full request URL shown in the playground.
     */
    #[Computed]
    public function requestUrl(): string
    {
        $base = url("/api/v1/{$this->endpoint}");
        $params = $this->queryParams;

        return $params ? $base.'?'.http_build_query($params) : $base;
    }

    /**
     * Build a curl command equivalent for the current request.
     */
    #[Computed]
    public function curlCommand(): string
    {
        return "curl \"{$this->requestUrl}\"";
    }

    /**
     * Send the API request and store the formatted JSON response.
     */
    public function sendRequest(): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;
        $this->responseJson = null;
        $this->responseStatus = null;

        try {
            $response = Http::timeout(10)->get($this->requestUrl);
            $this->responseStatus = $response->status();
            $this->responseJson = json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Request failed: '.$e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function render(): View
    {
        return view('livewire.api-playground', [
            'stateOptions' => MalaysiaStates::options(),
            'scopeOptions' => ['federal', 'state', 'custom'],
            'typeOptions' => ['federal', 'state', 'replacement', 'additional', 'custom'],
        ]);
    }
}
