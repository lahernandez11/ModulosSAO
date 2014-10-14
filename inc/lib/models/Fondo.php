<?php
require_once 'models/Obra.class.php';

class Fondo {

    public static function findAll() {}

    public static function findAllFondoObra(SAODBConn $conn, Obra $obra, $descripcion)
    {
        $params = array(
            $obra->getId(),
            $descripcion
        );

        $sql = "SELECT
                  [fondos].[id_fondo]
                , [fondos].[id_obra]
                , [fondos].[descripcion]
                , [fondos].[nombre]
                , [fondos].[saldo]
                , [fondos].[fecha]
                , [fondos].[cuenta_contable]
                , [fondos].[fondo_obra]
                , [fondos].[id_costo]
            FROM
                [fondos]
            WHERE
                [id_obra] = ?
                    AND
                [fondo_obra] = 1
                    AND
                [descripcion] LIKE '%' + ISNULL(?, [descripcion]) + '%'
            ORDER BY
                [fondos].[descripcion]";

        return $conn->executeQuery($sql, $params);
    }
} 