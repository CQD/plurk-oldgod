<?php

namespace Q\OldGod;

use GuzzleHttp\Client as HttpClient;

class VertexAI
{
    public static function call($prompt, $configs = [])
    {
        $payload = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $prompt,
                        ]
                    ],
                ]
            ],
            "generationConfig" => [
                "maxOutputTokens" => 80,
                "temperature" => 2,
                "topP" => 0.95
            ],
            "safetySettings" => [
                [
                    "category" => "HARM_CATEGORY_HATE_SPEECH",
                    "threshold" => "BLOCK_ONLY_HIGH"
                ],
                [
                    "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
                    "threshold" => "BLOCK_ONLY_HIGH"
                ],
                [
                    "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                    "threshold" => "BLOCK_ONLY_HIGH"
                ],
                [
                    "category" => "HARM_CATEGORY_HARASSMENT",
                    "threshold" => "BLOCK_ONLY_HIGH",
                ],
            ]
        ];

        $payload["generationConfig"] = array_merge($payload["generationConfig"], $configs);

        $url = sprintf(
            "https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s",
            "gemini-1.5-flash-latest",
            VERTEX_API_TOKEN,
        );

        $resp = (new HttpClient())->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $bodyRaw = (string) $resp->getBody();
        $body = json_decode($bodyRaw, true);

        if (($body["candidates"] ?? null) === null) {
            qlog(LOG_WARNING, "Bad LLM response: ". json_encode($body, JSON_UNESCAPED_UNICODE));
            throw new \Exception("Bad LLM response");
        } else {
            qlog(LOG_INFO, "LLM response: ". json_encode($body, JSON_UNESCAPED_UNICODE));
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

        return $result_text;
    }

    public function getVertexAIResponse($vertexAIRequest)
    {
        $vertexAIResponse = $this->getVertexAIResponse($vertexAIRequest);
        return $vertexAIResponse;
    }
}