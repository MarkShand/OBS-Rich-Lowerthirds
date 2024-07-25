<?php
    $action = $_GET['action'];
    $name = ucfirst($action);
    $table = $action;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            padding: 0px;
        }
        .table td, .table th {
            vertical-align: middle;
        }
    </style>
</head>
<body style="zoom:0.7;">
    <div class="container mt-2">
        <div class="input-group mb-3">
            <input type="text" id="messageInput" onchange="createMessage()" class="form-control" placeholder="Add <?= $name ?>">
            <!-- <div class="input-group-append">
                <button class="btn btn-primary" onclick="createMessage()">Create</button>
            </div> -->
        </div>
        <div class="input-group mb-3">
            <input type="text" id="searchInput" class="form-control" onkeyup="searchMessages()" placeholder="Search <?= $name?>">
            <div class="input-group-append">
                <button class="btn btn-secondary" onclick="searchMessages()">Search</button>
            </div>
        </div>
        <div class="input-group mb-3">
            <select id="datalist-channels" class="form-control"></select>
            <!-- <button class="btn btn-info" onclick="toggleAutoplay()">Toggle Autoplay</button> -->

            <?php if($name == 'Headlines'): ?>
            <input type="checkbox" class="btn-check" id="btn-check-outlined" onchange="autoplayToggle(this)" autocomplete="off">
            <label class="btn btn-outline-primary" for="btn-check-outlined">Autoplay</label>
            <input type="number" min="4" value="" id="autoplayDelayInput" onkeyup="saveAutoplayDelay(this)" onchange="saveAutoplayDelay(this)" class="form-control" placeholder="Delay in seconds">
            <?php endif; ?>
         </div>
        <table id="messagesTable" class="table table-bordered">
            <thead>
                <tr>
                    <th><?= $name ?></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <script>
        let currentMessage = '';
        let autoplayInterval;

        function recallDelay() {

            if (localStorage.getItem("autoplayDelay")) {
                const delay = localStorage.getItem("autoplayDelay");
                const d = delay / 1000
                console.log(d);
                $('#autoplayDelayInput').val(d)
            } else {
                $('#autoplayDelayInput').val(4)
            }
        }
        function loadChannels(){
            $.getJSON('db?table=channels', function(data){
                const datalistChannel = $('#datalist-channels');
                datalistChannel.empty();
                data.forEach(text => {
                    datalistChannel.append(`<option value="<?=$name?>-${text.text}"><?=$name?>-${text.text}</option>`);
                });
            });
        }

        function saveAutoplayDelay(input){
            const delay = $(input).val();
            if(delay <= 4){
                localStorage.setItem("autoplayDelay",4000);
            }else{
                const d = delay * 1000
                localStorage.setItem("autoplayDelay",d);
            }
        }

        function loadMessages() {
            $.getJSON('db?table=<?=$table?>', function(data) {
                const messagesTable = $('#messagesTable tbody');
                messagesTable.empty();
                data.forEach(message => {
                    messagesTable.append(`
                        <tr>
                            <td contenteditable="false" data-id="${message.id}">${message.text}</td>
                            <td>
                                <button class="btn btn-success btn-sm" data-message="${message.text}" onclick="toggleMessage(this, this)">Show</button>
                                <button class="btn btn-danger btn-sm" onclick="startDeleteCountdown(${message.id}, this)">Delete</button>
                                <button class="btn btn-warning btn-sm" onclick="toggleEdit(${message.id}, this)">Update</button>
                            </td>
                        </tr>
                    `);
                });
            });
        }
        function createMessage() {
            const message = $('#messageInput').val();
            $.post('db?table=<?=$table?>', { action: 'create', text: message }, function() {
                loadMessages();
                $('#messageInput').val('');
            });
        }
        
        function deleteMessage(id, button) {
            $.post('db?table=<?=$table?>', { action: 'delete', id: id }, function() {
                loadMessages();
            });
        }

        function startDeleteCountdown(id, button) {
            if ($(button).hasClass('countdown')) {
                clearInterval($(button).data('interval'));
                $(button).removeClass('countdown');
                $(button).text('Delete');
            } else {
                $(button).addClass('countdown');
                let countdown = 5;
                $(button).text(`Cancel (${countdown}s)`);

                const interval = setInterval(() => {
                    countdown--;
                    $(button).text(`Cancel (${countdown}s)`);
                    if (countdown <= 0) {
                        clearInterval(interval);
                        deleteMessage(id, button);
                    }
                }, 1000);

                $(button).data('interval', interval);
            }
        }

        function autoplayToggle(button) {
            const isChecked = $(button).is(":checked");

            if (isChecked) {
                if (autoplayInterval) {
                    // If autoplay is already running, stop it
                    clearInterval(autoplayInterval);
                    autoplayInterval = null;
                    $('#datalist-channels').prop('disabled', false);
                } else {
                    // Start autoplay
                    const broadcast_channel = $('#datalist-channels').val();
                    if (!broadcast_channel) {
                        console.log('Please select a broadcast channel.');
                        return;
                    }

                    $('#datalist-channels').prop('disabled', true);
                    const messages = $('#messagesTable tbody tr td:first-child');
                    let index = 0;

                    // Retrieve and parse autoplayDelay
                    const autoplayDelay = parseInt(localStorage.getItem('autoplayDelay'), 10);
                    // Use a default value if autoplayDelay is invalid
                    const delay = isNaN(autoplayDelay) ? 4000 : autoplayDelay; // Default to 3000 ms

                    // Start the interval
                    autoplayInterval = setInterval(() => {
                        const messageText = messages.eq(index).text();
                        const channel = new BroadcastChannel(broadcast_channel);
                        channel.postMessage({ action: 'show', text: messageText });
                        index = (index + 1) % messages.length;
                    }, delay);
                }
            } else {
                // If checkbox is unchecked, stop autoplay
                clearInterval(autoplayInterval);
                autoplayInterval = null;
                $('#datalist-channels').prop('disabled', false);
            }
        }


        function toggleMessage(button, text) {
            const message = $(text).attr('data-message');
            const broadcast_channel = $('#datalist-channels').val();
            const channel = new BroadcastChannel(broadcast_channel);

            if (currentMessage === message) {
                channel.postMessage({ action: 'hide' });
                currentMessage = '';
                button.textContent = 'Show';
            } else {
                channel.postMessage({ action: 'show', text: message });
                currentMessage = message;
                $('#messagesTable button:contains("Hide")').text('Show');
                button.textContent = 'Hide';
            }
        }

        function setChannels() {
            $.getJSON('db?table=channels', function(data) {
                const channelList = $('#datalist-channels');
                channelList.empty();
                data.forEach(text => {
                    channelList.append(`<option value="${text.text}">${text.text}</option>`);
                });
            });
        }

        function searchMessages() {
            const searchText = $('#searchInput').val().toLowerCase();
            $.getJSON('db?table=<?=$table?>', function(data) {
                const messagesTable = $('#messagesTable tbody');
                messagesTable.empty();
                data.forEach(message => {
                    if (message.text.toLowerCase().includes(searchText)) {
                        messagesTable.append(`
                            <tr>
                                <td contenteditable="false" data-id="${message.id}">${message.text}</td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="toggleMessage("${message.text}", this)">Show</button>
                                    <button class="btn btn-danger btn-sm" onclick="startDeleteCountdown(${message.id}, this)">Delete</button>
                                    <button class="btn btn-warning btn-sm" onclick="toggleEdit(${message.id}, this)">Update</button>
                                </td>
                            </tr>
                        `);
                    }
                });
            });
        }

        function updateMessage(id, newText) {
            $.post('db?table=<?=$table?>', { action: 'update', id: id, text: newText }, function() {
                loadMessages();
            });
        }

        function toggleEdit(id, button) {
            const row = $(button).closest('tr');
            const messageCell = row.find('td:first');
            const isEditable = messageCell.attr('contenteditable') === 'true';

            if (isEditable) {
                const newText = messageCell.text();
                updateMessage(id, newText);
                messageCell.attr('contenteditable', 'false');
                button.textContent = 'Update';
            } else {
                messageCell.attr('contenteditable', 'true').focus();
                button.textContent = 'Save';
                messageCell.on('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        const newText = messageCell.text();
                        updateMessage(id, newText);
                        messageCell.attr('contenteditable', 'false');
                        button.textContent = 'Update';
                    }
                });
            }
        }

        $(document).ready(function() {
            loadMessages();
            loadChannels();
           recallDelay();
        });
    </script>
</body>
</html>
