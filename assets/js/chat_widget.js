document.addEventListener('DOMContentLoaded', function () {
    const chatBtn = document.getElementById('chatWidgetBtn');
    const chatWindow = document.getElementById('chatWidgetWindow');
    const closeBtn = document.getElementById('chatCloseBtn');
    const toggleBtns = document.querySelectorAll('.chat-toggle-btn');
    const chatBodies = document.querySelectorAll('.chat-body');
    const chatFooter = document.getElementById('chatFooter');

    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSendBtn');
    const emojiBtn = document.getElementById('chatEmojiBtn');
    const fileBtn = document.getElementById('chatFileBtn');
    const micBtn = document.getElementById('chatMicBtn');
    const fileInput = document.getElementById('chatFileInput');
    const emojiPicker = document.querySelector('emoji-picker');
    const recordingIndicator = document.getElementById('recordingIndicator');

    // Search Elements
    const searchInput = document.getElementById('widgetSearchInput');
    const searchResults = document.getElementById('widgetSearchResults');

    let activeTab = 'chat';
    let isRecording = false;
    let mediaRecorder;
    let audioChunks = [];

    // Toggle Chat Window
    chatBtn.addEventListener('click', () => {
        chatWindow.classList.toggle('active');
        if (chatWindow.classList.contains('active')) {
            if (activeTab === 'chat') chatInput.focus();
            else searchInput.focus();
        }
    });

    closeBtn.addEventListener('click', () => {
        chatWindow.classList.remove('active');
    });

    // Tab Switching
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-tab');
            activeTab = target;

            // Update Tabs
            toggleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Update Bodies
            chatBodies.forEach(b => {
                b.style.display = 'none';
                if (b.id === `chatBody${capitalize(target)}`) {
                    b.style.display = 'flex';
                }
            });

            // Toggle Footer (Input for Chat and Support)
            if (target === 'chat') {
                chatFooter.style.display = 'block';
                chatInput.focus();
            } else if (target === 'support') {
                if (typeof userLoggedIn !== 'undefined' && userLoggedIn) {
                    chatFooter.style.display = 'block';
                    chatInput.focus();
                } else {
                    chatFooter.style.display = 'none';
                }
            } else {
                chatFooter.style.display = 'none';
                if (target === 'search') searchInput.focus();
            }
        });
    });

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // --- CHAT LOGIC ---

    // Send Message
    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    function sendMessage() {
        const message = chatInput.value.trim();
        const file = fileInput.files[0];

        if (!message && !file) return;

        // Add User Message
        let displayMsg = message;
        if (file) {
            displayMsg += `<br><small>📎 ${file.name}</small>`;
        }
        addMessage(displayMsg, 'user');

        chatInput.value = '';
        fileInput.value = ''; // Clear file input

        if (activeTab === 'chat') {
            // AI Chat Logic (Text only for now)
            showTypingIndicator();
            fetch('/inkmybook/modules/chat/ai_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `message=${encodeURIComponent(message)}`
            })
                .then(response => response.json())
                .then(data => {
                    removeTypingIndicator();
                    addMessage(data.reply, 'ai');
                })
                .catch(err => {
                    removeTypingIndicator();
                    // If AI fails, suggest support
                    addMessage("I'm having trouble connecting. Switching you to human support...", 'ai');
                    setTimeout(() => {
                        document.querySelector('[data-tab="support"]').click();
                    }, 1500);
                });

            // Auto-switch for support keywords
            const lowerMsg = message.toLowerCase();
            if (lowerMsg.includes('support') || lowerMsg.includes('human') || lowerMsg.includes('agent') || lowerMsg.includes('help')) {
                setTimeout(() => {
                    addMessage("I can connect you to a human agent. Switching tabs...", 'ai');
                    setTimeout(() => {
                        document.querySelector('[data-tab="support"]').click();
                    }, 1000);
                }, 500);
                return; // Stop AI fetch
            }
        } else if (activeTab === 'support') {
            // Support Chat Logic with File Upload
            const formData = new FormData();
            formData.append('message', message);
            if (file) {
                formData.append('attachment', file);
            }

            fetch('/inkmybook/modules/support/send_message.php', {
                method: 'POST',
                body: formData // No Content-Type header needed for FormData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') {
                        addMessage("Error: " + (data.message || "Unknown error"), 'system');
                    }
                })
                .catch(() => {
                    addMessage("Network error. Please try again.", 'system');
                });
        }
    }

    function addMessage(text, type) {
        // Determine target body based on active tab or type
        let targetBodyId = 'chatBodyChat';
        if (activeTab === 'support' || type === 'support') {
            targetBodyId = 'chatBodySupport';
        }

        const body = document.getElementById(targetBodyId);
        if (!body) return; // Safety check

        const msgDiv = document.createElement('div');
        msgDiv.className = `chat-message ${type} animate-fade-in`;
        msgDiv.innerHTML = `
            ${text}
            <span class="chat-timestamp">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
        `;
        body.appendChild(msgDiv);
        scrollToBottom(targetBodyId);
    }

    function scrollToBottom(elementId = 'chatBodyChat') {
        const body = document.getElementById(elementId);
        if (body) body.scrollTop = body.scrollHeight;
    }

    function showTypingIndicator() {
        const body = document.getElementById('chatBodyChat');
        const typingDiv = document.createElement('div');
        typingDiv.id = 'typingIndicator';
        typingDiv.className = 'chat-message ai';
        typingDiv.innerHTML = '<i class="fas fa-ellipsis-h fa-beat"></i>';
        body.appendChild(typingDiv);
        scrollToBottom();
    }

    function removeTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) indicator.remove();
    }

    // Support Chat Polling
    setInterval(() => {
        if (activeTab === 'support') {
            fetch('/inkmybook/modules/support/get_messages.php')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const body = document.getElementById('chatBodySupport');
                        // Simple logic: Clear and rebuild (inefficient but works for MVP)
                        // Ideally, check for new IDs. For now, we'll just append if empty or rely on user action
                        // A better approach for MVP without complex state:
                        // Just fetch on load? No, real-time needed.
                        // Let's just log for now or implement a smarter append if I had the last ID.
                        // For this step, I'll assume the user sees their own messages instantly.
                        // I will implement a proper "load history" function later.
                    }
                });
        }
    }, 5000);

    // Load Support History on Tab Switch
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.getAttribute('data-tab') === 'support') {
                loadSupportHistory();
            }
        });
    });

    function loadSupportHistory() {
        if (typeof userLoggedIn !== 'undefined' && !userLoggedIn) {
            return; // Do not load history if not logged in (keep static login prompt)
        }
        const body = document.getElementById('chatBodySupport');
        body.innerHTML = '<div class="text-center text-muted small mt-3">Loading history...</div>';

        fetch('/inkmybook/modules/support/get_messages.php')
            .then(res => res.json())
            .then(data => {
                body.innerHTML = ''; // Clear loader
                if (data.status === 'success' && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        const type = msg.sender_type === 'user' ? 'user' : 'support'; // 'support' style needs to be defined or use 'ai' style
                        const msgDiv = document.createElement('div');
                        // Map 'support' sender to 'received' or 'ai' class for styling
                        const styleClass = type === 'user' ? 'user' : 'ai';

                        msgDiv.className = `chat-message ${styleClass}`;
                        msgDiv.innerHTML = `
                            ${msg.message}
                            <span class="chat-timestamp">${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                        `;
                        body.appendChild(msgDiv);
                    });
                    scrollToBottom('chatBodySupport');
                } else {
                    body.innerHTML = '<div class="text-center text-muted small mt-5">No messages yet. Start chatting with support!</div>';
                }
            });
    }

    // Emoji Picker
    emojiBtn.addEventListener('click', () => {
        emojiPicker.classList.toggle('active');
    });

    emojiPicker.addEventListener('emoji-click', event => {
        chatInput.value += event.detail.unicode;
        emojiPicker.classList.remove('active');
        chatInput.focus();
    });

    // File Upload
    fileBtn.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            chatInput.placeholder = `File selected: ${file.name}`;
            chatInput.focus();
        }
    });

    // Voice Recording
    micBtn.addEventListener('click', toggleRecording);

    async function toggleRecording() {
        if (!isRecording) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.start();

                isRecording = true;
                micBtn.classList.add('text-danger');
                recordingIndicator.classList.add('active');

                mediaRecorder.ondataavailable = (e) => {
                    audioChunks.push(e.data);
                };

                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    addMessage(`<audio controls src="${audioUrl}"></audio>`, 'user');
                    audioChunks = [];
                };
            } catch (err) {
                console.error('Error accessing microphone:', err);
                alert('Could not access microphone.');
            }
        } else {
            mediaRecorder.stop();
            isRecording = false;
            micBtn.classList.remove('text-danger');
            recordingIndicator.classList.remove('active');
        }
    }

    // --- SEARCH LOGIC ---

    // Expose function globally for onclick events
    window.performWidgetSearch = function (query) {
        searchInput.value = query;
        executeSearch(query);
    };

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            executeSearch(searchInput.value);
        }
    });

    function executeSearch(query) {
        if (!query.trim()) return;

        searchResults.innerHTML = '<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';

        fetch(`/inkmybook/modules/chat/widget_search.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.results.length > 0) {
                    let html = '';
                    data.results.forEach(item => {
                        html += `
                            <a href="/inkmybook/modules/services/view.php?id=${item.id}" class="card mb-2 border-0 shadow-sm text-decoration-none">
                                <div class="card-body p-2 d-flex align-items-center">
                                    <img src="/inkmybook/${item.image}" class="rounded me-2" width="40" height="40" style="object-fit:cover;">
                                    <div>
                                        <h6 class="mb-0 text-dark small fw-bold">${item.title}</h6>
                                        <small class="text-muted">From $${item.price}</small>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    searchResults.innerHTML = html;
                } else {
                    searchResults.innerHTML = '<div class="text-center text-muted small mt-3">No results found.</div>';
                }
            })
            .catch(() => {
                searchResults.innerHTML = '<div class="text-center text-danger small mt-3">Error searching.</div>';
            });
    }
});
