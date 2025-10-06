<?php

declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const DB_SERVERNAME = 'localhost';
const DB_USERNAME   = 'harvards_admin';
const DB_PASSWORD   = 'Rasul9898aa';
const DB_NAME       = 'harvards_dpb';

/**
 * Returns a shared MySQLi connection using the legacy credentials.
 */
function get_db(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $connection = new mysqli(DB_SERVERNAME, DB_USERNAME, DB_PASSWORD, DB_NAME);
    $connection->set_charset('utf8mb4');

    return $connection;
}

/**
 * Caches the list of columns for a given table.
 *
 * @return string[]
 */
function table_columns(mysqli $db, string $table): array
{
    static $cache = [];

    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        throw new InvalidArgumentException('Invalid table name supplied.');
    }

    if (!array_key_exists($table, $cache)) {
        $result = $db->query('SHOW COLUMNS FROM `' . $table . '`');
        $columns = [];

        while ($row = $result->fetch_assoc()) {
            $columns[] = (string) $row['Field'];
        }

        $cache[$table] = $columns;
    }

    return $cache[$table];
}

function table_has_column(mysqli $db, string $table, string $column): bool
{
    try {
        return in_array($column, table_columns($db, $table), true);
    } catch (Throwable $exception) {
        return false;
    }
}

/**
 * Helper used to bind parameters to a statement with dynamic counts.
 *
 * @param array<int, mixed> $params
 */
function bind_params(mysqli_stmt $statement, string $types, array &$params): void
{
    if ($types === '') {
        return;
    }

    $references = [];
    foreach ($params as $key => &$value) {
        $references[$key] = &$value;
    }

    array_unshift($references, $types);
    $statement->bind_param(...$references);
}

/**
 * Builds the inventory aggregation subquery, preserving last stock activity if available.
 */
function build_inventory_subquery(mysqli $db): string
{
    $hasInventory = true;
    try {
        $columns = table_columns($db, 'inventory');
    } catch (Throwable $exception) {
        $hasInventory = false;
        $columns = [];
    }

    if (!$hasInventory) {
        return 'SELECT NULL AS book_id, 0 AS quantity, NULL AS last_stock_date LIMIT 0';
    }

    $dateExpression = "NULL";
    $hasAddedDate = in_array('added_date', $columns, true);
    $hasUpdatedAt = in_array('updated_at', $columns, true);

    if ($hasUpdatedAt && $hasAddedDate) {
        $dateExpression = 'MAX(COALESCE(updated_at, added_date))';
    } elseif ($hasUpdatedAt) {
        $dateExpression = 'MAX(updated_at)';
    } elseif ($hasAddedDate) {
        $dateExpression = 'MAX(added_date)';
    }

    return 'SELECT book_id, SUM(quantity) AS quantity, ' . $dateExpression . ' AS last_stock_date FROM inventory GROUP BY book_id';
}

/**
 * Builds the sales aggregation subquery to fetch the latest sale date per title.
 */
function build_sales_activity_subquery(mysqli $db): string
{
    try {
        $columns = table_columns($db, 'sales');
    } catch (Throwable $exception) {
        return 'SELECT NULL AS book_id, NULL AS last_sale_date LIMIT 0';
    }

    if (!in_array('sale_date', $columns, true)) {
        return 'SELECT NULL AS book_id, NULL AS last_sale_date LIMIT 0';
    }

    return 'SELECT book_id, MAX(sale_date) AS last_sale_date FROM sales GROUP BY book_id';
}

/**
 * Fetches all categories if a category column exists.
 *
 * @return string[]
 */
function fetch_categories(mysqli $db): array
{
    if (!table_has_column($db, 'books', 'category')) {
        return [];
    }

    $result = $db->query(
        "SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND TRIM(category) <> '' ORDER BY category"
    );

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = (string) $row['category'];
    }

    return $categories;
}

/**
 * Fetches book information with inventory and latest activity details.
 *
 * @return array<int, array<string, mixed>>
 */
function fetch_books(mysqli $db, string $search = '', string $category = '', string $sort = 'title'): array
{
    $columns = table_columns($db, 'books');
    $hasAuthor = in_array('author', $columns, true);
    $hasCategory = in_array('category', $columns, true);
    $hasDescription = in_array('description', $columns, true);

    $select = [
        'b.id',
        'b.name AS title',
        'b.price',
        'COALESCE(inv.quantity, 0) AS quantity',
        'COALESCE(inv.quantity, 0) * b.price AS inventory_value',
        "GREATEST(COALESCE(inv.last_stock_date, '1970-01-01'), COALESCE(sales.last_sale_date, '1970-01-01')) AS last_activity"
    ];

    $select[] = $hasAuthor ? 'b.author' : "NULL AS author";
    $select[] = $hasCategory ? 'b.category' : "NULL AS category";
    $select[] = $hasDescription ? 'b.description' : "NULL AS description";

    $inventorySubquery = build_inventory_subquery($db);
    $salesSubquery = build_sales_activity_subquery($db);

    $sql = 'SELECT ' . implode(', ', $select)
        . ' FROM books b'
        . ' LEFT JOIN (' . $inventorySubquery . ') AS inv ON inv.book_id = b.id'
        . ' LEFT JOIN (' . $salesSubquery . ') AS sales ON sales.book_id = b.id';

    $conditions = [];
    $types = '';
    $params = [];

    if ($search !== '') {
        $searchCondition = ['b.name LIKE ?'];
        $like = '%' . $search . '%';
        $params[] = $like;
        $types .= 's';

        if ($hasAuthor) {
            $searchCondition[] = 'b.author LIKE ?';
            $params[] = $like;
            $types .= 's';
        }

        if ($hasCategory) {
            $searchCondition[] = 'b.category LIKE ?';
            $params[] = $like;
            $types .= 's';
        }

        $conditions[] = '(' . implode(' OR ', $searchCondition) . ')';
    }

    if ($category !== '' && $hasCategory) {
        $conditions[] = 'b.category = ?';
        $params[] = $category;
        $types .= 's';
    }

    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    switch (strtolower($sort)) {
        case 'latest':
            $sql .= ' ORDER BY last_activity DESC, title ASC';
            break;
        case 'quantity':
            $sql .= ' ORDER BY quantity DESC, title ASC';
            break;
        case 'value':
            $sql .= ' ORDER BY inventory_value DESC, title ASC';
            break;
        default:
            $sql .= ' ORDER BY title ASC';
            break;
    }

    $statement = $db->prepare($sql);
    bind_params($statement, $types, $params);
    $statement->execute();

    $result = $statement->get_result();
    $books = [];

    while ($row = $result->fetch_assoc()) {
        $row['price'] = (float) $row['price'];
        $row['quantity'] = (int) $row['quantity'];
        $row['inventory_value'] = (float) $row['inventory_value'];
        $row['last_activity'] = $row['last_activity'] !== '1970-01-01' ? $row['last_activity'] : null;
        $books[] = $row;
    }

    return $books;
}

/**
 * Returns a single book with inventory totals or null if not found.
 */
function fetch_book(mysqli $db, int $bookId): ?array
{
    $columns = table_columns($db, 'books');
    $hasAuthor = in_array('author', $columns, true);
    $hasCategory = in_array('category', $columns, true);
    $hasDescription = in_array('description', $columns, true);

    $inventorySubquery = build_inventory_subquery($db);
    $salesSubquery = build_sales_activity_subquery($db);

    $select = [
        'b.id',
        'b.name AS title',
        'b.price',
        'COALESCE(inv.quantity, 0) AS quantity',
        'COALESCE(inv.quantity, 0) * b.price AS inventory_value',
        "GREATEST(COALESCE(inv.last_stock_date, '1970-01-01'), COALESCE(sales.last_sale_date, '1970-01-01')) AS last_activity"
    ];

    $select[] = $hasAuthor ? 'b.author' : "NULL AS author";
    $select[] = $hasCategory ? 'b.category' : "NULL AS category";
    $select[] = $hasDescription ? 'b.description' : "NULL AS description";

    $sql = 'SELECT ' . implode(', ', $select)
        . ' FROM books b'
        . ' LEFT JOIN (' . $inventorySubquery . ') AS inv ON inv.book_id = b.id'
        . ' LEFT JOIN (' . $salesSubquery . ') AS sales ON sales.book_id = b.id'
        . ' WHERE b.id = ?'
        . ' LIMIT 1';

    $statement = $db->prepare($sql);
    $statement->bind_param('i', $bookId);
    $statement->execute();

    $result = $statement->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        return null;
    }

    $row['price'] = (float) $row['price'];
    $row['quantity'] = (int) $row['quantity'];
    $row['inventory_value'] = (float) $row['inventory_value'];
    $row['last_activity'] = $row['last_activity'] !== '1970-01-01' ? $row['last_activity'] : null;

    return $row;
}

/**
 * Returns overall inventory metrics.
 *
 * @return array<string, mixed>
 */
function fetch_inventory_metrics(mysqli $db): array
{
    $inventorySubquery = build_inventory_subquery($db);
    $salesSubquery = build_sales_activity_subquery($db);

    $sql = 'SELECT'
        . ' COUNT(DISTINCT b.id) AS title_count,'
        . ' COALESCE(SUM(inv.quantity), 0) AS total_quantity,'
        . ' COALESCE(SUM(inv.quantity * b.price), 0) AS total_value,'
        . ' COALESCE(AVG(b.price), 0) AS average_price,'
        . " GREATEST(COALESCE(MAX(inv.last_stock_date), '1970-01-01'), COALESCE(MAX(sales.last_sale_date), '1970-01-01')) AS last_activity"
        . ' FROM books b'
        . ' LEFT JOIN (' . $inventorySubquery . ') AS inv ON inv.book_id = b.id'
        . ' LEFT JOIN (' . $salesSubquery . ') AS sales ON sales.book_id = b.id';

    $result = $db->query($sql)->fetch_assoc();

    return [
        'title_count' => (int) ($result['title_count'] ?? 0),
        'total_quantity' => (int) ($result['total_quantity'] ?? 0),
        'total_value' => (float) ($result['total_value'] ?? 0.0),
        'average_price' => (float) ($result['average_price'] ?? 0.0),
        'last_updated' => (!empty($result['last_activity']) && $result['last_activity'] !== '1970-01-01') ? (string) $result['last_activity'] : null,
    ];
}

/**
 * Computes category level analytics when categories are available.
 *
 * @return array<int, array<string, mixed>>
 */
function fetch_category_breakdown(mysqli $db): array
{
    if (!table_has_column($db, 'books', 'category')) {
        return [];
    }

    $inventorySubquery = build_inventory_subquery($db);

    $sql = "SELECT"
        . " COALESCE(NULLIF(TRIM(b.category), ''), 'Uncategorized') AS category_label,"
        . ' COUNT(DISTINCT b.id) AS title_count,'
        . ' COALESCE(SUM(inv.quantity), 0) AS total_quantity,'
        . ' COALESCE(SUM(inv.quantity * b.price), 0) AS total_value'
        . ' FROM books b'
        . ' LEFT JOIN (' . $inventorySubquery . ') AS inv ON inv.book_id = b.id'
        . ' GROUP BY category_label'
        . ' ORDER BY total_value DESC, category_label ASC';

    $result = $db->query($sql);
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'category' => (string) $row['category_label'],
            'title_count' => (int) $row['title_count'],
            'total_quantity' => (int) $row['total_quantity'],
            'total_value' => (float) $row['total_value'],
        ];
    }

    return $rows;
}

/**
 * Returns the most recently updated or sold books.
 *
 * @return array<int, array<string, mixed>>
 */
function fetch_recent_books(mysqli $db, int $limit = 5): array
{
    $columns = table_columns($db, 'books');
    $hasAuthor = in_array('author', $columns, true);
    $hasCategory = in_array('category', $columns, true);
    $hasDescription = in_array('description', $columns, true);

    $inventorySubquery = build_inventory_subquery($db);
    $salesSubquery = build_sales_activity_subquery($db);

    $select = [
        'b.id',
        'b.name AS title',
        'b.price',
        'COALESCE(inv.quantity, 0) AS quantity',
        'COALESCE(inv.quantity, 0) * b.price AS inventory_value',
        "GREATEST(COALESCE(inv.last_stock_date, '1970-01-01'), COALESCE(sales.last_sale_date, '1970-01-01')) AS last_activity"
    ];

    $select[] = $hasAuthor ? 'b.author' : "NULL AS author";
    $select[] = $hasCategory ? 'b.category' : "NULL AS category";
    $select[] = $hasDescription ? 'b.description' : "NULL AS description";

    $sql = 'SELECT ' . implode(', ', $select)
        . ' FROM books b'
        . ' LEFT JOIN (' . $inventorySubquery . ') AS inv ON inv.book_id = b.id'
        . ' LEFT JOIN (' . $salesSubquery . ') AS sales ON sales.book_id = b.id'
        . ' ORDER BY last_activity DESC, title ASC'
        . ' LIMIT ?';

    $statement = $db->prepare($sql);
    $limitParam = $limit;
    $statement->bind_param('i', $limitParam);
    $statement->execute();

    $result = $statement->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $row['price'] = (float) $row['price'];
        $row['quantity'] = (int) $row['quantity'];
        $row['inventory_value'] = (float) $row['inventory_value'];
        $row['last_activity'] = $row['last_activity'] !== '1970-01-01' ? $row['last_activity'] : null;
        $rows[] = $row;
    }

    return $rows;
}

/**
 * Returns sales made on a given date.
 *
 * @return array<int, array<string, mixed>>
 */
function fetch_sales_for_date(mysqli $db, string $date): array
{
    if (!table_has_column($db, 'sales', 'sale_date')) {
        return [];
    }

    $columns = table_columns($db, 'sales');
    $hasSaleType = in_array('sale_type', $columns, true);

    $sql = 'SELECT b.id, b.name AS title, s.quantity, s.sale_date,'
        . ' b.price, (s.quantity * b.price) AS total_value';

    if ($hasSaleType) {
        $sql .= ', s.sale_type';
    } else {
        $sql .= ", NULL AS sale_type";
    }

    $sql .= ' FROM sales s'
        . ' INNER JOIN books b ON b.id = s.book_id'
        . ' WHERE s.sale_date = ?'
        . ' ORDER BY b.name ASC';

    $statement = $db->prepare($sql);
    $statement->bind_param('s', $date);
    $statement->execute();

    $result = $statement->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'quantity' => (int) $row['quantity'],
            'sale_type' => $row['sale_type'] !== null ? (string) $row['sale_type'] : null,
            'price' => (float) $row['price'],
            'total_value' => (float) $row['total_value'],
            'sale_date' => (string) $row['sale_date'],
        ];
    }

    return $rows;
}

/**
 * Computes total quantity and revenue for the selected date.
 *
 * @return array<string, float|int>
 */
function summarize_sales(mysqli $db, string $date): array
{
    if (!table_has_column($db, 'sales', 'sale_date')) {
        return [
            'quantity' => 0,
            'value' => 0.0,
        ];
    }

    $sql = 'SELECT COALESCE(SUM(s.quantity), 0) AS total_quantity, COALESCE(SUM(s.quantity * b.price), 0) AS total_value'
        . ' FROM sales s'
        . ' INNER JOIN books b ON b.id = s.book_id'
        . ' WHERE s.sale_date = ?';

    $statement = $db->prepare($sql);
    $statement->bind_param('s', $date);
    $statement->execute();

    $result = $statement->get_result()->fetch_assoc();

    return [
        'quantity' => (int) ($result['total_quantity'] ?? 0),
        'value' => (float) ($result['total_value'] ?? 0.0),
    ];
}

function format_currency(float $amount): string
{
    return number_format($amount, 2, '.', ' ');
}

function format_quantity(int $quantity): string
{
    return number_format($quantity, 0, '.', ' ');
}
