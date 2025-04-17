<?php

class DB {
    private static $host = 'localhost';
    private static $dbname = 'user_management';
    private static $username = 'root';
    private static $password = '';
    private static $connection = null;
    private static $affectedRows = 0;

    public static function getInstance() {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$dbname,
                    self::$username,
                    self::$password,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                throw new Exception("Connection failed: " . $e->getMessage());
            }
        }
        return self::$connection;
    }

    public static function query($sql, $params = []) {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function fetchAll($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
    }

    public static function fetchOne($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    }

    public static function fetchColumn($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->fetchColumn() : false;
    }

    public static function getLastInsertId() {
        return self::getInstance()->lastInsertId();
    }

    public static function affectedRows() {
        return self::$affectedRows;
    }

    public static function getLastError() {
        $error = self::getInstance()->errorInfo();
        return $error[2];
    }
}

// Example usage:

// // Fetch all records from a table
// $users = DB::fetchAll("SELECT * FROM users");

// // Fetch with parameters
// $activeUsers = DB::fetchAll("SELECT * FROM users WHERE status = ?", ['active']);

// // Fetch single record
// $user = DB::fetchOne("SELECT * FROM users WHERE id = ?", [1]);

// // Count records
// $count = DB::fetchColumn("SELECT COUNT(*) FROM users");

// // Custom query (for INSERT, UPDATE, DELETE)
// $result = DB::query("INSERT INTO users (name, email) VALUES (?, ?)", ['John', 'john@example.com']);

// // Check for errors
// if ($result === false) {
//     print_r(DB::getLastError());
// }


// Example of transaction handling
// try {
//     $db = DB::getInstance();
//     $db->beginTransaction();

//     DB::query("INSERT INTO users (name) VALUES (?)", ['Alice']);
//     DB::query("UPDATE accounts SET balance = balance - 100 WHERE user_id = ?", [1]);
//     DB::query("UPDATE accounts SET balance = balance + 100 WHERE user_id = ?", [2]);

//     // example of prepare
//     $stmt = $db->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
//     $stmt->execute([1, 'Transaction started']);
//     $stmt = $db->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");


//     // example of prepare and execute with foreach loop
//     $stmt = $db->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
//     $actions = [
//         [1, 'Transaction started'],
//         [2, 'Transaction in progress'],
//         [3, 'Transaction completed']
//     ];
//     foreach ($actions as $action) {
//         $stmt->execute($action);
//     }



//     $db->commit();
//     echo "Transaction completed successfully";
// } catch (Exception $e) {
//     $db->rollBack();
//     echo "Transaction failed: " . $e->getMessage();
// }