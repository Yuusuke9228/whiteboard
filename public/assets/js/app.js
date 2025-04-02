// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function () {
    // Enable Bootstrap tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl =>
        new bootstrap.Tooltip(tooltipTriggerEl)
    );

    // Enable Bootstrap popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl =>
        new bootstrap.Popover(popoverTriggerEl)
    );

    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Handle share board modal functionality
    const shareModal = document.getElementById('shareModal');
    if (shareModal) {
        shareModal.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const boardId = button.getAttribute('data-board-id');

            document.getElementById('share-board-id').value = boardId;

            try {
                // Load current sharing settings
                const response = await fetch(`/api/boards/${boardId}`);
                const data = await response.json();

                if (data.success) {
                    // Set public toggle
                    document.getElementById('is-public').checked = data.board.is_public;

                    // Show shared users
                    const sharedUsersList = document.getElementById('shared-users-list');

                    if (data.shared_users && data.shared_users.length > 0) {
                        renderSharedUsers(sharedUsersList, data.shared_users, boardId);
                    } else {
                        sharedUsersList.innerHTML = '<p class="text-muted">No users shared with this board</p>';
                    }
                }
            } catch (error) {
                console.error('Error loading board details:', error);
            }
        });

        // Handle share form submission
        document.getElementById('share-submit').addEventListener('click', async function () {
            const boardId = document.getElementById('share-board-id').value;
            const email = document.getElementById('share-email').value;
            const permission = document.getElementById('share-permission').value;
            const isPublic = document.getElementById('is-public').checked;

            if (email) {
                try {
                    // Update board public status
                    await fetch(`/api/boards/${boardId}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ is_public: isPublic })
                    });

                    // Share with user
                    const response = await fetch(`/api/boards/${boardId}/share`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email, permission })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Reload shared users list
                        const boardResponse = await fetch(`/api/boards/${boardId}`);
                        const boardData = await boardResponse.json();

                        if (boardData.success && boardData.shared_users) {
                            const sharedUsersList = document.getElementById('shared-users-list');
                            renderSharedUsers(sharedUsersList, boardData.shared_users, boardId);
                        }

                        // Clear input
                        document.getElementById('share-email').value = '';

                        // Show success message
                        showAlert('success', 'Board shared successfully!');
                    } else {
                        showAlert('danger', data.error || 'Failed to share board');
                    }
                } catch (error) {
                    console.error('Error sharing board:', error);
                    showAlert('danger', 'An error occurred while sharing the board');
                }
            } else {
                // Just update public status
                try {
                    await fetch(`/api/boards/${boardId}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ is_public: isPublic })
                    });

                    showAlert('success', 'Board visibility updated!');
                } catch (error) {
                    console.error('Error updating board visibility:', error);
                    showAlert('danger', 'Failed to update board visibility');
                }
            }
        });
    }

    // Handle rename board modal
    const renameModal = document.getElementById('renameModal');
    if (renameModal) {
        renameModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const boardId = button.getAttribute('data-board-id');
            const boardTitle = button.getAttribute('data-board-title');

            document.getElementById('rename-board-id').value = boardId;
            document.getElementById('board-title').value = boardTitle;
        });

        // Handle rename form submission
        document.getElementById('rename-submit').addEventListener('click', async function () {
            const boardId = document.getElementById('rename-board-id').value;
            const title = document.getElementById('board-title').value;

            if (!title) {
                showAlert('danger', 'Title is required');
                return;
            }

            try {
                const response = await fetch(`/api/boards/${boardId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ title })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('success', 'Board renamed successfully');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showAlert('danger', data.error || 'Failed to rename board');
                }
            } catch (error) {
                console.error('Error renaming board:', error);
                showAlert('danger', 'An error occurred while renaming the board');
            }
        });
    }

    // Handle delete board modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const boardId = button.getAttribute('data-board-id');

            document.getElementById('delete-board-id').value = boardId;
        });

        // Handle delete confirmation
        document.getElementById('delete-submit').addEventListener('click', async function () {
            const boardId = document.getElementById('delete-board-id').value;

            try {
                const response = await fetch(`/api/boards/${boardId}`, {
                    method: 'DELETE'
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('success', 'Board deleted successfully');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showAlert('danger', data.error || 'Failed to delete board');
                }
            } catch (error) {
                console.error('Error deleting board:', error);
                showAlert('danger', 'An error occurred while deleting the board');
            }
        });
    }

    // Profile form validation
    const profileForm = document.getElementById('profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function (event) {
            const password = document.getElementById('new-password');
            const confirm = document.getElementById('confirm-password');

            if (password && confirm && password.value !== confirm.value) {
                event.preventDefault();
                showAlert('danger', 'Passwords do not match');
            }
        });
    }
});

// Helper Functions

// Render shared users list
function renderSharedUsers(container, users, boardId) {
    const list = document.createElement('ul');
    list.className = 'list-group';

    users.forEach(user => {
        const item = document.createElement('li');
        item.className = 'list-group-item d-flex justify-content-between align-items-center';

        const userInfo = document.createElement('div');
        userInfo.innerHTML = `
            <strong>${escapeHtml(user.username)}</strong>
            <small>(${escapeHtml(user.email)})</small>
        `;

        const controls = document.createElement('div');

        // Permission select
        const permissionSelect = document.createElement('select');
        permissionSelect.className = 'form-select form-select-sm d-inline-block me-2';
        permissionSelect.style.width = '100px';
        permissionSelect.innerHTML = `
            <option value="view" ${user.permission === 'view' ? 'selected' : ''}>View</option>
            <option value="edit" ${user.permission === 'edit' ? 'selected' : ''}>Edit</option>
            <option value="admin" ${user.permission === 'admin' ? 'selected' : ''}>Admin</option>
        `;

        // Update permission on change
        permissionSelect.addEventListener('change', async () => {
            try {
                const response = await fetch(`/api/boards/${boardId}/share`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: user.email,
                        permission: permissionSelect.value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('success', 'Permission updated');
                } else {
                    showAlert('danger', data.error || 'Failed to update permission');
                    // Reset to previous value
                    permissionSelect.value = user.permission;
                }
            } catch (error) {
                console.error('Error updating permission:', error);
                showAlert('danger', 'An error occurred while updating permission');
                permissionSelect.value = user.permission;
            }
        });

        controls.appendChild(permissionSelect);

        // Remove user button
        const removeButton = document.createElement('button');
        removeButton.className = 'btn btn-sm btn-danger';
        removeButton.innerHTML = '<i class="fas fa-times"></i>';

        removeButton.addEventListener('click', async () => {
            if (confirm(`Are you sure you want to remove ${user.username}?`)) {
                try {
                    const response = await fetch(`/api/boards/${boardId}/share/${user.id}`, {
                        method: 'DELETE'
                    });

                    const data = await response.json();

                    if (data.success) {
                        item.remove();
                        showAlert('success', 'User removed from board');
                    } else {
                        showAlert('danger', data.error || 'Failed to remove user');
                    }
                } catch (error) {
                    console.error('Error removing user:', error);
                    showAlert('danger', 'An error occurred while removing user');
                }
            }
        });

        controls.appendChild(removeButton);
        item.appendChild(userInfo);
        item.appendChild(controls);
        list.appendChild(item);
    });

    container.innerHTML = '';
    container.appendChild(list);
}

// Show bootstrap alert
function showAlert(type, message) {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        // Create alert container if it doesn't exist
        const container = document.createElement('div');
        container.id = 'alert-container';
        container.className = 'position-fixed top-0 start-50 translate-middle-x mt-3 z-index-1050';
        document.body.appendChild(container);
    }

    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    document.getElementById('alert-container').appendChild(alert);

    // Auto dismiss after 5 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 5000);
}

// Escape HTML to prevent XSS
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Confirm dangerous actions
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}