<?php

namespace App\Http\Middleware;

use App\Services\PromptGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Middleware to protect LLM/AI endpoints from prompt injection.
 *
 * Apply to any route that forwards user input to an LLM:
 *   Route::post('/ai/chat', [...])->middleware('prompt.guard');
 *
 * The middleware:
 *  1. Validates input length
 *  2. Scans for prompt injection patterns
 *  3. Wraps user input in safe delimiters
 *  4. Attaches sanitised messages to the request for downstream use
 */
class PromptInjectionGuard
{
    public function handle(Request $request, Closure $next)
    {
        // Collect all user-provided text fields that would be sent to an LLM
        $userInputs = [];
        $fieldsToCheck = ['message', 'prompt', 'input', 'query', 'text', 'content', 'instructions'];

        foreach ($fieldsToCheck as $field) {
            if ($request->filled($field)) {
                $userInputs[$field] = $request->input($field);
            }
        }

        // Also check nested 'messages' array (OpenAI/Anthropic format)
        if ($request->filled('messages') && is_array($request->input('messages'))) {
            foreach ($request->input('messages') as $index => $msg) {
                if (isset($msg['role']) && $msg['role'] === 'user' && isset($msg['content'])) {
                    $userInputs["messages.{$index}.content"] = $msg['content'];
                }
            }
        }

        // Validate and sanitise each input
        $sanitised = [];
        foreach ($userInputs as $field => $input) {
            $validation = PromptGuard::validate($input);

            if (!$validation['safe']) {
                Log::channel('security')->warning('Prompt injection attempt blocked', [
                    'field'    => $field,
                    'reason'   => $validation['reason'],
                    'user_id'  => $request->user()?->id,
                    'path'     => $request->path(),
                    'ip'       => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Your input could not be processed. Please rephrase.',
                    'code'    => 'INPUT_REJECTED',
                ], 422);
            }

            $sanitised[$field] = $validation['input'];
        }

        // Attach sanitised inputs to request for downstream controllers
        $request->merge(['_sanitised_inputs' => $sanitised]);

        return $next($request);
    }
}
