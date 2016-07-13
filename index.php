<!doctype html>

<html>

<head>

<meta charset="UTF-8">
 
<title>buscaTOP</title>

<link rel="stylesheet" type="text/css" href="css/principal.css">

<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>

</head> 

<body>

<div id="wrapper">

<div class="topbar">

<div class="topbar-container">

<div class="pesquisa-container">

<input id="pesquisa" type="text" placeholder="pesquisar..."></input>
<input id="pesquisar" type="button"></input>

</div> 

</div>

</div>

<div id="container">

<div id="section-resultados">

</div>

<script>

$(document).ready(function(){
	$("#pesquisar").click(function(){
		$.get("busca.php",{busca:$("#pesquisa").val()},function(data){
				res = data
				mostrarResultados(data,1);
		});
	});
});

function mostrarResultados(resultado,pagina){
	resultado = JSON.parse(resultado);
	$("#section-resultados").empty();
	
	if(resultado.length === 0){
		$("#section-resultados").append("<h3>Sua pesquisa não retornou nenhum resultado!</h3>");
	}
	
	for(var i=(pagina-1)*10;i<resultado.length && i<10*pagina;i++){
		txt = "";
		txt+="<div class='resultado'>";
		txt+="<a href='"+resultado[i]['url']+"'>";
		txt+="<h3><u>"+resultado[i]['titulo']+"</u></h3>";
		txt+="</a>";
		txt+="<span class='ranking'>"+resultado[i]['ranking']+"</span>";
		txt+="<p>"+resultado[i]['descricao']+"</p>";
		txt+="</div>";
		txt+="<hr>";
		$("#section-resultados").append(txt);
	}
	
	criarPaginacao(resultado,pagina);
}

function criarPaginacao(resultado,selecionado){
	var txt="";
	txt+="<ul class='pagination'>"
	
	for(var i=1;i<resultado.length/10 && i<=10;i++){
		if(i===selecionado){
			txt+="<li><a href='#' onclick='mostrarResultados(res,"+i+")' class='pagination-current'>"+i+"</a><li>"
		}
		else{
			txt+="<li><a href='#' onclick='mostrarResultados(res,"+i+")'>"+i+"</a></li>"
		}
	}
	
	txt+="</ul>";
	
	$("#section-resultados").append(txt);
}

</script>
</div>

<footer>
<p>Daniel Reis e Lucas Viana @2016</p>
</footer>

</div>

</body>

</html>