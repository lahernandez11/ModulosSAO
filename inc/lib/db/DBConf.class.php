<?php
require_once 'FileException.class.php';

/**
* Clase que carga desde un arreglo en un archivo php
* la configuracion para realizar una conexion a un servidor
* de base de datos
*
* @author Uziel Bueno
* @date 07.02.2013
*/
class DBConf {

   private $confFileName = 'db_config.php';
   private $dbServer;
   private $dbUser;
   private $userPassword;
   private $dbName;

   public function __construct( $confKey ) {

      $pathArray = explode( PATH_SEPARATOR, get_include_path() );
      $this->confFileName = $pathArray[2].'/db/db_config.php';

      if( ! file_exists( $this->confFileName ) ) {

         throw new FileException("El archivo '$this->confFileName' no existe");
      }

      include( $this->confFileName );

      $this->dbServer     = $dbServer[$confKey];
      $this->dbUser       = $dbUser[$confKey];
      $this->userPassword = $userPassword[$confKey];
      $this->dbName       = $dbName[$confKey];
   }

   public function getDBUser() {
      
      return $this->dbUser;
   }

   public function getDBServer() {
      
      return $this->dbServer;
   }

   public function getUserPassword() {
      
      return $this->userPassword;
   }

   public function getDBName() {
      
      return $this->dbName;
   }

   public function __toString() {

      $conf = "server=$this->dbServer;user=$this->dbUser;pwd=$this->userPassword;database=$this->dbName;";

      return $conf;
   }
}
?>