<!-- Chat Widget Button -->
<div id="chatWidgetBtn" class="chat-widget-btn animate-bounce">
    <i class="fas fa-comment-dots"></i>
</div>

<!-- Chat Widget Window -->
<div id="chatWidgetWindow" class="chat-widget-window">
    <!-- Header -->
    <div class="chat-header">
        <button id="chatCloseBtn" class="chat-close-btn"><i class="fas fa-times"></i></button>

        <!-- Toggle Tabs -->
        <div class="chat-toggle">
            <button class="chat-toggle-btn active" data-tab="chat"><i class="fas fa-comment-alt"></i> Chat</button>
            <button class="chat-toggle-btn" data-tab="search"><i class="fas fa-search"></i> Search</button>
            <button class="chat-toggle-btn" data-tab="support"><i class="fas fa-headset"></i> Support</button>
        </div>

        <!-- Avatars -->
        <div class="chat-avatars">
            <div class="chat-avatar" style="background-image: url('https://randomuser.me/api/portraits/men/32.jpg');">
            </div>
            <div class="chat-avatar" style="background-image: url('https://randomuser.me/api/portraits/women/44.jpg');">
            </div>
            <div class="chat-avatar" style="background-image: url('https://randomuser.me/api/portraits/men/85.jpg');">
            </div>
        </div>

        <div class="chat-header-title">Questions? Chat with us!</div>
        <div class="chat-header-status">
            <div class="status-dot"></div> Support is online
        </div>
    </div>

    <!-- Body: Chat -->
    <div id="chatBodyChat" class="chat-body">
        <!-- Welcome Block -->
        <div class="welcome-block animate-fade-in">
            <div class="welcome-title">
                Hi there <i class="fas fa-smile text-warning"></i> How can we help you today?
            </div>
            <p class="small mb-2 opacity-90">You can ask things like:</p>
            <ul class="welcome-list">
                <li>Looking for a logo designer</li>
                <li>How to hire a freelancer</li>
                <li>Pricing for content writing</li>
                <li>Something else!</li>
            </ul>
            <div class="welcome-footer">
                If we are currently unavailable, leave your email and we'll get back to you.
            </div>
        </div>

        <!-- Messages will be loaded here via JS -->
    </div>

    <!-- Body: Support -->
    <div id="chatBodySupport" class="chat-body" style="display: none;">
        <div class="text-center text-muted small mt-5">
            <i class="fas fa-headset fa-3x mb-3 opacity-50"></i>
            <p>Connect directly with our support team.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="alert alert-warning small mx-3">
                    Please <a href="/inkmybook/modules/auth/login.php" class="fw-bold text-dark">login</a> to chat with
                    support.
                </div>
            <?php else: ?>
                <p>Type your message below to start a ticket.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Body: Search -->
    <div id="chatBodySearch" class="chat-body" style="display: none;">
        <div class="search-container">
            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="widgetSearchInput" class="search-input" placeholder="What are you looking for?">
            </div>

            <div class="frequent-searches">
                <h6>Frequent searches</h6>
                <div class="search-item" onclick="performWidgetSearch('logo design')">
                    <i class="far fa-star"></i> Logo Design
                </div>
                <div class="search-item" onclick="performWidgetSearch('wordpress')">
                    <i class="far fa-star"></i> WordPress Development
                </div>
                <div class="search-item" onclick="performWidgetSearch('seo')">
                    <i class="far fa-star"></i> SEO Services
                </div>
                <div class="search-item" onclick="performWidgetSearch('content writing')">
                    <i class="far fa-star"></i> Content Writing
                </div>
            </div>

            <div id="widgetSearchResults" class="mt-3"></div>
        </div>
    </div>

    <!-- Footer / Input (Only visible in Chat tab) -->
    <div id="chatFooter" class="chat-footer">
        <div id="recordingIndicator" class="recording-indicator">
            <div class="recording-dot"></div> Recording...
        </div>
        <div class="chat-input-wrapper">
            <textarea id="chatInput" class="chat-input-field" placeholder="Compose your message..." rows="1"></textarea>

            <div class="chat-actions">
                <button id="chatEmojiBtn" class="chat-action-icon"><i class="far fa-smile"></i></button>
                <button id="chatFileBtn" class="chat-action-icon"><i class="fas fa-paperclip"></i></button>
                <input type="file" id="chatFileInput" hidden>
                <button id="chatMicBtn" class="chat-action-icon"><i class="fas fa-microphone-alt"></i></button>
            </div>

            <button id="chatSendBtn" class="chat-send-icon"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <!-- Emoji Picker Element -->
    <emoji-picker></emoji-picker>
</div>

<!-- Load Emoji Picker Script -->
<script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
<script>
    const userLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
</script>