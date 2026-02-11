<?php
$url = 'http://localhost/kweza-app/backend/api/login.php';
$data = ['phone' => '09900', 'pin' => '1234'];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Response: " . $result;
?>
