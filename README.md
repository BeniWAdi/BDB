# BDB

PHP library to (Create, Read, Update, Delete) in Mysql databases.

## Database example

Let's say we have the following database scheme:

```sql
CREATE TABLE `category` (
    `id`    INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT UNIQUE,
    `name`  TEXT
);

CREATE TABLE `tag` (
    `id`    INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT UNIQUE,
    `name`  TEXT
);

CREATE TABLE `post` (
    `id`    INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT UNIQUE,
    `title` TEXT,
    `category_id` INTEGER,
    `type`  TEXT,

    FOREIGN KEY(`category_id`) REFERENCES category(id)
);

CREATE TABLE `post_tag` (
    `id`    INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT UNIQUE,
    `tag_id`   INTEGER NOT NULL,
    `post_id`  INTEGER NOT NULL,

    FOREIGN KEY(`tag_id`) REFERENCES tag(id),
    FOREIGN KEY(`post_id`) REFERENCES post(id)
);
```

## Installation

Download package BDB, copy into your project. Open ```BDB/BConfig.php``` to setup configuration.
```
class BConfig {

    protected static $servername = "localhost";
    protected static $username = "root";
    protected static $password = "";
    protected static $dbname = "post";
    protected static $charset = "utf8";
    
    protected static $showError = true;
    protected static $showSqlWhenError = true;
    protected static $killAppWhenError = true;

}
```
Run ```BDB/RunMe.php``` copy the output script into ```BDB/DBReader.php```


## Usage

### Select
#### findByPk
Returns a record from matched ```id```. If nothing got matched ```null``` will be returned.
```
$post = BDB::findByPk("post", 1);
echo "<br/>Title : ".$post["title"];
echo "<br/>Category : ".$post["category"]["name"];

```
#### findAll
Returns all record from selected ```table```. If no record ```null``` will be returned.
```
$allPost = BDB::findAll("post");
foreach ($allPost as $post){
	echo "<br/>Title : ".$post["title"];
	echo "<br/>Category : ".$post["category"]["name"];
}

```
#### findAllByFields
Returns all record from matched dataset. If nothing got matched ```null``` will be returned.
```
$allPost = BDB::findAllByFields("post", ["title" => "Football", "category.name" => "sport"]);
foreach ($allPost as $post){
	echo "<br/>Title : ".$post["title"];
	echo "<br/>Category : ".$post["category"]["name"];

```
### Insert
Inserting data into database follow the example below:
```
$data = [
    "title" => "Post title",
    "text" => "Post text",
    "category_id" => 1
    ];
BDB::insert("post", $data);

```
Returns ```ture``` for a successful insert, ```false``` if not.

### Update
Updating from matched ```id```. If the update succeeded ```true``` will be returned, ```false``` if not.
```
$id = 1;
$data = [
    "title" => "New title",
    "text" => "new text"
    ];
BDB::update("post", $data, $id);

```
Updating from matched dataset. If the update succeeded ```true``` will be returned, ```false``` if not.
```
$conds = ["title"=>"Football", "category.name" => "sport"];
$data = [
    "title" => "New title",
    "text" => "new text"
    ];
BDB::update("post", $data, $conds);

```

### Delete
Deleting from matched ```id```. If the update succeeded ```true``` will be returned, ```false``` if not.
```
BDB::delete("post", 1);

```
Deleting from matched dataset. If the update succeeded ```true``` will be returned, ```false``` if not.
```
$conds = ["title"=>"Football", "category.name" => "sport"];
BDB::delete("post", $conds);

```

### Query
To create your custom ```query```. First ```parameter``` for ```sql statement```, second ```parameter``` for ```paramValues```, third ```parameter``` for ```bind paramTypes``` defaults ```string```.
```
$sql = "SELECT id FROM post WHERE id = :id AND title = :title";
$paramValues = [":id"=>1, ":title"=>"Football"];
$paramTypes = ["id"=> PDO::PARAM_INT];
$result = BDB::query($sql, $paramValues, $paramTypes);

```
Returns will be ```multiples array``` or ```null``` if ```count(column) > 0```, ```bool``` if ```count(column) = 0```.

### API
Function | Parameters | Return
---------|------------|-------
`findByPk` | `$tableName, $primaryKey`|`array`/`null`.
`findAll` | `$tableName, $additionalQuery = ""` | `array`/`null`.
`searchAll` | `$tableName, $fieldsCompare, $keyword, $additionalQuery = ""` | `array`/`null`.
`findAllByFields` | `$tableName, array $fields, $additionalQuery = ""` | `array`/`null`.
`insert` | `$tableName, array $fields` | `bool`.
`update` | `$tableName, array $fields, $pkOrFields, $operator = "AND"` | `bool`.
`delete` | `$tableName, $pkOrFields, $operator = "AND"` | `bool`.
`query` | `$statement, array $bindValues = null, array $bindTypes = null, $returnOption = 0` | Mixed.
`beginTransaction` | | `bool`.
`rollBackTransaction` | | `bool`.
`commitTransaction` | | `bool`.
`isInTransaction` | | `bool`.

