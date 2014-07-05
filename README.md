# Abstract

SQL_Maker - SQL builder written in PHP.


SQL::Maker - SQL builder written in perl.
     https://github.com/tokuhirom/SQL-Maker

# REQUIREMENTS

- PHP 5.3 or later

# The JSON SQL Injection Vulnerability

SQL_Maker has a JSON SQL Injection Vulnerability if not used in `strict` mode.

Therefore, I strongly recommend to use SQL_Maker in `strict` mode.
You can turn on the `strict` mode by passing `'strict' => 1` as:

```php
new SQL_Maker(array(..., 'strict' => 1))
new SQL_Maker_Select(array(..., 'strict' => 1))
```

In strict mode, array or hash conditions are not accepted anymore. A sample usage snippet is shown in below:

```php
require_once 'SQL/Maker.php';
require_once 'SQL/QueryMaker.php';

$builder = new SQL_Maker(array('driver' => 'mysql', 'strict' => 1));

$builder->select('user', array('*'), array('name' => $json['name']));
// => SELECT * FROM `user` WHERE `name` = ?

$builder->select('user', array('*'), array('name' => array('foo', 'bar')));
// => Exception! Will not generate SELECT * FROM `name` IN (?, ?) any more

$builder->select('user', array('*'), array('name' => sql_in(array('foo', 'bar'))));
// => SELECT * FROM `user` WHERE `name` IN (?, ?)

$builder->select('fruit', array('*'), array('price' => sql_le($json['max_price'])));
// => SELECT * FROM `fruit` WHERE `price` <= ?
```

See following articles for more details (perl version)

* http://blog.kazuhooku.com/2014/07/the-json-sql-injection-vulnerability.html (English)
* http://developers.mobage.jp/blog/2014/7/3/jsonsql-injection (Japanese)


# SYNOPSIS


## create instance

      $builder = new SQL_Maker(array('driver' => 'SQLite'));



## select()

      $table = 'pokedex';
      
      $fields = array('id', 'name');
      
      $where = array();
      $where['area'] = 'houen';
      $where['type'] = array( 'like' => '%electric%' );
      
      $opt = array();
      $opt['order_by'] = array('id');
      
      
      list($sql, $binds) =
           $builder->select($table, $fields, $where, $opt);
     
     /*
      * $sql   => "SELECT id, name
      *            FROM pokedex
      *            WHERE (area = ?) AND (type LIKE ?)
      *            ORDER BY id"
      * $binds => ("houen", "%electric%")
      */



## WHERE

     $table  = 'pokedex';
     	          
     $fields = array('*');
     	          
     $where  = array(
                   'name'      => 'Pikachu',
                   'legendry'  => null,
                   'area'      => array('ish', 'joto'),
                   'base_hp'   => array('<=' => 40),
                   'type'      => array('like' => '%electric%'),
                   'id'        => SQL_Maker::scalar( array('= (? - 0)', 25) ),
                   'available' => SQL_Maker::scalar( array('LIKE ? ESCAPE ?', '%mega\\_kick%', '\\') )
                   );
			   
     list($sql, $binds) = $builder->select($table, $fields, $where);
     			
     /*
      * WHERE ( name = "Pikachu" ) AND
      *       ( legendry IS NULL ) AND
      *       ( area IN ("ish", "joto") ) AND
      *       ( base_hp <= 40 ) AND
      *       ( type LIKE "%electric%" ) AND
      *       ( id = (25 - 0) ) AND
      *       ( available LIKE "%mega\_kick%" ESCAPE "\" );
      */



## delete()


     list($sql, $binds) = $builder->delete('pokedex', array( 'id' => '25' ));
     
     
     /*
      * $sql    => "DELETE FROM pokedex WHERE (id = ?)"
      * $binds  => (25)
      */




## insert()

      $table = 'pokedex';
   
      $values = array(
             'id' => 25,
             'name' => 'Pikachu'
	);
						
	list($sql, $binds) = $builder->insert($table, $values);
						
						
	/*
	 * $sql   => "INSERT INTO pokedex (name, id) VALUES (?, ?)"
	 * $binds => ("Pikachu", 25)
 	 */




## update()

      $table = 'pokedex';
      
      $set   = array('type' => 'electric');
      
      $where = array('id' => 25);
       
      list($sql, $binds) = $builder->update($table, $set, $where);
   
      /*
       * $sql   => "UPDATE pokedex SET type = ? WHERE (id = ?)"
       * $binds => ("electric", 25)
       */




## method chain

       $sql = new SQL_Maker_Select();
       
       $sql
       　->addSelect('foo')
       　->addSelect('bar')
       　->addSelect('baz')
       　->addFrom('table_name')
       　->asSql();
       　
      　 // => "SELECT foo, bar, baz FROM table
