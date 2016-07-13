<?php 

// Autores: Daniel Reis e Lucas Viana 
// Ranqueamento baseado no modelo BM25 

class rankingBM25 {

	function __construct($k1=1,$b=0.25) {
		$this->k1 = $k1;
		$this->b = $b; 
		
		$this->m = new MongoClient(); 
		$db = $this->m->documentos; 
		$urls = $db->urls;
		$docs = $urls->find(array('indexado'=>True),array('length'=>1));
		$this->len = array();
		
		foreach($docs as $doc) {
			$this->len[(string)$doc['_id']] = $doc['length'];
		}
	}
	
	function beta($f,$docLen) {
		$numerador = ($this->k1+1)*$f;
		$denominador = $this->k1*((1-$this->b)+$this->b*($docLen/400.0))+$f;
		return $numerador / $denominador; 
	}
	
	function ranking($q) {
		
		$db = $this->m->documentos;
		$indice = $db->indice; 
		$urls = $db->urls;
		$urls_sim = array(); 
		
		$N = $urls->find(array("indexado"=>true))->count(); 
		$q = explode(" ",$q);
		
		foreach($q as $ti) {
			
			$cursor = $indice->findOne(array("termo"=>$ti));
			$ni = $cursor["freq"];
			$ocorrencias = $cursor["ocorrencias"];
			
			if(empty($ocorrencias)===false){
				foreach($ocorrencias as $doc) {
					if (isset($doc["freq"])===false)
					{
						continue; 
					}
					$f = $doc["freq"];
					$id = (string) $doc["doc"];
					if(!isset($this->len[$id])){
						$this->len=400.0;
					}
					$sim = $this->beta($f,$this->len[$id])*log(($N-$ni+0.5)/($ni+0.5),2.0);
					if(array_key_exists($id,$urls_sim)){
						$urls_sim[$id] += $sim; 
					}
					else {
						$urls_sim[$id] = $sim; 
					}
				}
			}
		}
		
		return $urls_sim; 
	}
}

?>