<?php

namespace App\Services;

/**
 * Prompt injection protection for any LLM integration.
 *
 * Usage:
 *   $safe = PromptGuard::wrap('user provided text');
 *   // Returns: a safe string with delimiters and sanitisation
 *
 *   $messages = PromptGuard::buildMessages(
 *       system: "You are a helpful restaurant assistant.",
 *       userInputs: [$userMessage],
 *   );
 *   // Returns: safe message array with user content in USER role, never SYSTEM
 */
class PromptGuard
{
    /**
     * Delimiters used to isolate user input from system instructions.
     * Using XML-style tags for clarity and strong visual separation.
     */
    private const OPEN_DELIMITER  = '<|user_input|>';
    private const CLOSE_DELIMITER = '<|/user_input|>';

    /**
     * Patterns that indicate prompt injection attempts.
     */
    private const INJECTION_PATTERNS = [
        '/ignore\s+(all\s+)?(previous|prior|above|earlier|preceding)\s+(instructions?|prompts?|rules?|directives?)/iu',
        '/you\s+are\s+now\s+(a|an|the)/iu',
        '/system\s*:\s*/iu',
        '/<\|system\|>/iu',
        '/<\|im_start\|>/iu',
        '/<\|im_end\|>/iu',
        '/\[INST\]/iu',
        '/\[/iu',
        '/###\s*(system|assistant|human)/iu',
        '/act\s+as\s+if\s+you\s+(have\s+)?no\s+(rules?|restrictions?|guidelines?)/iu',
        '/pretend\s+you\s+are\s+/iu',
        '/roleplay\s+as\s+/iu',
        '/forget\s+(your|all)\s+(previous|prior|instructions?|rules?)/iu',
        '/override\s+(your|all)\s+(previous|prior|instructions?|rules?)/iu',
        '/jailbreak/iu',
        '/do\s+anything\s+now/iu',
        '/DAN\s+mode/iu',
        '/developer\s+mode/iu',
        '/bypass\s+(your|all)\s+(restrictions?|rules?|guidelines?)/iu',
        '/new\s+instructions?:/iu',
        '/<\|reserved_special_token_\d+\|>/iu',
    ];

    /**
     * Wrap user input in safe delimiters and sanitise.
     *
     * @param  string  $input  Raw user input
     * @return string  Delimited, sanitised string safe to embed in a prompt
     */
    public static function wrap(string $input): string
    {
        $input = self::sanitise($input);

        return self::OPEN_DELIMITER . "\n" . $input . "\n" . self::CLOSE_DELIMITER;
    }

    /**
     * Check if user input contains prompt injection patterns.
     * Returns true if suspicious patterns are found.
     */
    public static function detectInjection(string $input): bool
    {
        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a safe message array for LLM APIs.
     * User content is ALWAYS placed in the USER role — never SYSTEM.
     * System prompt is isolated and cannot be overridden by user input.
     *
     * @param  string       $system      System prompt text
     * @param  string[]     $userInputs  Array of user input strings
     * @param  string|null  $context     Optional context (e.g. order data) — treated as system context
     * @return array[]      Safe message array for OpenAI/Anthropic/compatible APIs
     */
    public static function buildMessages(
        string $system,
        array $userInputs,
        ?string $context = null,
    ): array {
        $messages = [];

        // System prompt — isolated, no user content allowed to leak here
        $systemContent = $system;

        if ($context !== null) {
            $systemContent .= "\n\n<|context|>\n" . $context . "\n<|/context|>";
        }

        $messages[] = [
            'role'    => 'system',
            'content' => $systemContent,
        ];

        // Each user input is wrapped in delimiters
        foreach ($userInputs as $index => $input) {
            $messages[] = [
                'role'    => 'user',
                'content' => self::wrap($input),
            ];
        }

        return $messages;
    }

    /**
     * Sanitise user input to neutralise injection attempts.
     * Strips control characters and neutralises common injection markers.
     */
    public static function sanitise(string $input): string
    {
        // Strip null bytes and other control characters (keep newlines and tabs)
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);

        // Neutralise XML/HTML-like tags that could confuse delimiter parsing
        // (but don't strip them entirely — they might be legitimate content)
        $input = str_replace(
            ['<|', '|>'],
            ['< !', '!>'],
            $input,
        );

        return $input;
    }

    /**
     * Validate and sanitise input for a specific LLM use case.
     * Returns ['safe' => bool, 'input' => string, 'reason' => string|null]
     */
    public static function validate(string $input, int $maxLength = 10000): array
    {
        if (mb_strlen($input) > $maxLength) {
            return [
                'safe'   => false,
                'input'  => self::wrap(mb_substr($input, 0, $maxLength)),
                'reason' => "Input exceeds maximum length of {$maxLength} characters.",
            ];
        }

        if (self::detectInjection($input)) {
            return [
                'safe'   => false,
                'input'  => self::wrap($input),
                'reason' => 'Input contains potential prompt injection patterns.',
            ];
        }

        return [
            'safe'   => true,
            'input'  => self::wrap($input),
            'reason' => null,
        ];
    }
}
