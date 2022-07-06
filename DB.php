<?php
class Config {
    public $db_host = 'localhost';
    public $db_name = 'zedgedb';
    public $db_user = 'root';
    public $db_password = '';
    public $db_charset = 'utf8mb4';
}

class DB extends Config{
    protected $db;
    protected $table = null;
    protected $data = null;
    protected $count = 0;
    protected $total = 0;
    protected $perPage = 10;
    protected $pages = 0;
    protected $page = 0;
    protected $where = [];
    protected $query = null;
    protected $usingPaginator = false;
    protected $error = null;
    protected $first = null;
    protected $limit;
    
    private static function connection($db_host, $db_name, $db_user, $db_password, $db_charset = 'utf8mb4'){
        try {
            $pdoConfig = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
            return new PDO($pdoConfig, $db_user, $db_password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        catch (\PDOException $e) {
            self::$error = $e->getMessage();
            return $e->getMessage();
        }
    }
    
    function __construct(){
        // Connect to databse
        $this->db = $this->connection($this->db_host, $this->db_name, $this->db_user, $this->db_password, $this->db_charset = 'utf8mb4');
    }
    
    public function __call($method, $args)
    {
        return $this->call($method, $args);
    }

    public static function __callStatic($method, $args)
    {
        return (new static())->call($method, $args);
    }

    private function call($method, $args)
    {
        if (! method_exists($this , '_' . $method)) {
            throw new Exception('Call undefined method ' . $method);
        }

        return $this->{'_'.$method}(...$args);
    }

    public function _query($query) {
        $this->query = trim(strtolower($query));
        return $this;
    }
    
    public function limit ($max){
        $this->limit = (int)trim($max);
        $this->query = $this->query.' limit '.$this->limit;   
        return $this;
    }
    
    public function getQuery(){
        return $this->query;
    }
    
    public function get() {
        if($this->usingPaginator === true){
            return (object) $this->data;
        }
        
        $run = $this->db->query($this->query);
        $this->data = $run->fetchAll();
        
        return (object) $this->data;
    }
    
    public function first() {
        if($this->usingPaginator === true){
            return $this->count > 0 ? $this->data[0] : null;
        }
        
        $run = $this->db->query($this->query);
        $this->data = $run->fetchAll();
        $this->first = $run->rowCount() > 0 ? $this->data[0] : null;
        
        return (object) $this->first;
    }
    
    public function count() {    
        if($this->usingPaginator === true){
            return $this->count;
        } 
        
        $run = $this->db->query($this->query);
        $this->count = $run->rowCount();
        return (int) $this->count;
    }
    
    public function total(){
        if($this->usingPaginator === true){
            return $this->total;
        }
        
        $run = $this->db->query($this->query);
        $this->data = $run->fetchAll();
        $this->total = $run->rowCount();
        return (int) $this->total;
    }
    
    public function orderBy($a = 'asc', $b = '') {
        $a = trim(strtolower($a));
        $b = trim(strtolower($b));
        
        if(strlen($a) > 0 && strlen($b) > 0){
            $this->query.' order by '.$a.' '.$b;
        }
        elseif(strlen($a) > 0 && strlen($b) == 0){
            if($a == 'asc' || $a == 'desc'){
                $$this->query.' order by id '.$a;
            }
            else{
                $this->query = $this->query.' order by '.$a.' asc';   
            }
        }
        else{
            $this->query.' order by id asc';
        }
 
        return $this;
    }
    
    public function where(...$params){
        $useWord = strpos($this->query, 'where') !== false ? 'and' : 'where';
        
        if(count($params) == 3){
            $this->query = $this->query.' '.$useWord.' '.trim(strtolower($params[0])).' '.trim(strtolower($params[1])).' '.trim(strtolower($params[2]));
        }
        elseif(count($params) == 2){
            $this->query = $this->query.' '.$useWord.' '.trim(strtolower($params[0])).' = '.trim(strtolower($params[1]));
        }
        
        return $this;
    }
    
    public function _table($table_name){
        $this->table = trim($table_name);
        
        if(strpos($this->query, 'select') !== false){
            $this->query .= ' from '.$this->table;
            return $this;
        }
        
        $this->query = 'select * from '.$this->table;
        return $this;
    }
    
    public function _select($select_column){
        $this->query = 'select '.trim($select_column);
        return $this;
    }
    
    public function perPage(){
        return $this->perPage;
    }
    
    public function num_pages(){
        return $this->pages;
    }
    
    public function current_page(){
        return $this->page;
    }
    
    public function paginate($perPage = 10) {
        // Tell count(), get(), total(), first() and other functions that we're using pagination
        $this->usingPaginator = true;
        
        // Set per-page results number
        $this->perPage = (int)trim($perPage);
        
        // Current page correction
        $this->page = isset($_GET['page']) ? (int)trim($_GET['page']) : 1;
        
        // Calculate the starting point
        $starting_limit = ($this->page - 1) * $perPage;
        
        // keep the query to count total results
        $queryWithNoLimit = $this->query;
        
        // Modify the query code - add `LIMIT`
        $this->query = $this->query.' LIMIT '.$starting_limit.','.$this->perPage;
        
        // Run the query
        $run = $this->db->query($this->query);
        
        // Set fetched array to the $data varriable
        $this->data = $run->fetchAll();
        
        // Count results on current page
        $this->count = $run->rowCount();
        
        // Count results on current page
        $this->total = $this->db->query($queryWithNoLimit)->rowCount();
        
        // calculate total pages
        $this->pages = (int)ceil($this->total / $this->perPage);
        
        // Output the class object
        return $this;
    }
    
}