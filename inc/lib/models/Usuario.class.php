<?php
require_once 'models/Obra.class.php';
require_once 'db/ModulosSAOConn.class.php';
require_once 'db/SAODBConnFactory.class.php';

class Usuario {
	
	private $id;
	private $username;
	private $email;
	private $name;
	private $conn;
	private $inactivo = true;

	public function __construct( $username ) {
		$this->username = $username;
		$this->conn = ModulosSAOConn::getInstance();
		$this->init();
	}

	public function tieneAccesoATodasObras( SAODBConn $conn ) {

		$tsql = "SELECT 1
				 FROM
				 	[dbo].[usuarios]
				 WHERE
				 	[usuario] = ?
				 		AND
				 	[id_obra] IS NULL";

		$params = array( $this->username );
		$data = $conn->executeQuery( $tsql, $params );

		if ( count( $data ) > 0 )
	    	return true;
	    else
	    	return false;
	}

	private function getObrasBaseDatos( SAODBConn $conn ) {

		$params = array();
		$filter = '';

		if ( ! $this->tieneAccesoATodasObras( $conn ) ) {
			$filter = " INNER JOIN 
					[dbo].[usuarios_obras]
					ON
						[obras].[id_obra] = [usuarios_obras].[id_obra]
					WHERE
						[usuarios_obras].[usuario] = ? ";

			$params[] = $this->username;
		}

		$tsql = "SELECT
					  [obras].[id_obra]
				FROM
					[dbo].[obras]
				{$filter}
				ORDER BY
					IIF( LEN([nombre_publico]) = 0, [nombre], [nombre_publico])";

		$data = $conn->executeQuery( $tsql, $params );

		$obraList = array();
		foreach ( $data as $obra ) {
			$obraList[] = new Obra( $conn, $obra->id_obra );
		}

		return $obraList;
	}

	private function init() {
		
		$tsql = "SELECT
					  [Usuarios].[IDUsuario]
					, [Usuarios].[Nombre]
					, [Usuarios].[Usuario]
					, [Usuarios].[Email]
					, [Usuarios].[Inactivo]
					, [Usuarios].[AccesoTodosProyectos]
				FROM
					[Seguridad].[Usuarios]
				WHERE
					[Usuarios].[Usuario] = ?";

		$params = array( $this->username );

		$data = $this->conn->executeQuery($tsql, $params);

		if (count($data) < 1) {
			throw new Exception("El usuario no fue encontrado.");
		} else {
			$this->id = $data[0]->IDUsuario;
			$this->name = $data[0]->Nombre;
			$this->email = $data[0]->Email;
			$this->inactivo = $data[0]->Inactivo;
		}
	}

	public function getUsername() {
		return $this->username;
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getEmail() {
		return $this->email;
	}

	public function getObras() {

      	$confFile = 'db/db_config.php';
		require ( $confFile );

		// leer las configuraciones que sean de tipo OBRA
        // crear una conexion por cada configuracion
        $connList = array();
		foreach ( $db_host as $key => $value ) {

			if ( $db_type[$key] === DBConf::TIPO_OBRA ) {
	        	$connList[] = SAODBConnFactory::getInstance( $key );
			}
        }

        // obtiene las obras a las que el usuario 
        // tiene acceso en cada conexion
        $obraList = array();
        foreach ( $connList as $conn ) {
        	foreach ( $this->getObrasBaseDatos( $conn ) as $obra ) {
        		$obraList[] = $obra;
        	}
        }

        return $obraList;
	}

	public function __toString() {
		return "id={$this->id};\n"
			 . "username={$this->username};\n"
			 . "name={$this->name};\n"
			 . "email={$this->email}";
	}
}
?>