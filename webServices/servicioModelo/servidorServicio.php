<?php

namespace servicioBeneficiario;
include ('Funcion.class.php');

$directorioWSDL = "http://localhost/hipnos/webServices/directorioWDSL/archivoWSDL.wsdl";
ini_set ( "soap.wsdl_cache_enabled", "0" );

$parametros = array (
		'uri' => 'http://localhost/hipnos/webServices/servicioBeneficiario/',
		'soap_version' => SOAP_1_2 
);

$objetoServicio = new \SoapServer($directorioWSDL,$parametros);


$objetoServicio->setClass ( "Beneficiario" );
$objetoServicio->handle ();

?>