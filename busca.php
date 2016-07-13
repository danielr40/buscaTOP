<?php

require 'bm25.php';

function stripAccents($str) {
    return strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
}

$bm25 = new rankingBM25();
$q = $_GET['busca'];
$q = strtolower($q);
$q = stripAccents($q);

$q = explode(" ",$q);

for($i=0;$i<count($q);$i++){
	$q[$i]=stem_portuguese($q[$i]);
}

$q = implode(" ",$q);

$resultado = $bm25->ranking($q);
arsort($resultado);

$m = new MongoClient(); 
$db = $m->documentos; 
$urls = $db->urls; 
$elemento = array(); 
$resposta = array(); 
$anterior="";

if(empty($resultado)===false){
	foreach($resultado as $id=>$ranking){
		$pagina = $urls->findOne(array("_id"=>new MongoId($id)));
		
		if($pagina["titulo"] === $anterior){
			continue;
		}
		else{
			$anterior = $pagina["titulo"];
		}
		
		$elemento["url"] = $pagina["url"];
		$elemento["titulo"] = $pagina["titulo"];
		$elemento["descricao"] = $pagina["descricao"];
		$elemento["ranking"] = $ranking; 
		
		$resposta[] = $elemento;
	}
	echo json_encode($resposta);
}
else {
	echo json_encode(array());
}

?>