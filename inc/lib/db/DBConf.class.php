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
   const TIPO_OBRA = 'OBRA';
   
   private $confFileName = 'db_config.php';
   private $db_host;
   private $db_user;
   private $db_pwd;
   private $db_name;
   private $db_type;
   private $db_source_id;
   private $source_name;

   public function __construct( $confKey ) {
      $this->source_name = $confKey;

      $pathArray = explode( PATH_SEPARATOR, get_include_path() );
      $this->confFileName = $pathArray[2]."db/{$this->confFileName}";

      if( ! file_exists( $this->confFileName ) ) {
         throw new FileException("El archivo '{$this->confFileName}' no existe");
      }

      require( $this->confFileName );

      if ( isset($db_host[$confKey]) ) {
         $this->db_host      = $db_host[$confKey];
         $this->db_user      = $db_user[$confKey];
         $this->db_pwd       = $db_pwd[$confKey];
         $this->db_name      = $db_name[$confKey];
         $this->db_type      = $db_type[$confKey];
         if ( isset($db_source_id[$confKey]) ) {
            $this->db_source_id = $db_source_id[$confKey];
         }
      } else {
         throw new ConfException("No se encontraron los datos de configuracion.");
      }
   }

   public function getUser() {
      return $this->db_user;
   }

   public function getHost() {
      return $this->db_host;
   }

   public function getPwd() {
      return $this->db_pwd;
   }

   public function getDBName() {
      return $this->db_name;
   }

   public function getSourceName() {
      return $this->source_name;
   }

   public function getSourceId() {
      return $this->db_source_id;
   }

   public function __toString() {

      $conf = "server={$this->db_host};\n"
            . "user={$this->db_user};\n"
            . "pwd={$this->db_pwd};\n"
            . "db={$this->db_name};\n"
            . "db_type={$this->db_type};\n"
            . "source_name={$this->source_name};\n"
            . "db_source_id={$this->db_source_id};";
      
      return $conf;
   }
}

class ConfException extends Exception {}
?>