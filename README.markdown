CodeIgniter-MY_Model
====================
Replaces CodeIgniter's default model and adds CRUD helper functions along with
validation against a schema.

Example
-------
The following is an example of the schema you need to write for each table/model
in your application. Since this custom class provides the basic CRUD functions
such as create() you do not need to define these for every model and eliminates
repeated code. The CRUD functions are all validated against the schema you write
so that if you inadvertly forget to pass a value for a required (not null)
column, a catachable exception will be thrown with a clear message why the query
was rejected, all before the query is even sent to the database. Other
validations include data type checking, protection against string truncation,
conversion from empty string to NULL (when applicable).

	class User_model extends MY_Model 
	{	
		function __construct()
		{
			parent::MY_Model();
			
			$this->schema = array( //       < DATATYPE  | LENGTH | FLAGS  | DEFAULT >
				'id'				=> array(INT		, NULL	, PK+AI+UN,	NULL),
				'username'			=> array(VARCHAR	, 45	, NN,		NULL),
				'password'			=> array(CHAR		, 40	, NN,		NULL),
				'salt'				=> array(CHAR		, 40	, NN,		NULL),
				'email'				=> array(VARCHAR	, NULL	, NN,		NULL),
				'realname'			=> array(VARCHAR	, NULL	, NULL,		NULL),
				'date_joined'		=> array(DATETIME	, NULL	, NN,		$this->now()),
				'last_login'		=> array(DATETIME	, NULL	, NN,		$this->now()),
				'active'			=> array(TINYINT	, 1		, NN,		1)
			);
			
			$this->before_create[] = '_prepare_password';
			$this->before_update[] = '_prepare_password';
		}
	}

Requirements
------------
* CodeIgniter 2.0
* Schema definitions for each model