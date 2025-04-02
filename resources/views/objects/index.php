<!-- resources/views/objects/index.php -->
<?php
$title = 'Board Objects';
ob_start();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= htmlspecialchars($board['title']) ?> - Objects</h1>
        <div>
            <a href="/boards/<?= $board['id'] ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back to Board
            </a>
            <a href="/boards/<?= $board['id'] ?>/objects/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Object
            </a>
        </div>
    </div>

    <?php if (empty($objects)): ?>
        <div class="alert alert-info">
            This board doesn't have any objects yet. Click the "Add Object" button to create one.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h2 class="h5 mb-0">Objects List</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Content</th>
                                <th>Created by</th>
                                <th>Created at</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($objects as $object): ?>
                                <?php
                                $properties = json_decode($object['properties'], true);
                                $contentPreview = '';
                                if ($object['type'] === 'text' || $object['type'] === 'note') {
                                    $contentPreview = mb_substr($object['content'], 0, 50);
                                    if (mb_strlen($object['content']) > 50) {
                                        $contentPreview .= '...';
                                    }
                                } else if ($object['type'] === 'shape') {
                                    $contentPreview = $properties['shapeType'] ?? 'N/A';
                                } else if ($object['type'] === 'line') {
                                    $contentPreview = $properties['lineType'] ?? 'N/A';
                                } else if ($object['type'] === 'connector') {
                                    $contentPreview = 'Connector';
                                }
                                ?>
                                <tr>
                                    <td><?= $object['id'] ?></td>
                                    <td>
                                        <span class="badge rounded-pill 
                                            <?php
                                            switch ($object['type']) {
                                                case 'text':
                                                    echo 'bg-primary';
                                                    break;
                                                case 'note':
                                                    echo 'bg-warning text-dark';
                                                    break;
                                                case 'shape':
                                                    echo 'bg-success';
                                                    break;
                                                case 'line':
                                                    echo 'bg-secondary';
                                                    break;
                                                case 'connector':
                                                    echo 'bg-info text-dark';
                                                    break;
                                                default:
                                                    echo 'bg-light text-dark';
                                            }
                                            ?>">
                                            <?= ucfirst($object['type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($contentPreview) ?></td>
                                    <td>User ID: <?= $object['user_id'] ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($object['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="/boards/<?= $board['id'] ?>/objects/<?= $object['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/boards/<?= $board['id'] ?>/objects/<?= $object['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="/boards/<?= $board['id'] ?>/objects/<?= $object['id'] ?>" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this object?');">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>