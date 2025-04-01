<!-- resources/views/boards/create.php -->
<?php
$title = 'Create Board';
ob_start();
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1 class="h4 mb-0">Create New Board</h1>
                </div>

                <div class="card-body">
                    <form action="/boards" method="post">
                        <div class="mb-3">
                            <label for="title" class="form-label">Board Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="background-color" class="form-label">Background Color</label>
                            <input type="color" class="form-control form-control-color" id="background-color" name="background_color" value="#F5F5F5">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is-public" name="is_public">
                                <label class="form-check-label" for="is-public">
                                    Make board public (anyone with the link can view)
                                </label>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="/boards" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Board</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>