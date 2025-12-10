<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
include '../../includes/header.php';
?>

<div class="container py-4">
    <div class="chat-container d-flex">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="p-3 border-bottom bg-white">
                <h5 class="mb-0 fw-bold">Messages</h5>
                <div class="mt-2 position-relative">
                    <input type="text" class="form-control form-control-sm rounded-pill ps-4" placeholder="Search...">
                    <i class="fas fa-search position-absolute text-muted"
                        style="left: 10px; top: 8px; font-size: 0.8rem;"></i>
                </div>
            </div>
            <div class="chat-list" id="conversationList">
                <!-- Conversations will be loaded here -->
                <div class="text-center p-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Loading...</span>
                </div>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-main">
            <div id="chatDefaultState"
                class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                <div class="bg-light rounded-circle p-4 mb-3">
                    <i class="far fa-comments fa-3x text-primary"></i>
                </div>
                <h5>Select a conversation</h5>
                <p>Choose a contact to start messaging</p>
            </div>

            <div id="chatActiveState" class="d-none h-100 flex-column">
                <div class="chat-header">
                    <div class="d-flex align-items-center">
                        <img src="https://placehold.co/40" class="rounded-circle me-3" id="chatHeaderImg">
                        <div>
                            <h6 class="mb-0 fw-bold" id="chatHeaderName">User Name</h6>
                            <small class="text-success" id="chatHeaderStatus"><i class="fas fa-circle fa-xs me-1"></i>
                                Online</small>
                        </div>
                    </div>
                    <button class="btn btn-light btn-sm rounded-circle"><i class="fas fa-ellipsis-v"></i></button>
                </div>

                <div class="chat-messages" id="messageList">
                    <!-- Messages will be loaded here -->
                </div>

                <div class="chat-attachment-preview" id="attachmentPreview">
                    <div class="d-flex align-items-center justify-content-between">
                        <span id="fileName" class="small fw-bold"></span>
                        <button type="button" class="btn-close btn-sm" onclick="clearAttachment()"></button>
                    </div>
                </div>

                <div class="chat-input-area">
                    <form id="messageForm" class="d-flex align-items-center gap-2">
                        <input type="hidden" id="activeConversationId">
                        <label class="btn btn-light text-muted rounded-circle" title="Attach File">
                            <i class="fas fa-paperclip"></i>
                            <input type="file" id="fileInput" name="attachment" hidden
                                onchange="handleFileSelect(this)">
                        </label>
                        <input type="text" class="form-control rounded-pill" id="messageInput"
                            placeholder="Type a message..." autocomplete="off">
                        <button type="submit" class="btn btn-primary rounded-circle shadow-sm">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentConversationId = null;
    let pollInterval = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadConversations();

        // Poll for new messages every 3 seconds
        setInterval(loadConversations, 5000);
    });

    function loadConversations() {
        fetch('api.php?action=get_conversations')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const list = document.getElementById('conversationList');
                    // Only update if content changed to avoid flickering (simplified for now)
                    let html = '';
                    if (data.data.length === 0) {
                        html = '<div class="text-center p-4 text-muted"><small>No conversations yet</small></div>';
                    } else {
                        data.data.forEach(conv => {
                            const activeClass = currentConversationId == conv.id ? 'active' : '';
                            const unreadBadge = conv.unread_count > 0 ? `<span class="badge bg-danger rounded-pill ms-auto">${conv.unread_count}</span>` : '';
                            const lastMsg = conv.last_message ? (conv.last_message.length > 30 ? conv.last_message.substring(0, 30) + '...' : conv.last_message) : '<i>Attachment</i>';

                            html += `
                            <div class="chat-item ${activeClass}" onclick="openConversation(${conv.id}, '${conv.other_user_name}', ${conv.other_user_id})">
                                <div class="d-flex align-items-center">
                                    <img src="https://placehold.co/40" class="rounded-circle me-3">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 fw-bold text-truncate">${conv.other_user_name}</h6>
                                            <small class="text-muted" style="font-size: 0.7rem;">${conv.time_ago}</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <p class="mb-0 text-muted small text-truncate flex-grow-1">${lastMsg}</p>
                                            ${unreadBadge}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        });
                    }
                    list.innerHTML = html;
                }
            });
    }

    function openConversation(id, name, userId) {
        currentConversationId = id;
        document.getElementById('activeConversationId').value = id;
        document.getElementById('chatHeaderName').innerText = name;

        document.getElementById('chatDefaultState').classList.add('d-none');
        document.getElementById('chatDefaultState').classList.remove('d-flex');
        document.getElementById('chatActiveState').classList.remove('d-none');
        document.getElementById('chatActiveState').classList.add('d-flex');

        loadMessages();

        // Start polling messages for this conversation
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(loadMessages, 3000);

        // Mark as read
        fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=mark_read&conversation_id=${id}`
        });
    }

    function loadMessages() {
        if (!currentConversationId) return;

        fetch(`api.php?action=get_messages&conversation_id=${currentConversationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const list = document.getElementById('messageList');
                    let html = '';
                    const currentUserId = <?php echo $user_id; ?>;

                    data.data.forEach(msg => {
                        const type = msg.sender_id == currentUserId ? 'sent' : 'received';
                        let content = msg.message;
                        if (msg.attachment) {
                            const fileName = msg.attachment.split('/').pop();
                            content += `<div class="mt-2"><a href="/inkmybook/${msg.attachment}" target="_blank" class="btn btn-sm btn-light border"><i class="fas fa-file-download me-1"></i> ${fileName}</a></div>`;
                        }

                        html += `
                        <div class="message ${type}">
                            ${content}
                            <span class="message-time">${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                        </div>
                    `;
                    });

                    // Only scroll if near bottom or first load (simplified: always scroll for now)
                    const shouldScroll = true;
                    list.innerHTML = html;
                    if (shouldScroll) list.scrollTop = list.scrollHeight;
                }
            });
    }

    document.getElementById('messageForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const input = document.getElementById('messageInput');
        const fileInput = document.getElementById('fileInput');
        const message = input.value.trim();

        if (!message && !fileInput.files.length) return;

        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('conversation_id', currentConversationId);
        formData.append('message', message);
        if (fileInput.files.length) {
            formData.append('attachment', fileInput.files[0]);
        }

        // Optimistic UI update
        const list = document.getElementById('messageList');
        const tempHtml = `
        <div class="message sent opacity-75">
            ${message} ${fileInput.files.length ? '<br>[Uploading file...]' : ''}
            <span class="message-time">Sending...</span>
        </div>
    `;
        list.insertAdjacentHTML('beforeend', tempHtml);
        list.scrollTop = list.scrollHeight;

        input.value = '';
        clearAttachment();

        fetch('api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    loadMessages(); // Reload to get server timestamp and real file path
                    loadConversations(); // Update sidebar list order
                }
            });
    });

    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            document.getElementById('fileName').innerText = input.files[0].name;
            document.getElementById('attachmentPreview').style.display = 'block';
        }
    }

    function clearAttachment() {
        document.getElementById('fileInput').value = '';
        document.getElementById('attachmentPreview').style.display = 'none';
    }
</script>

<?php include '../../includes/footer.php'; ?>