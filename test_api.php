<?php
define('GEMINI_API_KEY', 'AIzaSyDfPEltbig7De0Oav8l9fXNkVSvs9Vac-Y');
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . GEMINI_API_KEY;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Hi"]
            ]
        ]
    ]
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "RESULT:\n";
var_dump($result);
if ($result === false) {
    echo "ERROR:\n";
    print_r(error_get_last());
} else {
    echo "RESPONSE BODY:\n";
    echo $result;
}
?>
