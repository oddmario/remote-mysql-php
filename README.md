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

#### Example `SELECT` query while expecting a BLOB column
Note that if any of the returned columns is of a BLOB type (or any of it's derivatives), the response will most likely be blank (because JSON can't output or take binary data). So if you're expecting a BLOB column to be returned, it's better to specify the column name in the `b64_fields` part of the request.

This way the script will Base64-encode the value of the column before returning it, then you can Base64-decode it in your application as per your needs.

```shell
POST https://myproject.com/index.php?password=MyConfiguredPasswordHere

Request body:
{
    "sql": "SELECT * FROM users LIMIT 1",
    "b64_fields": ["profile_picture"]
}

Response body:

{
    "error": false,
    "rows": [
        {
            "id": 1,
            "username": "mario",
            "email": "...",
            "password": "...",
            "profile_picture": "[base64 encoded value of the profile picture bytes will be returned here]"
        }
    ],
    "num_rows": 1,
    "insert_id": 0
}
```

#### Example `INSERT` query with binary BLOB data
Now here if you want to insert a row that has a BLOB column, you have to Base64-encode the column value first (because like we said in the above example, JSON can't take binary data). You also have to append `b64decode::` before the Base64 encoded string to let the script know that this value should be Base64-decoded before sent to the MySQL server.

Also note that in order to apply all this, you have to send the query as a prepared statement.

Bit lost? Examples are awesome when it comes to complicated explanations, so check out the below example:

Assume that our binary data is `Hello World`, we're going to Base64-encode this first. The output will be `SGVsbG8gV29ybGQ=`

Now let's send our request as the following:

```shell
POST https://myproject.com/index.php?password=MyConfiguredPasswordHere

Request body:
{
    "sql": "INSERT INTO users (profile_picture) VALUES (?)",
    "bind_letters": "b",
    "bind_values": ["b64decode::SGVsbG8gV29ybGQ="]
}

Response body:

{
    "error": false,
    "rows": [],
    "num_rows": 0,
    "insert_id": 24
}
```