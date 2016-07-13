<!doctype html>
<html>

<head>
<meta charset="UTF-8">

<style>
.container {
	
	margin: auto;
	max-width: 400px; 
	height: auto; 
	background-color: aquamarine; 
}

.header {
	
	height: 80px; 
	padding: 2px;
	text-align: center; 
	color: white; 
	background-color: aqua; 
}

.form {
	
	padding: 20px; 
}

</style>

</head>
<body>

<div class="container">
	
	<div class="header">
		<h1>PUC BOT</h1>
	</div>

	<form class="form" action="coletor.php" method="GET">
	<table>
	<tr>
		<td>URL: </td>
		<td><input type="text" name="url" size="40"></td>
	</tr>
	<tr>
		<td>Formato: </td>
		<td><input type="text" name="formato" size="40"></td>
	</tr>
	<tr>
		<td></td>
		<td><input type="radio" name="recomecar" value="f">Continuar
		<input type="radio" name="recomecar" value="v" checked>Recomeçar</td>
	</tr>
	<tr>
	<td></td>
		<td><input type="submit" name="submit" value="Coletar">
		</td>
	</tr>
	</table>
	</form>
</div>

<?php

require 'crawler.php';

	if(isset($_GET["submit"]))
	{
		$crawler = new crawler();
			
		if(!empty($_GET["formato"]))
		{
			
			$formato = $_GET["formato"];
			$crawler->crawl_site($_GET["url"],$_GET["recomecar"],$formato);
		}
			
		$crawler->crawl_site($_GET["url"],$_GET["recomecar"]);
	}
?>
</body>
</html>