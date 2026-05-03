<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/UserController.php';

$userController = new UserController();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$message = isset($_GET['message']) ? $_GET['message'] : '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'create') {
        try {
            if (empty($_POST['firstName']) || empty($_POST['lastName']) || empty($_POST['email']) || empty($_POST['phone']) || empty($_POST['password'])) {
                $message = "error: All fields are required";
            } else {
                $user = new User(
                    null,
                    $_POST['firstName'],
                    $_POST['lastName'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['password'],
                    isset($_POST['active']) ? 1 : 0
                );

                $userController->save($user);
                header('Location: users.php?action=list&message=success: User created successfully');
                exit;
            }
        } catch (Exception $e) {
            $message = "error: " . $e->getMessage();
        }
    } elseif ($action === 'update' && $id) {
        try {
            $user = new User(
                $id,
                $_POST['firstName'],
                $_POST['lastName'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['password'],
                isset($_POST['active']) ? 1 : 0
            );

            $userController->update($id, $user);
            header('Location: users.php?action=list&message=success: User updated successfully');
            exit;
        } catch (Exception $e) {
            $message = "error: " . $e->getMessage();
        }
    } elseif ($action === 'delete' && $id) {
        try {
            $userController->delete($id);
            header('Location: users.php?action=list&message=success: User deleted successfully');
            exit;
        } catch (Exception $e) {
            $message = "error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Matching</title>

</head>

<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>User Management</h1>
                <p>Manage users in your matching platform</p>
            </div>
            <div class="header-actions">
                <a href="users.php?action=list" class="btn btn-secondary">List</a>
                <a href="users.php?action=create" class="btn btn-success">+ New User</a>
            </div>
        </div>

        <?php if ($message): ?>
            <?php
            $messageType = strpos($message, 'error:') === 0 ? 'error' : 'success';
            $messageText = str_replace(['error: ', 'success: '], '', $message);
            ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($messageText); ?>
            </div>
        <?php endif; ?>

        <div class="content">
            <?php if ($action === 'list'): ?>
                <!-- List View -->
                <?php
                try {
                    $users = $userController->getAll();
                    $totalUsers = $userController->count();
                    $activeUsers = count($userController->getAllActive());
                ?>
                    <div class="stats">
                        <div class="stat-card">
                            <h3><?php echo $totalUsers; ?></h3>
                            <p>Total Users</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo $activeUsers; ?></h3>
                            <p>Active Users</p>
                        </div>
                    </div>

                    <?php if (count($users) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($user['id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['firstName']); ?></td>
                                            <td><?php echo htmlspecialchars($user['lastName']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td>
                                                <span style="padding: 5px 10px; border-radius: 3px; font-size: 12px; <?php echo $user['active'] ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #7f1d1d;'; ?>">
                                                    <?php echo $user['active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-primary">Edit</a>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <p>No users found</p>
                            <a href="users.php?action=create" class="btn btn-success">Create First User</a>
                        </div>
                    <?php endif; ?>
                <?php
                } catch (Exception $e) {
                    echo '<div class="message error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>

            <?php elseif ($action === 'create'): ?>
                <!-- Create Form -->
                <div class="form-container">
                    <h2>Create New User</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="create">

                        <div class="form-group">
                            <label for="firstName">First Name *</label>
                            <input type="text" id="firstName" name="firstName" required>
                        </div>

                        <div class="form-group">
                            <label for="lastName">Last Name *</label>
                            <input type="text" id="lastName" name="lastName" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone *</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="active" name="active" checked>
                                <label for="active" style="margin-bottom: 0;">Set as Active</label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="users.php?action=list" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Create User</button>
                        </div>
                    </form>
                </div>

            <?php elseif ($action === 'edit' && $id): ?>
                <!-- Edit Form -->
                <?php
                try {
                    $user = $userController->getById($id);
                    if (!$user) {
                        echo '<div class="message error">User not found</div>';
                    } else {
                ?>
                        <div class="form-container">
                            <h2>Edit User</h2>
                            <form method="POST">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">

                                <div class="form-group">
                                    <label for="firstName">First Name *</label>
                                    <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user->getFirstName()); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="lastName">Last Name *</label>
                                    <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user->getLastName()); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user->getEmail()); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="phone">Phone *</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user->getPhone()); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="password">Password *</label>
                                    <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
                                </div>

                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="active" name="active" <?php echo $user->getActive() ? 'checked' : ''; ?>>
                                        <label for="active" style="margin-bottom: 0;">Set as Active</label>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <a href="users.php?action=list" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-success">Update User</button>
                                </div>
                            </form>
                        </div>
                <?php
                    }
                } catch (Exception $e) {
                    echo '<div class="message error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            <?php else: ?>
                <div class="no-data">
                    <p>Invalid action</p>
                    <a href="users.php?action=list" class="btn btn-secondary">Back to List</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>