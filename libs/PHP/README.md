# Remote MySQL PHP
## The PHP library for it

This library was made to look as much as possible like the normal PHP MySQLi client we are used to.

### Examples

- Prepared `SELECT` query
```php
$conn = new RemoteMysql('http://example.com/remote-mysql-php/?password=123456789');

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", "coolusername");
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

echo $result->num_rows;
foreach($result->fetch_all() as $row) {
    echo $row['username'] . "<br />";
}
```

- Normal `SELECT` query
```php
$conn = new RemoteMysql('http://example.com/remote-mysql-php/?password=123456789');

$query = $conn->query("SELECT * FROM users");

echo $query->num_rows;
foreach($query->fetch_all() as $row) {
    echo $row['username'] . "<br />";
}
```

- Normal `SELECT` query without returning any rows (faster in case you want to just count the number of rows selected)
```php
$conn = new RemoteMysql('http://example.com/remote-mysql-php/?password=123456789');

$query = $conn->query("SELECT * FROM users", false);

echo $query->num_rows;
```