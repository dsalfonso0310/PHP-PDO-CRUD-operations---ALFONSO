<?php
// db.php - Database connection
$host = 'localhost';
$dbname = 'shoot';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

session_start();

//CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        } else {
            $login_error = "Invalid email or password";
        }
    }
    elseif (isset($_POST['signup'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $password]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $signup_error = "Email already exists";
        }
    }
    elseif (isset($_POST['add_book'])) {
        $title = $_POST['title'];
        $author = $_POST['author'];
        $year = $_POST['year'];
        
        $stmt = $pdo->prepare("INSERT INTO books (title, author, year, user_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $author, $year, $_SESSION['user_id']]);
        header('Location: '.$_SERVER['PHP_SELF']);
        exit;
    }
    elseif (isset($_POST['delete_book'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        header('Location: '.$_SERVER['PHP_SELF']);
        exit;
    }
    elseif (isset($_POST['update_book'])) {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $author = $_POST['author'];
        $year = $_POST['year'];
        
        $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, year = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $author, $year, $id, $_SESSION['user_id']]);
        header('Location: '.$_SERVER['PHP_SELF']);
        exit;
    }
    elseif (isset($_POST['logout'])) {
        session_destroy();
        header('Location: '.$_SERVER['PHP_SELF']);
        exit;
    }
}

// Get books if logged in
$books = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2980b9;
            --danger: #e74c3c;
            --success: #2ecc71;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #95a5a6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header Styles */
        header {
            background: var(--primary);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        h1 {
            font-weight: 600;
            font-size: 24px;
        }
        
        /* Button Styles */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            opacity: 0.9;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        /* Form Styles */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin: 30px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        /* Table Styles */
        .book-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-collapse: collapse;
            margin: 30px 0;
        }
        
        .book-table th, 
        .book-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .book-table th {
            background: var(--primary);
            color: white;
            font-weight: 500;
        }
        
        .book-table tr:hover {
            background: #f8f9fa;
        }
        
        /* Auth Pages */
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .auth-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 800px;
            overflow: hidden;
        }
        
        .auth-tabs {
            display: flex;
        }
        
        .auth-tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        
        .auth-tab.active {
            background: white;
            font-weight: 500;
        }
        
        .auth-content {
            padding: 40px;
        }
        
        .error-message {
            color: var(--danger);
            margin-bottom: 20px;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .auth-tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Login/Signup Page -->
        <div class="auth-container">
            <div class="auth-box">
                <div class="auth-tabs">
                    <div class="auth-tab active">Login</div>
                    <div class="auth-tab">Sign Up</div>
                </div>
                
                <div class="auth-content">
                    <!-- Login Form -->
                    <div id="login-form" style="display: block;">
                        <h2>Login to Your Account</h2>
                        <?php if (isset($login_error)): ?>
                            <div class="error-message"><?php echo $login_error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary">Login</button>
                        </form>
                    </div>
                    
                    <!-- Signup Form -->
                    <div id="signup-form" style="display: none;">
                        <h2>Create New Account</h2>
                        <?php if (isset($signup_error)): ?>
                            <div class="error-message"><?php echo $signup_error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <button type="submit" name="signup" class="btn btn-primary">Sign Up</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            //Switching for auth forms
            document.querySelectorAll('.auth-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    if (tab.textContent === 'Login') {
                        document.getElementById('login-form').style.display = 'block';
                        document.getElementById('signup-form').style.display = 'none';
                    } else {
                        document.getElementById('login-form').style.display = 'none';
                        document.getElementById('signup-form').style.display = 'block';
                    }
                });
            });
        </script>
    
    <?php else: ?>
        <!-- Book Management Page -->
        <header>
            <div class="header-content">
                <h1>Library Management System</h1>
                <form method="post">
                    <button type="submit" name="logout" class="btn btn-danger">Logout</button>
                </form>
            </div>
        </header>
        
        <div class="container">
            <!-- Book List -->
            <div class="form-container">
                <h2>Your Book Collection</h2>
                <?php if (empty($books)): ?>
                    <p>No books found. Add some books to get started!</p>
                <?php else: ?>
                    <table class="book-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Year</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['year']); ?></td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
                                            <input type="hidden" name="title" value="<?php echo htmlspecialchars($book['title']); ?>">
                                            <input type="hidden" name="author" value="<?php echo htmlspecialchars($book['author']); ?>">
                                            <input type="hidden" name="year" value="<?php echo htmlspecialchars($book['year']); ?>">
                                            <button type="button" onclick="editBook(this.form)" class="btn btn-primary">Edit</button>
                                        </form>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" name="delete_book" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Add/Edit Book Form -->
            <div class="form-container">
                <h2 id="form-title">Add New Book</h2>
                <form method="post" id="book-form">
                    <input type="hidden" name="id" id="book-id">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" required>
                    </div>
                    <div class="form-group">
                        <label for="year">Publication Year</label>
                        <input type="number" id="year" name="year" required>
                    </div>
                    <button type="submit" name="add_book" id="submit-btn" class="btn btn-success">Add Book</button>
                </form>
            </div>
        </div>
        
        <script>
            function editBook(form) {
                document.getElementById('book-id').value = form.elements['id'].value;
                document.getElementById('title').value = form.elements['title'].value;
                document.getElementById('author').value = form.elements['author'].value;
                document.getElementById('year').value = form.elements['year'].value;
                
                document.getElementById('form-title').textContent = 'Edit Book';
                document.getElementById('submit-btn').textContent = 'Update Book';
                document.getElementById('submit-btn').name = 'update_book';
                
                // Scroll to form
                document.querySelector('#book-form').scrollIntoView({ behavior: 'smooth' });
            }
        </script>
    <?php endif; ?>
</body>
</html>
