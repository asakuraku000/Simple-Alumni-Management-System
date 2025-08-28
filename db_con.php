<?php
/**
 * Northern Iloilo State University Alumni System
 * Database Connection Configuration
 * 
 * Simple and clean database connection using PDO
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'nisu_alumni_system');
define('DB_USER', 'root');           // Change to your database username
define('DB_PASS', '');               // Change to your database password
define('DB_CHARSET', 'utf8mb4');

// PDO options for better security and error handling
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
];

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        $options
    );
    
    // Connection successful message (optional - remove in production)
    // echo "Database connected successfully!";
    
} catch (PDOException $e) {
    // Handle connection error
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Helper function to execute queries easily
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return PDOStatement
 */
function query($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}

/**
 * Helper function to fetch single row
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return array|false
 */
function fetchRow($sql, $params = []) {
    return query($sql, $params)->fetch();
}

/**
 * Helper function to fetch all rows
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return array
 */
function fetchAll($sql, $params = []) {
    return query($sql, $params)->fetchAll();
}

/**
 * Helper function to get last insert ID
 * 
 * @return string
 */
function lastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Helper function to begin transaction
 */
function beginTransaction() {
    global $pdo;
    $pdo->beginTransaction();
}

/**
 * Helper function to commit transaction
 */
function commit() {
    global $pdo;
    $pdo->commit();
}

/**
 * Helper function to rollback transaction
 */
function rollback() {
    global $pdo;
    $pdo->rollBack();
}

/*
==============================================
USAGE EXAMPLES:
==============================================

// Include this file in your PHP scripts
require_once 'db_con.php';

// Example 1: Fetch all alumni
$alumni = fetchAll("SELECT * FROM alumni WHERE is_active = 1");

// Example 2: Fetch single alumni by ID
$alumni = fetchRow("SELECT * FROM alumni WHERE id = ?", [1]);

// Example 3: Insert new alumni
query("INSERT INTO alumni (student_id, first_name, last_name, email) VALUES (?, ?, ?, ?)", 
      ['2024-001', 'John', 'Doe', 'john.doe@email.com']);

// Example 4: Update alumni
query("UPDATE alumni SET email = ? WHERE id = ?", ['newemail@email.com', 1]);

// Example 5: Search alumni
$searchResults = fetchAll("SELECT * FROM alumni WHERE CONCAT(first_name, ' ', last_name) LIKE ?", 
                          ['%garcia%']);

// Example 6: Get alumni with college info (using JOIN)
$alumniWithCollege = fetchAll("
    SELECT a.*, c.name as college_name 
    FROM alumni a 
    JOIN colleges c ON a.college_id = c.id 
    WHERE a.batch_id = ?
", [1]);

// Example 7: Using transactions
try {
    beginTransaction();
    
    query("INSERT INTO alumni (...) VALUES (...)", [...]);
    query("INSERT INTO alumni_employment (...) VALUES (...)", [...]);
    
    commit();
    echo "Data saved successfully!";
} catch (Exception $e) {
    rollback();
    echo "Error: " . $e->getMessage();
}

==============================================
*/
?>