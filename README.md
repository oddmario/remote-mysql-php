# remote-mysql-php
a PHP proxy script to remotely use a MySQL server

Nothing hard nothing complicated here :) just a really simple script that I made to remotely access a MySQL server which was binding only locally

## Installation
- Simply, just put the three files of the project together and configure the `config.php` file to match your credentials.

## Usage

#### Example `SELECT` query
```shell
POST https://myproject.com/index.php?password=MyConfiguredPasswordHere

Request body:
{
    "sql": "SELECT * FROM users LIMIT 1"
}

Response body:

{
    "error": false,
    "rows": [
        {
            "id": 1,
            "username": "mario",
            "email": "...",
            "password": "..."
        }
    ],
    "num_rows": 1,
    "insert_id": 0
}
```

#### Example `SELECT` query without returning the result rows
```shell
POST https://myproject.com/index.php?password=MyConfiguredPasswordHere

Request body:
{
    "sql": "SELECT * FROM users LIMIT 1",
    "no_rows": true
}

Response body:

{
    "error": false,
    "rows": [],
    "num_rows": 1,
    "insert_id": 0
}
```

#### Example `UPDATE` query
```shell
POST https://myproject.com/index.php?password=MyConfiguredPasswordHere

Request body:
{
    "sql": "UPDATE users SET username = 'john' WHERE id = 1",
}

Response body:

{
    "error": false,
    "rows": [],
    "num_rows": 0,
    "insert_id": 0
}
```

#### Example prepared `UPDATE` query
```shell
POST https://myproject.com/index.php?password=MyConfiguredPasswordHere

Request body:
{
    "sql": "UPDATE users SET username = ? WHERE id = ?",
    "bind_letters": "si",
    "bind_values": ["john", 1]
}

Response body:

{
    "error": false,
    "rows": [],
    "num_rows": 0,
    "insert_id": 0
}
```