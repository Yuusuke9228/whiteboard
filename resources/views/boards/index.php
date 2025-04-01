<!-- resources/views/boards/index.php -->
<?php
$title = 'My Boards';
ob_start();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Boards</h1>
        <a href="/boards/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Board
        </a>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#owned-boards">My Boards</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#shared-boards">Shared With Me</a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="owned-boards">
            <?php if (empty($ownedBoards)): ?>
                <div class="text-center p-5 bg-light rounded">
                    <h4>You don't have any boards yet</h4>
                    <p>Create your first board to start collaborating</p>
                    <a href="/boards/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Board
                    </a>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($ownedBoards as $board): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($board['title']) ?></h5>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            Created: <?= date('Y-m-d', strtotime($board['created_at'])) ?>
                                        </small>
                                    </p>
                                    <div class="d-flex justify-content-between">
                                        <a href="/boards/<?= $board['id'] ?>" class="btn btn-primary">Open</a>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#shareModal" data-board-id="<?= $board['id'] ?>">
                                                        <i class="fas fa-share-alt"></i> Share
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#renameModal" data-board-id="<?= $board['id'] ?>" data-board-title="<?= htmlspecialchars($board['title']) ?>">
                                                        <i class="fas fa-edit"></i> Rename
                                                    </a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteModal" data-board-id="<?= $board['id'] ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <small class="text-muted">
                                        <?= $board['is_public'] ? 'Public' : 'Private' ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="shared-boards">
            <?php if (empty($sharedBoards)): ?>
                <div class="text-center p-5 bg-light rounded">
                    <h4>No boards shared with you</h4>
                    <p>Boards shared with you will appear here</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($sharedBoards as $board): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($board['title']) ?></h5>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            Created: <?= date('Y-m-d', strtotime($board['created_at'])) ?><br>
                                            Owner: <?= htmlspecialchars($board['owner_username']) ?>
                                        </small>
                                    </p>
                                    <a href="/boards/<?= $board['id'] ?>" class="btn btn-primary">Open</a>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <small class="text-muted">
                                        Permission: <?= ucfirst($board['permission']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Share Board</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="shareForm">
                    <input type="hidden" id="share-board-id" name="board_id">

                    <div class="mb-3">
                        <label for="share-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="share-email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="share-permission" class="form-label">Permission</label>
                        <select class="form-select" id="share-permission" name="permission">
                            <option value="view">View</option>
                            <option value="edit">Edit</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is-public" name="is_public">
                            <label class="form-check-label" for="is-public">
                                Make board public (anyone with the link can view)
                            </label>
                        </div>
                    </div>
                </form>

                <div class="mt-4">
                    <h6>Shared With</h6>
                    <div id="shared-users-list">
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="ms-2">Loading shared users...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="share-submit">Share</button>
            </div>
        </div>
    </div>
</div>

<!-- Rename Modal -->
<div class="modal fade" id="renameModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rename Board</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="renameForm">
                    <input type="hidden" id="rename-board-id" name="board_id">

                    <div class="mb-3">
                        <label for="board-title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="board-title" name="title" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="rename-submit">Rename</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Board</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this board? This action cannot be undone.</p>
                <input type="hidden" id="delete-board-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="delete-submit">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // 共有モーダル
        const shareModal = document.getElementById('shareModal');
        shareModal.addEventListener('show.bs.modal', async (event) => {
            const button = event.relatedTarget;
            const boardId = button.getAttribute('data-board-id');
            document.getElementById('share-board-id').value = boardId;

            // ボードの詳細を取得
            try {
                const response = await fetch(`/api/boards/${boardId}`);
                const data = await response.json();

                if (data.success) {
                    // 公開状態の設定
                    document.getElementById('is-public').checked = data.board.is_public;

                    // 共有ユーザーリストの表示
                    const sharedUsersList = document.getElementById('shared-users-list');

                    if (data.shared_users && data.shared_users.length > 0) {
                        const usersList = document.createElement('ul');
                        usersList.className = 'list-group';

                        data.shared_users.forEach(user => {
                            const item = document.createElement('li');
                            item.className = 'list-group-item d-flex justify-content-between align-items-center';

                            const userInfo = document.createElement('div');
                            userInfo.innerHTML = `
                                <strong>${user.username}</strong>
                                <small>(${user.email})</small>
                            `;
                            item.appendChild(userInfo);

                            const controls = document.createElement('div');

                            const permissionSelect = document.createElement('select');
                            permissionSelect.className = 'form-select form-select-sm d-inline-block me-2';
                            permissionSelect.style.width = '100px';
                            permissionSelect.innerHTML = `
                                <option value="view" ${user.permission === 'view' ? 'selected' : ''}>View</option>
                                <option value="edit" ${user.permission === 'edit' ? 'selected' : ''}>Edit</option>
                                <option value="admin" ${user.permission === 'admin' ? 'selected' : ''}>Admin</option>
                            `;

                            permissionSelect.addEventListener('change', async () => {
                                try {
                                    const response = await fetch(`/api/boards/${boardId}/share`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            email: user.email,
                                            permission: permissionSelect.value
                                        })
                                    });

                                    const data = await response.json();

                                    if (!data.success) {
                                        alert(data.error);
                                    }
                                } catch (error) {
                                    console.error('Error updating permission:', error);
                                    alert('Failed to update permission');
                                }
                            });

                            controls.appendChild(permissionSelect);

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
                                        } else {
                                            alert(data.error);
                                        }
                                    } catch (error) {
                                        console.error('Error removing user:', error);
                                        alert('Failed to remove user');
                                    }
                                }
                            });

                            controls.appendChild(removeButton);
                            item.appendChild(controls);

                            usersList.appendChild(item);
                        });

                        sharedUsersList.innerHTML = '';
                        sharedUsersList.appendChild(usersList);
                    } else {
                        sharedUsersList.innerHTML = '<p class="text-muted">No users shared with this board</p>';
                    }
                } else {
                    alert('Failed to load board details');
                }
            } catch (error) {
                console.error('Error fetching board details:', error);
                alert('Failed to load board details');
            }
        });

        // 共有フォームの送信
        document.getElementById('share-submit').addEventListener('click', async () => {
            const boardId = document.getElementById('share-board-id').value;
            const email = document.getElementById('share-email').value;
            const permission = document.getElementById('share-permission').value;
            const isPublic = document.getElementById('is-public').checked;

            if (!email) {
                alert('Email is required');
                return;
            }

            try {
                // 公開状態の更新
                await fetch(`/api/boards/${boardId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        is_public: isPublic
                    })
                });

                // ユーザーとの共有
                if (email) {
                    const response = await fetch(`/api/boards/${boardId}/share`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            email,
                            permission
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Board shared successfully');
                        bootstrap.Modal.getInstance(shareModal).hide();
                        document.getElementById('share-email').value = '';
                    } else {
                        alert(data.error);
                    }
                }
            } catch (error) {
                console.error('Error sharing board:', error);
                alert('Failed to share board');
            }
        });

        // リネームモーダル
        const renameModal = document.getElementById('renameModal');
        renameModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const boardId = button.getAttribute('data-board-id');
            const boardTitle = button.getAttribute('data-board-title');

            document.getElementById('rename-board-id').value = boardId;
            document.getElementById('board-title').value = boardTitle;
        });

        // リネームフォームの送信
        document.getElementById('rename-submit').addEventListener('click', async () => {
            const boardId = document.getElementById('rename-board-id').value;
            const title = document.getElementById('board-title').value;

            if (!title) {
                alert('Title is required');
                return;
            }

            try {
                const response = await fetch(`/api/boards/${boardId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        title
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Board renamed successfully');
                    bootstrap.Modal.getInstance(renameModal).hide();
                    window.location.reload();
                } else {
                    alert(data.error);
                }
            } catch (error) {
                console.error('Error renaming board:', error);
                alert('Failed to rename board');
            }
        });

        // 削除モーダル
        const deleteModal = document.getElementById('deleteModal');
        deleteModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const boardId = button.getAttribute('data-board-id');

            document.getElementById('delete-board-id').value = boardId;
        });

        // 削除の確認
        document.getElementById('delete-submit').addEventListener('click', async () => {
            const boardId = document.getElementById('delete-board-id').value;

            try {
                const response = await fetch(`/api/boards/${boardId}`, {
                    method: 'DELETE'
                });

                const data = await response.json();

                if (data.success) {
                    alert('Board deleted successfully');
                    bootstrap.Modal.getInstance(deleteModal).hide();
                    window.location.reload();
                } else {
                    alert(data.error);
                }
            } catch (error) {
                console.error('Error deleting board:', error);
                alert('Failed to delete board');
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>