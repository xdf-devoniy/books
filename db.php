<?php
declare(strict_types=1);

session_start();

const APP_ACCOUNTS = [
    'dapa' => 'DAPA',
    'kids' => 'KIDS',
];

function current_account(): string
{
    $account = strtolower($_GET['account'] ?? ($_SESSION['account'] ?? 'dapa'));
    if (!array_key_exists($account, APP_ACCOUNTS)) {
        $account = 'dapa';
    }
    $_SESSION['account'] = $account;
    return $account;
}

function get_account_label(string $account): string
{
    return APP_ACCOUNTS[$account] ?? strtoupper($account);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_NAME') ?: 'books';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    bootstrap_schema($pdo);

    return $pdo;
}

function bootstrap_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account VARCHAR(20) NOT NULL,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) DEFAULT NULL,
            category VARCHAR(255) DEFAULT NULL,
            quantity INT NOT NULL DEFAULT 0,
            buying_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            selling_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_book_account (account, title)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account VARCHAR(20) NOT NULL,
            book_id INT DEFAULT NULL,
            book_title VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            unit_buy_price DECIMAL(10,2) NOT NULL,
            unit_sell_price DECIMAL(10,2) NOT NULL,
            sold_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function fetch_books(string $account): array
{
    $stmt = db()->prepare('SELECT * FROM books WHERE account = ? ORDER BY title');
    $stmt->execute([$account]);
    return $stmt->fetchAll();
}

function find_book(int $id, string $account): ?array
{
    $stmt = db()->prepare('SELECT * FROM books WHERE id = ? AND account = ?');
    $stmt->execute([$id, $account]);
    $book = $stmt->fetch();
    return $book ?: null;
}

function create_book(string $account, array $data): int
{
    $stmt = db()->prepare('INSERT INTO books (account, title, author, category, quantity, buying_price, selling_price) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $account,
        $data['title'],
        $data['author'] ?? null,
        $data['category'] ?? null,
        (int) $data['quantity'],
        (float) $data['buying_price'],
        (float) $data['selling_price'],
    ]);
    return (int) db()->lastInsertId();
}

function update_book(int $id, string $account, array $data): void
{
    $stmt = db()->prepare('UPDATE books SET title = ?, author = ?, category = ?, quantity = ?, buying_price = ?, selling_price = ? WHERE id = ? AND account = ?');
    $stmt->execute([
        $data['title'],
        $data['author'] ?? null,
        $data['category'] ?? null,
        (int) $data['quantity'],
        (float) $data['buying_price'],
        (float) $data['selling_price'],
        $id,
        $account,
    ]);
}

function delete_book(int $id, string $account): void
{
    $stmt = db()->prepare('DELETE FROM books WHERE id = ? AND account = ?');
    $stmt->execute([$id, $account]);
}

function record_sale(string $account, int $bookId, int $quantity): array
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $book = find_book($bookId, $account);
        if (!$book) {
            throw new RuntimeException('Kitob topilmadi.');
        }
        if ($quantity < 1) {
            throw new RuntimeException('Minimal miqdor 1 dona.');
        }
        if ($quantity > (int) $book['quantity']) {
            throw new RuntimeException('Omborda yetarli dona mavjud emas.');
        }

        $update = $pdo->prepare('UPDATE books SET quantity = quantity - ? WHERE id = ? AND account = ?');
        $update->execute([$quantity, $bookId, $account]);

        $insert = $pdo->prepare('INSERT INTO sales (account, book_id, book_title, quantity, unit_buy_price, unit_sell_price) VALUES (?, ?, ?, ?, ?, ?)');
        $insert->execute([
            $account,
            $bookId,
            $book['title'],
            $quantity,
            (float) $book['buying_price'],
            (float) $book['selling_price'],
        ]);

        $saleId = (int) $pdo->lastInsertId();
        $pdo->commit();

        return [
            'id' => $saleId,
            'book_title' => $book['title'],
            'quantity' => $quantity,
            'unit_buy_price' => (float) $book['buying_price'],
            'unit_sell_price' => (float) $book['selling_price'],
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function fetch_dashboard_metrics(string $account): array
{
    $pdo = db();
    $inventory = $pdo->prepare('SELECT COUNT(*) AS total_books, COALESCE(SUM(quantity), 0) AS total_quantity, COALESCE(SUM(quantity * buying_price), 0) AS total_cost_value, COALESCE(SUM(quantity * selling_price), 0) AS total_sale_value FROM books WHERE account = ?');
    $inventory->execute([$account]);
    $inventoryRow = $inventory->fetch() ?: [
        'total_books' => 0,
        'total_quantity' => 0,
        'total_cost_value' => 0,
        'total_sale_value' => 0,
    ];

    $profit = $pdo->prepare('SELECT COALESCE(SUM((unit_sell_price - unit_buy_price) * quantity), 0) AS total_profit, COALESCE(SUM(unit_sell_price * quantity), 0) AS total_revenue FROM sales WHERE account = ?');
    $profit->execute([$account]);
    $profitRow = $profit->fetch() ?: [
        'total_profit' => 0,
        'total_revenue' => 0,
    ];

    return [
        'total_books' => (int) $inventoryRow['total_books'],
        'total_quantity' => (int) $inventoryRow['total_quantity'],
        'stock_cost' => (float) $inventoryRow['total_cost_value'],
        'stock_value' => (float) $inventoryRow['total_sale_value'],
        'total_profit' => (float) $profitRow['total_profit'],
        'total_revenue' => (float) $profitRow['total_revenue'],
    ];
}

function fetch_recent_sales(string $account, int $limit = 5): array
{
    $stmt = db()->prepare('SELECT book_title, quantity, unit_buy_price, unit_sell_price, sold_at FROM sales WHERE account = ? ORDER BY sold_at DESC LIMIT ?');
    $stmt->bindValue(1, $account, PDO::PARAM_STR);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function fetch_profit_by_day(string $account, int $days = 7): array
{
    $stmt = db()->prepare('SELECT DATE(sold_at) AS day, COALESCE(SUM((unit_sell_price - unit_buy_price) * quantity), 0) AS profit, COALESCE(SUM(unit_sell_price * quantity), 0) AS revenue FROM sales WHERE account = ? AND sold_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY day ORDER BY day DESC');
    $stmt->bindValue(1, $account, PDO::PARAM_STR);
    $stmt->bindValue(2, $days, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
