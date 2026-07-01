<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmxAuctionApiService
{
    protected string $baseUrl;

    protected string $pathPrefix;

    protected string $clientId;

    protected string $clientSecret;

    protected string $oauthPath;

    protected string $exportPendingPath;

    protected string $exportByReceiptPath;

    protected int $timeout;

    protected ?string $lastError = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('tmx_auction.base_url'), '/');
        $this->pathPrefix = config('tmx_auction.path_prefix', '');
        $this->clientId = trim((string) config('tmx_auction.client_id'));
        $this->clientSecret = trim((string) config('tmx_auction.client_secret'));
        $this->oauthPath = config('tmx_auction.oauth_path');
        $this->exportPendingPath = config('tmx_auction.export_pending_path');
        $this->exportByReceiptPath = config('tmx_auction.export_by_receipt_path');
        $this->timeout = config('tmx_auction.timeout', 60);
    }

    protected function url(string $path): string
    {
        return $this->baseUrl . $this->pathPrefix . $path;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Get Bearer token from TMX OAuth (client_credentials).
     */
    public function getAccessToken(): ?string
    {
        $this->lastError = null;
        $url = $this->url($this->oauthPath);

        // TMX Mode B spec: POST with Content-Type: application/json, body { client_id, client_secret, grant_type }
        $body = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ];
        $rawClientId = config('tmx_auction.client_id');
        Log::info('TMX OAuth request', [
            'url' => $url,
            'client_id_sent' => $this->clientId ?: '(empty)',
            'client_id_raw_from_config' => $rawClientId === null ? 'null' : (is_string($rawClientId) ? 'string(len=' . strlen($rawClientId) . ')' : gettype($rawClientId)),
            'client_secret_length' => strlen($this->clientSecret),
            'auth_style' => 'OAuth2 (JSON body per TMX spec)',
        ]);

        try {
            $client = Http::timeout($this->timeout)->acceptJson();
            if (config('tmx_auction.verify_ssl') === false) {
                $client = $client->withOptions(['verify' => false]);
            }
            $response = $client->asJson()->post($url, $body);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? $data['token'] ?? ($data['data']['access_token'] ?? $data['data']['token'] ?? null);
                if ($token) {
                    Log::info('TMX OAuth success, proceeding to export request');
                    return $token;
                }
                Log::warning('TMX OAuth 200 but no token in response', ['response_keys' => array_keys($data ?? [])]);
                $this->lastError = 'OAuth returned 200 but no access_token found in response. Check response format.';
                return null;
            }
            $this->lastError = 'OAuth token: ' . $response->status() . ' ' . $response->reason();
            if ($response->status() === 404) {
                $this->lastError .= ' — URL: ' . $url . ' (check TMX_AUCTION_BASE_URL and TMX_AUCTION_OAUTH_PATH in .env)';
            }
            Log::warning('TMX OAuth failed', ['url' => $url, 'status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error('TMX OAuth exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * POST /export-pending — optional body: from_date, to_date, receipt_no.
     * Returns decoded response or null; response has "data" (array of auction payloads).
     */
    public function exportPending(?string $fromDate = null, ?string $toDate = null, ?int $receiptNo = null): ?array
    {
        $this->lastError = null;
        $token = $this->getAccessToken();
        if (! $token) {
            Log::warning('TMX export-pending skipped: no token from OAuth');
            return null;
        }
        $url = $this->url($this->exportPendingPath);
        $body = array_filter([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'receipt_no' => $receiptNo,
        ], fn ($v) => $v !== null && $v !== '');

        Log::info('TMX export-pending request', ['url' => $url, 'body' => $body]);

        try {
            $client = Http::withToken($token)->timeout($this->timeout)->acceptJson();
            if (config('tmx_auction.verify_ssl') === false) {
                $client = $client->withOptions(['verify' => false]);
            }
            $response = $client->asJson()->post($url, $body);

            if ($response->successful()) {
                return $response->json();
            }
            $this->lastError = 'export-pending: ' . $response->status() . ' ' . $response->reason();
            if ($response->status() === 404) {
                $this->lastError .= ' — URL: ' . $url . ' (check TMX_AUCTION_BASE_URL and paths in .env)';
            }
            $err = $response->json();
            if ($err && ($response->status() === 404 || $response->status() === 422)) {
                $this->lastError = ($err['error'] ?? $err['message'] ?? $this->lastError);
            }
            Log::warning('TMX export-pending failed', ['url' => $url, 'status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error('TMX export-pending exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * POST /export-by-receipt/{receipt_no} — single receipt re-delivery.
     * Returns decoded response or null; response has "data" (single auction object).
     */
    public function exportByReceipt(int $receiptNo): ?array
    {
        $this->lastError = null;
        $token = $this->getAccessToken();
        if (! $token) {
            Log::warning('TMX export-by-receipt skipped: no token from OAuth');
            return null;
        }
        $path = str_replace('{receipt_no}', (string) $receiptNo, $this->exportByReceiptPath);
        $url = $this->url($path);

        Log::info('TMX export-by-receipt request', ['url' => $url, 'receipt_no' => $receiptNo]);

        try {
            $client = Http::withToken($token)->timeout($this->timeout)->acceptJson();
            if (config('tmx_auction.verify_ssl') === false) {
                $client = $client->withOptions(['verify' => false]);
            }
            $response = $client->asJson()->post($url, []);

            if ($response->successful()) {
                return $response->json();
            }
            $this->lastError = 'export-by-receipt: ' . $response->status() . ' ' . $response->reason();
            if ($response->status() === 404) {
                $this->lastError .= ' — URL: ' . $url . ' (check TMX_AUCTION_BASE_URL and paths in .env)';
            }
            $err = $response->json();
            if (isset($err['error'])) {
                $this->lastError = $err['error'];
            }
            Log::warning('TMX export-by-receipt failed', ['url' => $url, 'receipt_no' => $receiptNo, 'status' => $response->status()]);
            return null;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error('TMX export-by-receipt exception', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
