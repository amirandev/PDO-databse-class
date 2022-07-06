# PDO-databse-class
A super simple php class to work with databse



### **How to use?**
Upload/put the **DB.php** somewhere in your php project and include it like this (for example)
```
<?php
require_once 'DB.php';
```

### Example 1

```
<?php
// Modern way (A little bit)
$query = DB::select('id')->table('items')->where('category_id', 13)->where('user_id', 1)->orderBy('id')->paginate(2);

echo '<pre>';
var_dump([
    'data' => $query->get(),
    'count' => $query->count(),
    'total' => $query->total(),
    'total_pages' => $query->num_pages(),
    'current_pages' => $query->current_page(),
    'per-page' => $query->perPage(),
    'first' => $query->first()
]);
echo '</pre>';
?>
```


<hr>
<h1>Example 2</h1>

```
<?php
// Classic way
$run = DB::query('SELECT * FROM items')->where('category_id', 13)->where('user_id', 1)->orderBy('id')->paginate(2);
echo '<pre>';
var_dump([
    'data' => $run->get(),
    'count' => $run->count(),
    'total' => $run->total(),
    'total_pages' => $run->num_pages(),
    'current_pages' => $run->current_page(),
    'per-page' => $run->perPage(),
    'first' => $run->first()
]);
echo '</pre>';
```
