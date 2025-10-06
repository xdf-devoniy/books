<?php

declare(strict_types=1);

/**
 * Returns a shared PDO connection to the local SQLite database.
 */
function get_db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $storageDirectory = __DIR__ . '/storage';
    if (!is_dir($storageDirectory)) {
        mkdir($storageDirectory, 0775, true);
    }

    $databasePath = $storageDirectory . '/library.sqlite';
    $initializeSchema = !file_exists($databasePath);

    $connection = new PDO('sqlite:' . $databasePath);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $connection->exec('PRAGMA foreign_keys = ON');

    if ($initializeSchema) {
        initialize_schema($connection);
    }

    return $connection;
}

/**
 * Creates the tables needed by the application.
 */
function initialize_schema(PDO $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            author TEXT DEFAULT NULL,
            category TEXT DEFAULT NULL,
            price REAL NOT NULL DEFAULT 0,
            quantity INTEGER NOT NULL DEFAULT 0,
            description TEXT DEFAULT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $db->exec('CREATE INDEX IF NOT EXISTS idx_books_title ON books(title)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_books_category ON books(category)');
}

/**
 * Fetches all categories in the catalogue.
 *
 * @return string[]
 */
function fetch_categories(PDO $db): array
{
    $statement = $db->query(
        'SELECT DISTINCT category
         FROM books
         WHERE category IS NOT NULL AND TRIM(category) <> ""
         ORDER BY category COLLATE NOCASE'
    );

    return array_map(static fn(array $row): string => (string) $row['category'], $statement->fetchAll());
}

/**
 * Returns a single book by id or null if it does not exist.
 */
function fetch_book(PDO $db, int $bookId): ?array
{
    $statement = $db->prepare(
        'SELECT *, (price * quantity) AS inventory_value
         FROM books
         WHERE id = :id'
    );
    $statement->bindValue(':id', $bookId, PDO::PARAM_INT);
    $statement->execute();

    $book = $statement->fetch();

    return $book !== false ? $book : null;
}

/**
 * Fetches books applying optional filters and sorting preferences.
 *
 * @return array<int, array<string, mixed>>
 */
function fetch_books(PDO $db, string $search = '', string $category = '', string $sort = 'title'): array
{
    $conditions = [];
    $parameters = [];

    if ($search !== '') {
        $conditions[] = '(title LIKE :search OR author LIKE :search OR category LIKE :search)';
        $parameters[':search'] = '%' . $search . '%';
    }

    if ($category !== '') {
        $conditions[] = 'category = :category';
        $parameters[':category'] = $category;
    }

    $sql = 'SELECT *, (price * quantity) AS inventory_value FROM books';

    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sort = strtolower($sort);
    switch ($sort) {
        case 'latest':
            $sql .= ' ORDER BY datetime(updated_at) DESC, title COLLATE NOCASE ASC';
            break;
        case 'quantity':
            $sql .= ' ORDER BY quantity DESC, title COLLATE NOCASE ASC';
            break;
        case 'value':
            $sql .= ' ORDER BY inventory_value DESC, title COLLATE NOCASE ASC';
            break;
        default:
            $sql .= ' ORDER BY title COLLATE NOCASE ASC';
            break;
    }

    $statement = $db->prepare($sql);

    foreach ($parameters as $key => $value) {
        $statement->bindValue($key, $value, PDO::PARAM_STR);
    }

    $statement->execute();

    return $statement->fetchAll();
}

/**
 * Returns general inventory metrics.
 *
 * @return array<string, mixed>
 */
function fetch_inventory_metrics(PDO $db): array
{
    $metrics = $db->query(
        'SELECT
            COUNT(*) AS title_count,
            COALESCE(SUM(quantity), 0) AS total_quantity,
            COALESCE(SUM(price * quantity), 0) AS total_value,
            COALESCE(AVG(price), 0) AS average_price
         FROM books'
    )->fetch();

    $lastUpdated = $db->query('SELECT MAX(updated_at) AS last_updated FROM books')->fetchColumn();

    return [
        'title_count'   => (int) ($metrics['title_count'] ?? 0),
        'total_quantity' => (int) ($metrics['total_quantity'] ?? 0),
        'total_value'   => (float) ($metrics['total_value'] ?? 0.0),
        'average_price' => (float) ($metrics['average_price'] ?? 0.0),
        'last_updated'  => $lastUpdated ? (string) $lastUpdated : null,
    ];
}

/**
 * Computes category level analytics.
 *
 * @return array<int, array<string, mixed>>
 */
function fetch_category_breakdown(PDO $db): array
{
    $statement = $db->query(
        'SELECT
            COALESCE(NULLIF(TRIM(category), ""), "Uncategorized") AS category,
            COUNT(*) AS title_count,
            COALESCE(SUM(quantity), 0) AS total_quantity,
            COALESCE(SUM(price * quantity), 0) AS total_value
         FROM books
         GROUP BY COALESCE(NULLIF(TRIM(category), ""), "Uncategorized")
         ORDER BY total_value DESC, category COLLATE NOCASE ASC'
    );

    return $statement->fetchAll();
}

/**
 * Returns the most recently updated books.
 *
 * @return array<int, array<string, mixed>>
 */
function fetch_recent_books(PDO $db, int $limit = 5): array
{
    $statement = $db->prepare(
        'SELECT *, (price * quantity) AS inventory_value
         FROM books
         ORDER BY datetime(updated_at) DESC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

/**
 * Formats a float as currency with thousands separators.
 */
function format_currency(float $amount): string
{
    return number_format($amount, 2, '.', ' ');
}

/**
 * Formats an integer quantity with thousands separators.
 */
function format_quantity(int $quantity): string
{
    return number_format($quantity, 0, '.', ' ');
}
