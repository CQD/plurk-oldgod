<?php

namespace Q\OldGod;

use GuzzleHttp\Client as HttpClient;

class VertexAI
{
    public static function call(
        string|array $contents = "",
        string $model = "gemini-2.0-flash-exp",
        string|null $system_prompt = null,
        array $configs = [],
        int $maxOutputTokens = 300,
    )
    {
        if (is_string($contents)) {
            $contents = [
                [
                    "parts" => [
                        [
                            "text" => $contents,
                        ],
                    ],
                ],
            ];
        }

        $configs = $configs + [
            "maxOutputTokens" => $maxOutputTokens,
            "temperature" => 2,
        ];

        $payload = [
            "contents" => $contents,
            "generationConfig" => $configs,
            "safetySettings" => [
                [
                    "category" => "HARM_CATEGORY_HATE_SPEECH",
                    "threshold" => "BLOCK_NONE"
                ],
                [
                    "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
                    "threshold" => "BLOCK_NONE"
                ],
                [
                    "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                    "threshold" => "BLOCK_NONE"
                ],
                [
                    "category" => "HARM_CATEGORY_HARASSMENT",
                    "threshold" => "BLOCK_NONE",
                ],
            ]
        ];

        if ($system_prompt) {
            $payload["systemInstruction"] = [
                "parts" => [
                    "text" => $system_prompt,
                ],
            ];
        }

        $payload["generationConfig"] = array_merge($payload["generationConfig"], $configs);

        // note: 不要紀錄 payload，避免問題被記錄下來。 LLM response 還是會記錄，不然太難 debug
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = explode(':', $host)[0];
        if (in_array($host, ['localhost', '', '127.0.0.1'])) {
            $user_prompt = array_reduce($contents, function ($carry, $item) {
                return $carry . $item["parts"][0]["text"];
            }, "");
            qlog(LOG_DEBUG, "prompt:\n" . "------------\nsystem prompt\n------------\n" . $system_prompt . "\n\n------------\ncontent\n------------\n" . $user_prompt);
        }

        $url = sprintf(
            "https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s",
            urlencode($model),
            urlencode(VERTEX_API_TOKEN),
        );

        $start_time = microtime(true);
        $resp = (new HttpClient())->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $bodyRaw = (string) $resp->getBody();
        $end_time = microtime(true);

        $body = json_decode($bodyRaw, true);
        $body["_time_used"] = $end_time - $start_time;

        if (($body["candidates"] ?? null) === null) {
            qlog(LOG_WARNING, "Bad LLM response: ". json_encode($body, JSON_UNESCAPED_UNICODE));
            throw new \Exception("Bad LLM response");
        }

        $result_text = "";
        foreach ($body["candidates"] as $candidate) {
            if (($candidate["finishReason"] ?? null) === "SAFETY") {
                throw new \Exception("Safety block");
            }

            if (($candidate["content"]["parts"] ?? null) === null) {
                qlog(LOG_WARNING, "Bad candidate format: ". json_encode($candidate, JSON_UNESCAPED_UNICODE));
                continue;
            }

            foreach ($candidate["content"]["parts"] as $part) {
                $result_text .= $part["text"];
            }
        }

        qlog(LOG_INFO, "LLM response:\n". $result_text);

        return $result_text;
    }
}
