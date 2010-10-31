<?php

class Scaffold extends Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}
	
	private $author		= "My Name <me@company.com>";
	private $copyright	= "My Company";
	
	private $tables		= array();
	private $models		= array();
	private $datatypes	= array(
		'CHAR', 'VARCHAR', 'BINARY', 'VARBINARY', 'TINYBLOB', 'TINYTEXT', 'BLOB',
		'TEXT', 'MEDIUMBLOB', 'MEDIUMTEXT', 'LONGBLOB', 'LONGTEXT', 'ENUM',
		'SET', 'BOOL', 'BOOLEAN', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT',
		'INTEGER', 'BIGINT', 'FLOAT', 'DOUBLE', 'DECIMAL', 'NUMERIC', 'BIT',
		'DATE', 'TIME', 'DATETIME', 'TIMESTAMP', 'YEAR'
	);
	
	private $header = <<<EOT
<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
		
/**
 * %1\$s Model
 *
 * @author %2\$s
 * @copyright Copyright (C) %3\$d, %4\$s
 **/

class %1\$s_model extends MY_Model
{
	function __construct()
	{
		parent::__construct();
		
		%5\$s\$this->schema = array(

EOT;

	private $footer = <<<EOT
		);
	}
}
/* End of file %1\$s_model.php */
/* Location: ./application/models/%1\$s_model.php */
EOT;
	
	function _remap()
	{		
		ob_start();
		
		$tables = $this->db->list_tables();
		
		foreach ($tables as $table)
		{
			$query = $this->db->query("SHOW COLUMNS FROM `$table`");
			$fields = $query->result();
			
			foreach ($fields as $field)
			{
				$this->tables[$table][] = $this->_parse_column($field);
			}
			
			echo "\t<li><a href='#$table'>$table</a></li>\n";
		}
		
		echo "</ul>\n";
		
		$this->models = $this->_format_output();
		
		foreach ($this->models as $name => $data)
		{
			echo "<h1><a name='$name'></a>$name</h1>\n";
			echo "<a href='#'>TOP</a><br />\n";
			echo "<textarea style='width: 1000px; height: 500px;'>$data</textarea>\n";
		}
		
		$out = ob_get_contents();
		ob_end_clean();
		
		if (count($_POST) > 0)
			$this->_export();
		
		echo "<strong>".count($tables)." models generated in "
		     . $this->benchmark->elapsed_time('total_execution_time_start')
		     . " seconds</strong><br />\n"
		     . '<form method="post">' . "\n"
		     . '<fieldset><legend>Export</legend>' . "\n"
		     . '<label><input type="checkbox" name="backup" value="1" checked="checked" /> Backup</label><br />' . "\n"
		     . '<input name="save" type="submit" value="Save into ./application/models" />' . "\n"
		     . "</fieldset></form>\n"
		     . "<ul>\n";
		
		echo $out;
	}
	
	private function _export()
	{
		$backup = FALSE;
		
		if ($this->input->post('backup') == 1)
		{
			$backup = TRUE;
		}
		
		foreach ($this->models as $name => $data)
		{
			$fullpath = FCPATH . APPPATH . 'models/' . $name . '_model' . EXT;
			
			if ($backup === TRUE && file_exists($fullpath))
			{
				copy($fullpath, $fullpath . '.' . time() . '.bak');
				echo "Backup of " . $fullpath . " created<br />\n";
			}
			
			file_put_contents($fullpath, $data);
		}
		
		echo "Export completed.<br />\n";
	}
	
	private function _parse_column($field)
	{
		$arr = array(
			'name'		=> NULL,
			'datatype'	=> NULL,
			'maxlength'	=> NULL,
			'flags'		=> array(), // PK, AI, NN, UN
			'default'	=> NULL
		);
		
		$arr['name'] = $field->Field;
		
		if ($field->Key == 'PRI')
		{
			$arr['flags'][] = 'PK';
		}
		
		if ($field->Extra == 'auto_increment')
		{
			$arr['flags'][] = 'AI';
		}
		
		if ($field->Null == 'NO' && ! in_array('PK', $arr['flags']))
		{
			$arr['flags'][] = 'NN';
		}
		
		if ($field->Default != '')
		{
			$arr['default'] = $field->Default;
		}
		
		preg_match_all('/([^ ()]+)/', $field->Type, $matches);
		
		$matches = $matches[0];
				
		foreach ($matches as $match)
		{
			if (in_array($arr['datatype'], array('DECIMAL', 'NUMERIC'))
			    && strpos($match, ',') !== FALSE)
			{
				$match = str_replace(',', '.', $match);
				$arr['maxlength'] = floatval($match);
			}
			else if (in_array($arr['datatype'], array('ENUM', 'SET'))
			    && strpos($match, "'") !== FALSE)
			{
				$match = str_replace("'", '', $match);
				$arr['maxlength'] = explode(',', $match);
			}
			else if (is_numeric($match))
			{
				$arr['maxlength'] = $match;
			}
			else if ($match == 'unsigned')
			{
				$arr['flags'][] = 'UN';
			}
			else if (in_array(strtoupper($match), $this->datatypes))
			{
				$arr['datatype'] = strtoupper($match);
			}
		}
		
		return $arr;
	}
	
	private function _format_output()
	{
		$models = array();
		
		foreach ($this->tables as $name => $fields)
		{
			$max = array(
				'name'		=> 0,
				'datatype'	=> 11,
				'length'	=> 5,
				'flags'		=> 8
			);
			
			$enums = array();
			$enums_str = '';
			$enum_idx = 1;
			
			foreach ($fields as $field)
			{
				$length = strlen($field['name']);
				
				if ($length > $max['name'])
				{
					$max['name'] = $length;
				}
				
				if (in_array($field['datatype'], array('ENUM', 'SET')))
				{
					$enums[] = $field['maxlength'];
				}
			}
			
			foreach ($enums as $key => $enum)
			{
				$enums_str .= '$e' . ($key + 1) . ' = array(';
				
				foreach ($enum as $idx => $val)
				{
					$enum[$idx] = "'".$val."'";
				}
				
				$enums_str .= implode(', ', $enum);
				$enums_str .= ");\n\t\t";
			}
			
			if ($enums_str != '')
			{
				$enums_str .= "\n\t\t";
			}
			
			$str = sprintf($this->header, ucfirst($name), $this->author,
			               date('Y'), $this->copyright, $enums_str);
			
			foreach ($fields as $field)
			{
				$flags = implode('+', $field['flags']);
				
				if ($flags == '')
				{
					$flags = 'NULL';
				}
				
				if ($field['maxlength'] == NULL)
				{
					$field['maxlength'] = 'NULL';
				}
				
				if (is_array($field['maxlength']))
				{
					$field['maxlength'] = '$e' . $enum_idx++;
				}
				else
				{
					$field['maxlength'] = (string) $field['maxlength'];
				}
				
				if ($field['default'] == NULL)
				{
					$field['default'] = 'NULL';
				}
				else if ( ! is_numeric($field['default']))
				{
					$field['default'] = "'" . $field['default'] . "'";
				}
				
				if (empty($field['flags']))
				{
					$field['flags'] = 'NULL';
				}
			
				$str .= "\t\t\t'" . $field['name'] . "'";
				$str .= str_repeat(' ', $max['name'] - strlen($field['name']));
				$str .= ' => array(';
				$str .= $field['datatype'];
				$str .= str_repeat(' ', $max['datatype'] - strlen($field['datatype']));
				$str .= ', ' . $field['maxlength'];
				$str .= str_repeat(' ', $max['length'] - strlen($field['maxlength']));
				$str .= ', ' . $flags;
				$str .= str_repeat(' ', $max['flags'] - strlen($flags));
				$str .= ', ' . $field['default'] . "),\n";
			}
			
			$str .= sprintf($this->footer, strtolower($name));
			
			$models[$name] = $str;
		}
		
		return $models;
	}
}

/* End of file scaffold.php */
/* Location: ./application/controllers/scaffold.php */