<!-- resources/views/objects/show.php -->
<?php
$title = 'View Object';
ob_start();

// プロパティを取得して変数に格納
$properties = json_decode($object['properties'], true);
$type = $object['type'];
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h1 class="h4 mb-0">Object Details</h1>
                    <div>
                        <a href="/boards/<?= $board['id'] ?>/objects" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="fas fa-list"></i> All Objects
                        </a>
                        <a href="/boards/<?= $board['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Board
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="mb-4">
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
                        <span class="text-muted ms-2">ID: <?= $object['id'] ?></span>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h2 class="h5">General Information</h2>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th>Created By</th>
                                        <td>User ID: <?= $object['user_id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Created At</th>
                                        <td><?= date('Y-m-d H:i:s', strtotime($object['created_at'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Updated At</th>
                                        <td><?= date('Y-m-d H:i:s', strtotime($object['updated_at'])) ?></td>
                                    </tr>
                                    <?php if ($type === 'text' || $type === 'note'): ?>
                                        <tr>
                                            <th>Content</th>
                                            <td>
                                                <pre class="mb-0"><?= htmlspecialchars($object['content']) ?></pre>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h2 class="h5">Properties</h2>
                            <table class="table">
                                <tbody>
                                    <?php if ($type === 'text' || $type === 'note' || $type === 'shape'): ?>
                                        <tr>
                                            <th>Position</th>
                                            <td>X: <?= $properties['x'] ?>, Y: <?= $properties['y'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Size</th>
                                            <td>Width: <?= $properties['width'] ?>, Height: <?= $properties['height'] ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($type === 'line'): ?>
                                        <tr>
                                            <th>Start Point</th>
                                            <td>X: <?= $properties['x1'] ?>, Y: <?= $properties['y1'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>End Point</th>
                                            <td>X: <?= $properties['x2'] ?>, Y: <?= $properties['y2'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Line Type</th>
                                            <td><?= ucfirst($properties['lineType'] ?? 'straight') ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($type === 'shape'): ?>
                                        <tr>
                                            <th>Shape Type</th>
                                            <td><?= ucfirst($properties['shapeType'] ?? 'rectangle') ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($type === 'text' || $type === 'note'): ?>
                                        <tr>
                                            <th>Text Color</th>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color: <?= $properties['textColor'] ?>; border: 1px solid #ddd; margin-right: 10px;"></div>
                                                    <?= $properties['textColor'] ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Font Size</th>
                                            <td><?= $properties['fontSize'] ?>px</td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($type === 'text' || $type === 'note' || $type === 'shape'): ?>
                                        <tr>
                                            <th>Fill Color</th>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color: <?= $properties['fillColor'] ?? '#ffffff' ?>; border: 1px solid #ddd; margin-right: 10px;"></div>
                                                    <?= $properties['fillColor'] ?? '#ffffff' ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($type === 'shape' || $type === 'line'): ?>
                                        <tr>
                                            <th>Stroke Color</th>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color: <?= $properties['strokeColor'] ?>; border: 1px solid #ddd; margin-right: 10px;"></div>
                                                    <?= $properties['strokeColor'] ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Line Width</th>
                                            <td><?= $properties['lineWidth'] ?? 1 ?>px</td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($type === 'connector' && isset($properties['startObjectId']) && isset($properties['endObjectId'])): ?>
                                        <tr>
                                            <th>Start Object</th>
                                            <td>Object ID: <?= $properties['startObjectId'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>End Object</th>
                                            <td>Object ID: <?= $properties['endObjectId'] ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="/boards/<?= $board['id'] ?>/objects/<?= $object['id'] ?>/edit" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Object
                        </a>
                        <form action="/boards/<?= $board['id'] ?>/objects/<?= $object['id'] ?>" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this object?');">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Object
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Preview</h2>
                </div>
                <div class="card-body">
                    <?php if ($type === 'text'): ?>
                        <div class="border p-3" style="background-color: <?= $properties['fillColor'] ?? '#ffffff' ?>;">
                            <div style="color: <?= $properties['textColor'] ?>; font-size: <?= $properties['fontSize'] ?>px;">
                                <?= nl2br(htmlspecialchars($object['content'])) ?>
                            </div>
                        </div>
                    <?php elseif ($type === 'note'): ?>
                        <div class="border p-3" style="background-color: <?= $properties['fillColor'] ?? '#ffffbb' ?>;">
                            <div style="color: <?= $properties['textColor'] ?>; font-size: <?= $properties['fontSize'] ?>px;">
                                <?= nl2br(htmlspecialchars($object['content'])) ?>
                            </div>
                        </div>
                    <?php elseif ($type === 'shape'): ?>
                        <div class="border p-3 text-center">
                            <svg width="200" height="150" viewBox="0 0 200 150">
                                <?php if ($properties['shapeType'] === 'rectangle'): ?>
                                    <rect x="50" y="25" width="100" height="100"
                                        fill="<?= $properties['fillColor'] ?? '#ffffff' ?>"
                                        stroke="<?= $properties['strokeColor'] ?>"
                                        stroke-width="<?= $properties['lineWidth'] ?? 1 ?>" />
                                <?php elseif ($properties['shapeType'] === 'circle'): ?>
                                    <circle cx="100" cy="75" r="50"
                                        fill="<?= $properties['fillColor'] ?? '#ffffff' ?>"
                                        stroke="<?= $properties['strokeColor'] ?>"
                                        stroke-width="<?= $properties['lineWidth'] ?? 1 ?>" />
                                <?php elseif ($properties['shapeType'] === 'triangle'): ?>
                                    <polygon points="100,25 150,125 50,125"
                                        fill="<?= $properties['fillColor'] ?? '#ffffff' ?>"
                                        stroke="<?= $properties['strokeColor'] ?>"
                                        stroke-width="<?= $properties['lineWidth'] ?? 1 ?>" />
                                <?php endif; ?>
                            </svg>
                        </div>
                    <?php elseif ($type === 'line'): ?>
                        <div class="border p-3 text-center">
                            <svg width="200" height="150" viewBox="0 0 200 150">
                                <?php if ($properties['lineType'] === 'straight'): ?>
                                    <line x1="50" y1="25" x2="150" y2="125"
                                        stroke="<?= $properties['strokeColor'] ?>"
                                        stroke-width="<?= $properties['lineWidth'] ?? 1 ?>" />
                                <?php elseif ($properties['lineType'] === 'arrow'): ?>
                                    <!-- Arrow line with arrowhead -->
                                    <defs>
                                        <marker id="arrowhead" markerWidth="10" markerHeight="7"
                                            refX="9" refY="3.5" orient="auto">
                                            <polygon points="0 0, 10 3.5, 0 7" fill="<?= $properties['strokeColor'] ?>" />
                                        </marker>
                                    </defs>
                                    <line x1="50" y1="25" x2="150" y2="125"
                                        stroke="<?= $properties['strokeColor'] ?>"
                                        stroke-width="<?= $properties['lineWidth'] ?? 1 ?>"
                                        marker-end="url(#arrowhead)" />
                                <?php elseif ($properties['lineType'] === 'curve'): ?>
                                    <!-- Curved line -->
                                    <path d="M50,25 Q100,0 150,125"
                                        fill="none"
                                        stroke="<?= $properties['strokeColor'] ?>"
                                        stroke-width="<?= $properties['lineWidth'] ?? 1 ?>" />
                                <?php endif; ?>
                            </svg>
                        </div>
                    <?php elseif ($type === 'connector'): ?>
                        <div class="border p-3 text-center">
                            <p class="text-muted">Connector between objects</p>
                            <small>From Object ID: <?= $properties['startObjectId'] ?? 'N/A' ?></small>
                            <br>
                            <small>To Object ID: <?= $properties['endObjectId'] ?? 'N/A' ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Board Information</h2>
                </div>
                <div class="card-body">
                    <p><strong>Board:</strong> <?= htmlspecialchars($board['title']) ?></p>
                    <p><strong>Board ID:</strong> <?= $board['id'] ?></p>
                    <p><strong>Background Color:</strong>
                        <span class="d-inline-block" style="width: 20px; height: 20px; background-color: <?= $board['background_color'] ?>; border: 1px solid #ddd; vertical-align: middle;"></span>
                        <?= $board['background_color'] ?>
                    </p>
                    <p><strong>Visibility:</strong> <?= $board['is_public'] ? 'Public' : 'Private' ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>