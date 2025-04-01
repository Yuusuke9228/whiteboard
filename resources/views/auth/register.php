<!-- resources/views/auth/register.php -->
<?php
$title = 'Register';
ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h1 class="h4 mb-0">Register</h1>
                </div>

                <div class="card-body">
                    <?php if (isset($_SESSION['errors']) && isset($_SESSION['errors']['general'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['errors']['general'][0] ?>
                        </div>
                    <?php endif; ?>

                    <form action="/register" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control <?= isset($_SESSION['errors']['username']) ? 'is-invalid' : '' ?>" id="username" name="username" required>
                            <?php if (isset($_SESSION['errors']['username'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['username'][0] ?>
                                </div>
                            <?php endif; ?>
                        </div>

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
                            <div class="form-text">Password must be at least 8 characters long</div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control <?= isset($_SESSION['errors']['password_confirmation']) ? 'is-invalid' : '' ?>" id="password_confirmation" name="password_confirmation" required>
                            <?php if (isset($_SESSION['errors']['password_confirmation'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['password_confirmation'][0] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                </div>

                <div class="card-footer text-center">
                    <p class="mb-0">Already have an account? <a href="/login">Login</a></p>
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