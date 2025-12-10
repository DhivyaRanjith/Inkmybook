function startConversation(userId, type, id) {
    const formData = new FormData();
    formData.append('action', 'start_conversation');
    formData.append('other_user_id', userId);
    formData.append('entity_type', type);
    formData.append('entity_id', id);

    fetch('/inkmybook/modules/messaging/api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = '/inkmybook/modules/messaging/inbox.php?conversation_id=' + data.conversation_id;
        } else {
            // If user is not logged in, redirect to login
            if (data.message === 'Unauthorized') {
                window.location.href = '/inkmybook/modules/auth/login.php';
            } else {
                alert('Error starting conversation: ' + data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
