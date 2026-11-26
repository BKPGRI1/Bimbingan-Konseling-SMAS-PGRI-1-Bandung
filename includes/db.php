<?php
// includes/db.php
function getDB() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

function sanitize($input) {
    $conn = getDB();
    return $conn->real_escape_string(htmlspecialchars(strip_tags($input)));
}

function query($sql) {
    $conn = getDB();
    return $conn->query($sql);
}

function getRow($sql) {
    $result = query($sql);
    return $result->fetch_assoc();
}

function getRows($sql) {
    $result = query($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function insert($table, $data) {
    $conn = getDB();
    $fields = array_keys($data);
    $values = array_map(function($value) use ($conn) {
        return "'" . $conn->real_escape_string($value) . "'";
    }, array_values($data));
    
    $sql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ")";
    if ($conn->query($sql)) {
        return $conn->insert_id;
    }
    return false;
}

function update($table, $data, $where) {
    $conn = getDB();
    $sets = [];
    foreach ($data as $key => $value) {
        $sets[] = "$key = '" . $conn->real_escape_string($value) . "'";
    }
    $sql = "UPDATE $table SET " . implode(',', $sets) . " WHERE $where";
    return $conn->query($sql);
}

function delete($table, $where) {
    $conn = getDB();
    $sql = "DELETE FROM $table WHERE $where";
    return $conn->query($sql);
}

function getLastError() {
    $conn = getDB();
    return $conn->error;
}
?>