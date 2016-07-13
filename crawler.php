<?php

/**
* web crawler 
* Autores: Daniel Reis e Lucas Viana
* 
* Fontes:
* http://subinsb.com/how-to-create-a-simple-web-crawler-in-php
*/

// require 'simple_html_dom.php';

class crawler {

	// trasforma urls relativas para absolutas
    function rel2abs($rel, $base) {
        if (parse_url($rel, PHP_URL_SCHEME) != '')
            return $rel;
        if ($rel[0] == '#' || $rel[0] == '?')
            return $base . $rel;
        extract(parse_url($base));
		
		if(!isset($path))
			return;
		
        $path = preg_replace('#/[^/]*$#', '', $path);
        if ($rel[0] == '/')
            $path = '';
        $abs = "$host$path/$rel";
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
            
        }
        $abs = str_replace("../", "", $abs);
        return $scheme . '://' . $abs;
    }
	
    function perfect_url($u, $b) {
        $bp = parse_url($b);
		if(isset($bp['path']))
			if (($bp['path'] != "/" && $bp['path'] != "") || $bp['path'] == '') {
				if ($bp['scheme'] == "") {
					$scheme = "http";
				} else {
					$scheme = $bp['scheme'];
				}
				$b = $scheme . "://" . $bp['host'] . "/";
			}
        if (substr($u, 0, 2) == "//") {
            $u = "http:" . $u;
        }
        if (substr($u, 0, 4) != "http") {
            $u = $this->rel2abs($u, $b);
        }
        return $u;
    }
	
	function getLinks($html)
	{
		preg_match_all('/<a\s+href=["\']([^"\']+)["\']/i',$html,$out);
		return array_unique($out[1]);
	}

	function gravar_documento($html,$dir,$doc)
	{
		$documento = fopen($dir."/".$doc,"w");
		fwrite($documento,$html); 
		fclose($documento);
		return; 
	}
	
	function search_link($link,$array)
	{
		foreach($array as $elemento) {
			if($elemento["url"] === $link)
				return $elemento["url"];
		}
		return false; 
	}
	
	// core do coletor 
    function crawl_site($url,$recomecar,$formato=null) {
		
		// sem tempo limite para execu??o do script 
		set_time_limit(0);
		
		$opts = array(
                'http' => array(
                    'method' => "GET",
                    'header' => "Accept-language: pt\r\n" .
                    "User-Agent: PucBot/1.0\r\n"
                )
        );

        $context = stream_context_create($opts);
		
		$document;
		$doc; 
		$m = new MongoClient(); 
		$db = $m->documentos;
		$urlsCol = $db->urls;
		$filaCol = $db->fila;
		
		$fila = array();
		$urls = array();
		
		// partir de uma nova url
		if($recomecar === "v") {
			$do = array("url"=>$url);
			$filaCol->insert($do);
			$fila[] = array("id"=>$do["_id"],"url"=>$url);
		}
		else { // continuar coleta de onde parou
			// recuperar urls da fila (banco de dados)
			$cursor = $filaCol->find();
			
			foreach($cursor as $do){
				$fila[] = array("id"=>$do["_id"],"url"=>$do["url"]);
				
				if(count($fila)>50000)
					break;
			}
		}
		
		// recuperar lista de urls inseridas no bd
		$cursor = $urlsCol->find();
		
		foreach($cursor as $doc){
			$urls[] = $doc["url"];
		}
		
		$total_urls = count($fila);
		
		$dir = "arquivos";
		$log = fopen("log.txt","w");
		
		while($total_urls > 0) {
			
			// selecionar primeira url da fila 
			$escolhido = array_shift($fila);
			$url = $escolhido["url"];
			$idfila = $escolhido["id"]; 
			$filaCol->remove(array("_id"=>$idfila));
			
			$inicio = microtime(true);
			// recupera conte?do da pagina 
			@$html = file_get_contents($url);
			
			if($html == false)
				continue;
			
			// grava a url no banco de dados e o codigo html em um arquivo 
			if(array_search($url,$urls)===false)
			{
				if(strpos($url,"https://pt.wikipedia.org/wiki/Especial:")===false && strpos($url,"https://pt.wikipedia.org/wiki/Categoria:")===false
					&& strpos($url,"https://pt.wikipedia.org/wiki/Portal:")===false && strpos($url,"https://pt.wikipedia.org/wiki/Anexo:")===false
					&& strpos($url,"https://pt.wikipedia.org/wiki/Ficheiro:")===false && strpos($url,'Usu%C3%A1rio')===false
					&& strpos($url,'Wikip%C3%A9dia:')===false && strpos($url,'Predefini%C3%A7%C3%A3o:')===false
					&& strpos($url,'Ajuda:')===false)
				{
					$document = array("url"=>$url);
					$urlsCol->insert($document);
					$doc = $document["_id"].".html";
					$this->gravar_documento($html,$dir,$doc);
					$urlsCol->update(array("_id"=>$document["_id"]),
										array('$set'=>array("dir"=>$dir,"doc"=>$doc)));
				}
				$urls[] = $url; 
			}
			else {
				continue; 
			}
			
			// recupera os links do documento 
			$links = $this->getLinks($html);
			
			foreach ($links as $li) {
				
				$li = $this->perfect_url($li, $url);
					
				if(filter_var($li, FILTER_VALIDATE_URL) ) 
				{ // inserir link na fila 
					if(is_null($formato))
					{
						$document = array("url"=>$li);
						$filaCol->insert($document); 
						
						if(count($fila)<30000)
							$fila[] = array("id"=>$document["_id"],"url"=>$li);
					}
					else if(strpos($li,$formato)!==false)
					{
						$document = array("url"=>$li);
						$filaCol->insert($document); 
						
						if(count($fila)<30000)
							$fila[] = array("id"=>$document["_id"],"url"=>$li);
					}
				}
			}
			
			$total_urls = count($fila);
			
			$tempo = microtime(true)-$inicio;
			fwrite($log,$tempo."\n");
			
			if($tempo<1.0)
			{
				$tempo = $tempo*1000000.0;
				$sleep = (int) 2000000.0-$tempo;
				usleep($sleep);
			}
		}
	}
}

