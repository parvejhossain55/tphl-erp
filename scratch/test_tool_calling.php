<?php
// Test script to check tool calling behavior of Gemini 2.5 Flash.

$apiKey = "AIzaSyAxHTAWdnEx0ZubmHkC6t2rrWHCkoCBcfg";
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$schemaText = "Table: projects (id, name, status, created_at)";
$message = "আমাদের মোট কয়টি প্রজেক্ট আছে?";

$data = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => "Database Schema:\n" . $schemaText . "\n\nUser Question: " . $message]
            ]
        ]
    ],
    "systemInstruction" => [
        "parts" => [
            [
                "text" => "You are a helpful and intelligent company ERP assistant. " .
                          "Answer user queries accurately by calling the 'run_database_query' tool to query the database. " .
                          "Always respond in natural, friendly Bengali language (বাংলা)."
            ]
        ]
    ],
    "tools" => [
        [
            "functionDeclarations" => [
                [
                    "name" => "run_database_query",
                    "description" => "Execute a read-only SELECT SQL query on the database to fetch projects, bookings, leads, floor setups, materials, expenses, and employee counts. Output must be a valid SELECT query. Do not perform any mutations.",
                    "parameters" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "query" => [
                                "type" => "STRING",
                                "description" => "The SELECT SQL query to execute."
                            ]
                        ],
                        "required" => ["query"]
                    ]
                ]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

echo "Gemini Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
