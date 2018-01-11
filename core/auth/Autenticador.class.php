<?php

/**
 * Autenticador.class.php
 *
 * Encargado de gestionar las sesiones de usuario.
 *
 * @author  Paulo Cesar Coronado
 * @version     1.1.0.1
 * @package     Kixi
 * @copyright   Universidad Distrital Francisco Jose de Caldas - Grupo de Trabajo Academico GNU/Linux GLUD
 * @license     GPL Version 3 o posterior
 *
 */
class Autenticador {
	private static $instancia;
	
	/**
	 * Arreglo que contiene los datos de la página que se va revisar
	 *
	 * @var String[]
	 */
	var $pagina;
	var $webService;
	
	/**
	 * Objeto.
	 * Con los atributos y métodos para gestionar la sesión de usuario
	 *
	 * @var Sesion
	 */
	var $sesionUsuario;
	var $tipoError;
	var $configurador;
	const NIVEL = 'nivel';
	private function __construct() {
		$this->configurador = Configurador::singleton ();
		
		require_once ($this->configurador->getVariableConfiguracion ( "raizDocumento" ) . "/core/auth/Sesion.class.php");
		$this->sesionUsuario = Sesion::singleton ();
		$this->sesionUsuario->setSesionUsuario ( $this->configurador->fabricaConexiones->miLenguaje->getCadena ( "usuarioAnonimo" ) );
		$this->sesionUsuario->setConexion ( $this->configurador->fabricaConexiones->getRecursoDB ( "configuracion" ) );
		$this->sesionUsuario->setTiempoExpiracion ( $this->configurador->getVariableConfiguracion ( "expiracion" ) );
		$this->sesionUsuario->setPrefijoTablas ( $this->configurador->getVariableConfiguracion ( "prefijo" ) );
	}
	public static function singleton() {
		if (! isset ( self::$instancia )) {
			$className = __CLASS__;
			self::$instancia = new $className ();
		}
		return self::$instancia;
	}
	function iniciarAutenticacion() {
		if (isset ( $this->pagina ["nombre"] )) {
			$resultado = $this->verificarExistenciaPagina ();
			
			if ($resultado) {
				
				$resultado = $this->cargarSesionUsuario ();
				
				if ($resultado) {
					// Verificar que el usuario está autorizado para el nivel de acceso de la página
					
					$resultado = $this->verificarAutorizacionUsuario ();
					if ($resultado) {
						$respuesta = true;
					} else {
						$this->tipoError = "usuarioNoAutorizado";
						$respuesta = false;
					}
				} else {
					$this->tipoError = "sesionNoExiste";
					$respuesta = false;
				}
			} else {
				
				$this->tipoError = "paginaNoExiste";
				$respuesta = false;
			}
		} else if (isset ( $this->webService ["nombre"] )) {
			
			/**
			 * Verificacion Existencia Web Services
			 */
			
			$resultado = $this->verificarExistenciaWebServices ();
			if ($resultado) {
				
				$resultado = $this->cargarSesionUsuario ();
				$respuesta = true;
			}else{
				$respuesta = false;
			}
		} else {
			$this->tipoError = "webServiceNoExiste";
			$respuesta = false;
		}
		
		return $respuesta;
	}
	function setPagina($pagina) {
		$this->pagina ["nombre"] = $pagina;
	}
	private function verificarExistenciaPagina() {
		$clausulaSQL = $this->sesionUsuario->miSql->getCadenaSql ( "seleccionarPagina", $this->pagina ["nombre"] );
		
		if ($clausulaSQL) {
			$registro = $this->configurador->conexionDB->ejecutarAcceso ( $clausulaSQL, "busqueda" );
			$totalRegistros = $this->configurador->conexionDB->getConteo ();
			
			if ($totalRegistros > 0) {
				$this->pagina [self::NIVEL] = $registro [0] [0];
				return true;
			}
		}
		$this->tipoError = "paginaNoExiste";
		return false;
	}
	
	/*
	 * Función Para Asignación de nombre Servicio Web
	 */
	function setWebService($service) {
		$this->webService ["nombre"] = $service;
	}
	
	/*
	 * Función para Verificar Existencia Web Services
	 */
	private function verificarExistenciaWebServices() {
		$clausulaSQL = $this->sesionUsuario->miSql->getCadenaSql ( "seleccionarWebServices", $this->webService ["nombre"] );
		
		if ($clausulaSQL) {
			$registro = $this->configurador->conexionDB->ejecutarAcceso ( $clausulaSQL, "busqueda" );
			
			$totalRegistros = $this->configurador->conexionDB->getConteo ();
			
			if ($totalRegistros > 0) {
				
				/**
				 * Cargar el grupo del Servicio Web
				 */
				if (is_null ( $registro [0] ['grupo'] ) == false) {
					$_REQUEST ['grupo'] = trim ( $registro [0] ['grupo'] );
					$respuesta = true;
				}

				/**
				 * Cargar el tipo de Servicio Web
				 */
				if (trim ($registro [0] ['tipo']) == 'soap' || trim ($registro [0] ['tipo']) == 'rest') {
					$_REQUEST ['tipo'] = trim ( $registro [0] ['tipo'] );
					$respuesta = true;
				}else{
					$this->tipoError ="webServiceNoTipo";
					$respuesta = false;
				}
			}else{
				$this->tipoError = "webServiceNoExiste";
				$respuesta = false;
			}
		}else{
			$this->tipoError = "webServiceNoExiste";
			$respuesta = false;
		}

		return $respuesta;
	}


	function getError() {
		return $this->tipoError;
	}
	
	/**
	 * Método.
	 *
	 * @return boolean
	 */
	function cargarSesionUsuario() {
		
		// Asignar el nivel de la sesión conforme al nivel de la página que se está visitando
		$this->sesionUsuario->setSesionNivel ( $this->pagina [self::NIVEL] );
		
		$verificar = $this->sesionUsuario->verificarSesion ();
		
		if (! $verificar) {
			$this->tipoError = "sesionNoExiste";
			return false;
		}
		
		return true;
	}
	function verificarAutorizacionUsuario() {
		if ($this->sesionUsuario->getSesionNivel () == $this->pagina [self::NIVEL]) {
			return true;
		}
		
		return false;
	}
}
?>
