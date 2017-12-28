<?php

namespace development\gestionBloques\funcion;

class ConsultarBloques {
	var $miConfigurador;
	var $miSql;
	var $conexion;
	var $directorioInstalacion = "blocks/";
	function __construct($sql) {
		$this->miConfigurador = \Configurador::singleton ();
		$this->miConfigurador->fabricaConexiones->setRecursoDB ( 'principal' );
		$this->miSql = $sql;
	}
	function procesarConsultaBloque() {
		$this->conexion = $this->miConfigurador->fabricaConexiones->getRecursoDB ( 'estructura' );
		if (! $this->conexion) {
			error_log ( "No se conectó" );
			$resultado = false;
		}
		
		$cadenaSql = $this->miSql->getCadenaSql ( 'consultarBloques' );
		
		$resultadoItems = $this->conexion->ejecutarAcceso ( $cadenaSql, 'busqueda' );
		var_dump($resultadoItems);

		$this->consultarBloquesDirectorio($this->directorioInstalacion);exit;
		
		$tabla = new \stdClass ();
		
		$page = $_REQUEST ['page'];
		
		$limit = $_REQUEST ['rows'];
		
		$sidx = $_REQUEST ['sidx'];
		
		$sord = $_REQUEST ['sord'];
		
		if (! $sidx)
			$sidx = 1;
		
		$filas = count ( $resultadoItems );
		
		if ($filas > 0 && $limit > 0) {
			$total_pages = ceil ( $filas / $limit );
		} else {
			$total_pages = 0;
		}
		
		if ($page > $total_pages) {
			$page = $total_pages;
		}
		$start = $limit * $page - $limit;
		if ($resultadoItems != false) {
			$tabla->page = $page;
			$tabla->total = $total_pages;
			$tabla->records = $filas;
			
			$i = 0;
			$j = 1;
			foreach ( $resultadoItems as $row ) {
				$tabla->rows [$i] ['id'] = $row ['id_bloque'];
				$tabla->rows [$i] ['cell'] = array (
						$row ['id_bloque'],
						trim ( $row ['nombre'] ),
						trim ( $row ['descripcion'] ),
						trim ( $row ['grupo'] ) 
				);
				$i ++;
			}
			
			$tabla = json_encode ( $tabla );
		} else {
			$tabla->page = 1;
			$tabla->total = 1;
			$tabla->records = 1;
			
			$tabla->rows [0] ['id'] = 1;
			$tabla->rows [0] ['cell'] = array (
					" ",
					" ",
					" ",
					" " 
			);
			$tabla = json_encode ( $tabla );
		}
		
		echo $tabla;
	}
	function consultarBloquesDirectorio($directorio) {
		
		$directorios = $this->escanearDirectorio($directorio);

		var_dump($directorios);


		foreach ($directorios as $valor) {
			
			if (file_exists($directorio.$valor."/bloque.php")) {

				$nombre_bloque=$valor;

				$arregloBloque[] = array(
					"id_bloque" => 9999 ,
					"nombre" => $valor,
					"descripcion" => "",
					"grupo" => ($directorio!='blocks/')? $directorio:""
					 );


					
			}
			

		}

		var_dump($arregloBloque);

		
	}

	function escanearDirectorio($dir=''){
		
		$var = scandir($dir);
		unset($var[0]);
		unset($var[1]);

		return $var;
	}
}

$miRegistrador = new ConsultarBloques ( $this->sql );

$resultado = $miRegistrador->procesarConsultaBloque ();

?>