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
}