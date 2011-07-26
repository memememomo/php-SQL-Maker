# Abstract

SQL_Maker - SQL builder written in PHP.


SQL::Maker - SQL builder written in perl.
     https://github.com/tokuhirom/SQL-Maker


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
