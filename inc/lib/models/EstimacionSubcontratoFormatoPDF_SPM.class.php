<?php
require_once "models/EstimacionSubcontratoFormatoPDF.class.php";

/*
 * Formato exclusivo para el proyecto Hotel Playa Mujeres Mobiliario
 * Se cambiaron los titulos de algunas etiquetas para hacerlo parecer
 * a las ordenes de compra.
*/
class EstimacionSubcontratoFormatoPDF_SPM extends EstimacionSubcontratoFormatoPDF {

	protected $titulo = 'ORDEN DE SUMINISTRO';
	protected $contratista_label = 'Proveedor:';
	protected $numero_de_estimacion_label = 'No. Suministro';
	protected $semana_de_contrato_label = 'Semana de Suministro';
	protected $firma_contratista_titulo_label = 'por el proveedor';
	protected $firma_cliente_descripcion_label = 'gerente de producción';
}