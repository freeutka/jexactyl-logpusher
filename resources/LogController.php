<?php

namespace Jexactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Jexactyl\Models\SettingPaste;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jexactyl\Http\Controllers\Controller;

class LogController extends Controller
{
    public function sendLog(Request $request)
    {
        $request->validate([
            'log' => 'required|string',
        ]);

        $logContent = $request->input('log');
        $service = SettingPaste::getValue('log_service', 'mclogs');

        try {
            if ($service === 'clbin') {
                $response = $this->sendToClbin($logContent);
            } elseif ($service === 'paste.rs') {
                $response = $this->sendToPasteRs($logContent);
            } elseif ($service === 'termbin') {
                $response = $this->sendToTermbin($logContent);
            } else {
                $response = $this->sendToMclogs($logContent);
            }

            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'url' => $response['url'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error sending log',
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in sendLog: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
            ], 500);
        }
    }

    private function sendToMclogs(string $logContent)
    {
        try {
            $response = Http::asForm()->post('https://api.mclo.gs/1/log', [
                'content' => $logContent,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'url' => $response->json()['url'],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error sending log to mclo.gs: ' . $e->getMessage());
        }

        return [
            'success' => false,
        ];
    }

    private function sendToClbin(string $logContent)
    {
        try {
            $response = Http::asForm()->post('https://clbin.com', [
                'clbin' => $logContent,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'url' => $response->body(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error sending log to clbin.com: ' . $e->getMessage());
        }

        return [
            'success' => false,
        ];
    }

    private function sendToPasteRs(string $logContent)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'text/plain',
            ])->post('https://paste.rs/', $logContent);

            if (in_array($response->status(), [201, 206])) {
                return [
                    'success' => true,
                    'url' => $response->body(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error sending log to paste.rs: ' . $e->getMessage());
        }

        return [
            'success' => false,
        ];
    }

    private function sendToTermbin(string $logContent)
    {
        try {
            $process = proc_open(
                'nc termbin.com 9999',
                [
                    0 => ["pipe", "r"], // stdin
                    1 => ["pipe", "w"], // stdout
                    2 => ["pipe", "w"], // stderr
                ],
                $pipes
            );

            if (is_resource($process)) {
                fwrite($pipes[0], $logContent);
                fclose($pipes[0]);

                $url = stream_get_contents($pipes[1]);
                fclose($pipes[1]);

                $error = stream_get_contents($pipes[2]);
                fclose($pipes[2]);

                $return_value = proc_close($process);

                if ($return_value == 0) {
                    return [
                        'success' => true,
                        'url' => trim($url),
                    ];
                } else {
                    Log::error('Error sending log to termbin.com: ' . $error);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error sending log to termbin.com: ' . $e->getMessage());
        }

        return [
            'success' => false,
        ];
    }
}
