<?php
$action = explode('/',$_GET['action']);
$name = ucfirst($action[0]);
$table = $action[0];

$channelNumber = $action[1];

$spaces = [
    'headlines' => 'normal',
    'programs' => 'nowrap',
    'announcers' => 'nowrap'
];
$spaces = [
    'headlines' => 'normal',
    'programs' => 'nowrap',
    'announcers' => 'nowrap'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $name ?> Receiver</title>
    
    <style>
        body {
            margin: 0;
            overflow: hidden;
            background-color: transparent;
        }
        #message {
            position: absolute;
            bottom: 10%;
            left: 0;
            transform: translateX(-100%);
            font-size: 2rem;
            background-color: white;
            padding: 10px 20px;
            border-radius: 5px;
            white-space: <?= $spaces[$action[0]]?>;
            visibility: visible !important;
            
            font-family: Arial, Helvetica, sans-serif;
            /* transition: transform 1s ease, visibility 0s 1s; */
        }
        .show {
            visibility: visible !important;
            transform: translateX(0) !important;
            transition: transform 1s ease, visibility 0s 1s;
        }
        .hide {
            visibility: hidden !important;
            transform: translateX(-100%) !important;
            transition: transform 1s ease, visibility 0s 1s;
        }
    </style>
</head>
<body>
    <div id="message"></div>

    <script>
        const messageElement = document.getElementById('message');
        const channel = new BroadcastChannel('<?=$name?>-Channel-<?=$channelNumber?>');
        console.log(channel);
        channel.onmessage = (event) => {
            const { action, text } = event.data;
            if (action === 'show') {
                messageElement.textContent = text;
                messageElement.classList.remove('hide');
                messageElement.classList.add('show');
            } else if (action === 'hide') {
                messageElement.classList.remove('show');
                messageElement.classList.add('hide');
            }
        };
    </script>
</body>
</html>
