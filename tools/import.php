<?php
    require_once 'config.inc.php';

    mysql_connect(MYSQL_SERVER, MYSQL_USERNAME, MYSQL_PASSWORD);
    mysql_query("SET NAMES utf8");
    mysql_select_db(MYSQL_DATABASE);
    
    $categories = Array( 'infinitive' => Array('infinitive-present'),
                         'indicative' => Array('present', 'imperfect', 'future', 'simple-past'),
                         'conditional'=> Array('present'),
                         'subjunctive'=> Array('present', 'imperfect'),
                         'imperative' => Array('imperative-present'),
                         'participle' => Array('present-participle', 'past-participle') );

    $replace_array = Array( 'infinitive-present' => '',
                            'present' => '_present',
                            'imperfect' => '_imperfect',
                            'future' => '_future',
                            'simple-past' => '_past',
                            'imperative-present' => '',
                            'present-participle' => '_present',
                            'past-participle' => '_past' );
    
    function build_query($name, $data)
    {
       global $template_columns;
       $field_names[] = 'name'; $field_values[] = '\'' .mysql_real_escape_string($name) . '\'';
       foreach($data as $element_name => $element)
       {
           if (count($element) == 1) 
           {
               $field_names[] =  "$element_name";
               if ($element[0] === NULL)
                 $field_values[] = 'NULL'; else
                 $field_values[] = '\'' . $element[0] . '\'';
           }
           else {
                foreach($element as $id => $value)
                {
                  $field_names[] = "{$element_name}_{$id}";
                  var_dump($value);
if ($value === NULL)
                    $field_values[] = 'NULL'; else
                    $field_values[] = '\'' .mysql_real_escape_string($value) . '\'';
                }    
           }
               
       }
       $template_columns = $field_names;
       return "INSERT INTO `templates` (" . implode(', ', $field_names)
                       . ") VALUES (" . implode(', ', $field_values) . ");";
    }

    function add_meta($a)
    {
      return "$a VARCHAR(30) NULL";
    }

    function build_tables()
    {
        global $template_columns;
        if (count($template_columns) == 0)
            die('Template columns not yet initialized.');
        $new = array_map(add_meta, $template_columns);
        $query = "CREATE TABLE templates (" . implode(" ,", $new) . ") COLLATE utf8_general_ci;";
        mysql_query($query);
        print mysql_error();
        $query = "CREATE TABLE verbs (verb VARCHAR(30) NOT NULL,".
                                         "template VARCHAR(30) NOT NULL,".
										 "prefix VARCHAR(30) NOT NULL,".
                                         "aspirate_h BOOL NOT NULL,".
										 "reflexive BOOL NOT NULL) COLLATE utf8_general_ci;";
        mysql_query($query);
        print mysql_error();
        }

    $xml = simplexml_load_file('conjugation-fr.xml');
    foreach ($xml->template as $template)
    {
      $template_name = $template['name'];
      unset($parsed);   # We reset the current parsed data
      foreach ($categories as $main_cat => $sub_cat)
      {
        foreach ($sub_cat as $value)
        {
          $counter = 0; 
          
          foreach ($template->{$main_cat}->{$value}->p as $current)
          {
              $last = NULL;
              
              foreach ($current->i as $__last) $last = $__last;

              if ($last !== NULL) $last = (string)$last;
              
             // var_dump($last);
                  
              $parsed[$main_cat . $replace_array[$value]][$counter] = $last;              
              $counter++;
          }
        }
      }
      $query = build_query($template_name, $parsed);
      if (!$generated_tables)
      {
        build_tables();
        $generated_tables = TRUE;
      }
      mysql_query($query);
      print mysql_error() . '<br>';
     }

    # ----------------------------------------------------------------------------#

    $xml = simplexml_load_file('verbs-fr.xml');
    foreach ($xml->v as $verb)
    {
      $query = 'INSERT INTO `verbs` (verb, template, prefix, aspirate_h, reflexive) VALUES (' .
      '\'' .mysql_real_escape_string($verb->i) . '\', \'' .
      mysql_real_escape_string($verb->t) . '\', \'' .  
	  mysql_real_escape_string(substr($verb->i, 0, -(strlen($verb->t)-strpos($verb->t,":")-1))) . '\', ' .
      mysql_real_escape_string( (int)isset($verb->{'aspirate-h'}) ) . ', 0);';
      mysql_query($query);
      print mysql_error();
    }

    build_tables();
?>
