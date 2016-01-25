<? include("DatosConexion/confiConeccion.php");  ?>
<?php
 if($_GET["tipo"] == 0)
 {
	 //consultar los datos del tipo de analisis
	 $sql = "select descripcion,valor_unitario from tipo_analisis where id =".$_GET["analisisId"];
	 $result = mysql_query($sql);
     $row = mysql_fetch_array($result);
	 $respuesta = utf8_encode($row[0])."_<@>_".$row[1];
	 echo $respuesta;
 }
 else
 {
	 
 }
  
?>