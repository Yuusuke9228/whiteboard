<!-- resources/views/auth/login.php -->
<?php
$title = 'Login';
ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h1 class="h4 mb-0">Login</h1>
                </div>

                <div class="card-body">
                    <?php if (isset($_SESSION['errors']) && isset($_SESSION['errors']['auth'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['errors']['auth'][0] ?>
                        </div>
                    <?php endif; ?>

                    <form action="/login" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?= isset($_SESSION['errors']['email']) ? 'is-invalid' : '' ?>" id="email" name="email" required>
                            <?php if (isset($_SESSION['errors']['email'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['email'][0] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control <?= isset($_SESSION['errors']['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
                            <?php if (isset($_SESSION['errors']['password'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['password'][0] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>

                <div class="card-footer text-center">
                    <p class="mb-0">Don't have an account? <a href="/register">Register</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
unset($_SESSION['errors']);
?>