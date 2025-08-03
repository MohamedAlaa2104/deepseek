<?php

namespace Mohamedaladdin\Deepseek;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;
class DeepseekService
{
    public function __construct()
    {
    }

    /**
     * Sends messages to the Deepseek chat completion API.
     *
     * @param array $messages An array of message objects, e.g., [['role' => 'user', 'content' => 'Hello!']]
     * @param string $model The model to use (e.g., 'deepseek-chat')
     * @return Response The HTTP response from the API.
     */
    public function getChatCompletion(array $messages, string $model = 'deepseek-chat'): Response
    {
        return Http::withToken(config('deepseek.api_key'))
        ->timeout(config('deepseek.timeout'))
        ->post('https://api.deepseek.com/v1/chat/completions', [
            'messages'  => $messages,
            'model'     => $model,
        ]);
    }

    public function getChatCompletionStream(array $messages, string $model = 'deepseek-chat', ?callable $callback = null)
    {
        $apiKey = config('deepseek.api_key');
        $timeout = config('deepseek.timeout');
        $url = 'https://api.deepseek.com/v1/chat/completions';

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: text/event-stream',
        ];

        $payload = json_encode([
            'messages'  => $messages,
            'model'     => $model,
            'stream'    => true,
        ]);

        $fullText = '';

        $streamResponse = response()->stream(function () use ($url, $headers, $payload, $timeout, &$fullText) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

            $phraseBuffer = '';

            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$phraseBuffer, &$fullText) {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (str_starts_with($line, 'data: ')) {
                        $json = substr($line, 6);
                        if ($json === '[DONE]') {
                            if (strlen(trim($phraseBuffer)) > 0) {
                                echo "data: " . $phraseBuffer . "\n\n";
                                ob_flush();
                                flush();
                                $phraseBuffer = '';
                            }
                            echo "event: done\ndata: [DONE]\n\n";
                            ob_flush();
                            flush();
                            continue;
                        }
                        $obj = json_decode($json, true);
                        if (isset($obj['choices'][0]['delta']['content'])) {
                            $content = $obj['choices'][0]['delta']['content'];
                            $phraseBuffer .= $content;
                            $fullText .= $content;

                            while (preg_match('/^(.*?[\.!?,])(\s|$)/u', $phraseBuffer, $matches)) {
                                $phrase = $matches[1];
                                echo "data: " . $phrase . "\n\n";
                                ob_flush();
                                flush();
                                $phraseBuffer = ltrim(substr($phraseBuffer, strlen($phrase)));
                            }
                        }
                    }
                }
                return strlen($data);
            });

            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_setopt($ch, CURLOPT_BUFFERSIZE, 1);

            $result = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($result === false) {
                echo "event: error\ndata: " . json_encode(['error' => $error]) . "\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);

        // Return both the stream response and the full text for backend use
        return [
            'stream' => $streamResponse,
            'full' => &$fullText,
        ];
    }
}