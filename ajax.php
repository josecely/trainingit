<?php
/* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Library General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*  Copyright  2007  TECSUA LTDA. Jose Antonio Cely Saidiza
*  Email jose.cely@tecsua.com
*  Bogotá Colombia
****************************************************************************/

    require_once "configs.php";

if(isset($_GET['pais'])){

	$conDpto = $db->get_results("SELECT id, nombre FROM division WHERE pais = '".$_GET['pais']."' ORDER BY nombre");
	echo "obj.options[obj.options.length] = new Option('...','');\n";	
	foreach ( $conDpto as $cgDpto )
	{
	         echo "obj.options[obj.options.length] = new Option('".$cgDpto->nombre."','".$cgDpto->id."');\n";
	}
	
}

if(isset($_GET['Transportador'])){
	$Max = $db->get_var("SELECT Actual + 1  FROM Consecutivo WHERE idActor = '".$_GET['Transportador']."'");
	echo $Max;
}

if(isset($_GET['salevehiculo'])){
	$db->query("UPDATE Vehiculos SET Fecha_sale = '".$ahora."' WHERE id = '".$_GET['salevehiculo']."'");
}

if(isset($_GET['pick'])){
	
        $single=true;
		$globalvar=$sesion->get_var('globalvar');
		if ($_GET[Codigobarra] == '') {
			if ($_GET[Referencia] != '') {
                $sqlref="SELECT Codigobarra FROM Producto WHERE Referencia = '".$_GET[Referencia] ."' AND Linea IN ( SELECT id FROM Linea WHERE Actor = '".$globalvar."')";
				$barra=$db->get_var($sqlref);
                if (($db->get_var("SELECT COUNT(*) as Qty FROM Producto WHERE Referencia = '".$_GET[Referencia] ."' AND Linea IN ( SELECT id FROM Linea WHERE Actor = '".$globalvar."'")) > 1 ) {
                    $single=false;
                }
			} else if ($_GET[Nombre] != '') {
                $sqlref="SELECT Codigobarra FROM Producto WHERE Nombre = '".$_GET[Nombre] ."' AND Linea IN ( SELECT id FROM Linea WHERE Actor = '".$globalvar."')";
				$barra=$db->get_var($sqlref);
                if (($db->get_var("SELECT COUNT(*) as Qty FROM Producto WHERE  Nombre = '".$_GET[Nombre] ."' AND Linea IN ( SELECT id FROM Linea WHERE Actor = '".$globalvar."'")) > 1 ) {
                    $single=false;
                }                
			} 											
		} else {
			$barra=$_GET[Codigobarra];
		}
        //Ejemplo aaaa
		$infodelprod=$db->get_row("SELECT Producto.id, Linea.Actor FROM Producto LEFT JOIN Linea ON Producto.Linea = Linea.id WHERE Codigobarra = '".$barra."'");
        $idprod=$infodelprod->id;
        
        if ($idprod!='' AND $single) {
					
				$NroFactura=$_GET[NroFactura];
				// filtro de inventario visible
				if ($sesion->IsRoot()) {
					$visualizacion=" Existencia.Grupo = '".$sesion->get_var('empresa')."'";
				} else {
					$PermsEstado=$sesion->get_var('PermsEstado');
					$PermsUso=$sesion->get_var('PermsUso');
					$PermsSedes=$sesion->get_var('PermsSedes');
					$stingestados='';
					$stingusos='';
					foreach($PermsEstado AS $dataa) {
						if ($stingestados=='') {
							$stingestados.=" Existencia.Estado = '".$dataa."'";
						} else {
							$stingestados.=" OR Existencia.Estado = '".$dataa."'";
						}
					}
					foreach($PermsUso AS $dataa) {
						if ($stingusos=='') {
							$stingusos.=" Existencia.Uso = '".$dataa."'";
						} else {
							$stingusos.=" OR Existencia.Uso = '".$dataa."'";
						}									
					}									
					$visualizacion=" Existencia.Grupo = '".$sesion->get_var('empresa')."' AND (".$stingestados.") AND (".$stingusos.")";
					if($PermsSedes) {
						foreach($PermsSedes as $values) { // filtro para sedes
							foreach($values as $val) {
								if ($stinsedes=='') {
									$stinsedes=" Existencia.Sede = '".$val."'";
								} else {
									$stinsedes.=" OR Existencia.Sede = '".$val."'";
								}
							}
						}
						$stinsedes=" AND (".$stinsedes.")";
						$visualizacion.=$stinsedes;
					}
				}
				if ($NroFactura != '') { // para numero de factura
					$visualizacion.=" AND Existencia.NroFactura = '".$NroFactura."' ";
				}
			
                if ($_GET['Ubicacion']=='') { // si va a sugerir una ubicacion
						$tipofifo=$db->get_row("SELECT Tipo, Serial FROM TipoFifo WHERE Actor = '".$infodelprod->Actor."' AND Tipo > 1 AND Serial > 0");
						if ($tipofifo->Tipo == 2) { // fifo
							$elorder="ORDER BY Existencia.FechaEntrada ASC";
						} else if ($tipofifo->Tipo == 3) { // lifo
							$elorder="ORDER BY Existencia.FechaEntrada DESC";
						} else if ($tipofifo->Tipo == 4) { // fefo
							$elorder="ORDER BY Existencia.Serial".$tipofifo->Serial." ASC";
						} else {
							$elorder="";
						}				
						$sqldebi="SELECT SUM(Qty - Seguro) AS Qty FROM Existencia WHERE Uso = '".$_GET['Uso']."' AND Estado = '".$_GET['Estado']."' AND id_producto = '$idprod' AND Existe = '1'  AND ".$visualizacion." ";
                        $ubicacionesa = $db->get_var($sqldebi);
                        
                        echo "<table class=\"tableforms\"><tr class=\"tablemakeheader\"><td>Qty</td><td>Ubic.</td><td>NUI</td><td>Serial1</td><td>Serial2</td><td>Serial3</td><td>Serial4</td><td>Sede</td></tr>\n";
                        if ($ubicacionesa >= $_GET['Cantidad']) {
								$sqlubi="SELECT (Qty - Seguro) AS Qty, Qty AS Qty2, Ubicacion, Seguro, Existencia.id AS NUI, Serial1, Serial2, Serial3, Serial4, Sedes_cliente.Nombre_sede  FROM Existencia LEFT JOIN Sedes_cliente ON Existencia.Sede = Sedes_cliente.id WHERE Uso = '".$_GET['Uso']."' AND Estado = '".$_GET['Estado']."' AND id_producto = '$idprod'  AND Existe = '1' AND ".$visualizacion."  ".$elorder."";
                                if ($ubicaciones = $db->get_results($sqlubi)) {
                                        $noalcanzo=TRUE;
                                        foreach ( $ubicaciones as $ubicacion )
                                        {
                                                if ($ubicacion->Qty < $_GET['Cantidad']) { //si no alcanza en la ubicacion
                                                        echo "<tr class=\"tablemakerow\"><td><font size=\"-2\" color=\"RED\"><B>$ubicacion->Qty2</B></td><td><a href=\"#\" onClick=\"Addposition('$ubicacion->Ubicacion'); return false;\">$ubicacion->Ubicacion</a></td><td>$ubicacion->NUI</td><td>$ubicacion->Serial1</td><td>$ubicacion->Serial2</td><td>$ubicacion->Serial3</td><td> $ubicacion->Serial4 insuficiente</td><td> $ubicacion->Nombre_sede </font></td></tr>\n";
                                                } else if ($ubicacion->Qty >= $_GET['Cantidad'] AND $ubicacion->Qty2 > $_GET['Cantidad'] AND $ubicacion->Seguro > 0) { // si alzana y hay bloqueadas
                                                        echo "<tr class=\"tablemakerow\"><td><font size=\"-2\" color=\"GREEN\"><B>$ubicacion->Qty2</B> </td><td><a href=\"#\" onClick=\"Addposition('$ubicacion->Ubicacion'); return false;\">$ubicacion->Ubicacion</a><br><font size=\"-2\" color=\"RED\">$ubicacion->Seguro Bloqueadas</font></td><td><a href=\"#\" onClick=\"AddNUI('$ubicacion->NUI'); return false;\">$ubicacion->NUI</a></td><td>$ubicacion->Serial1</td><td>$ubicacion->Serial2</td><td>$ubicacion->Serial3</td><td>$ubicacion->Serial4 </td><td> $ubicacion->Nombre_sede </font></td></tr>\n";
                                                        $noalcanzo=FALSE;
                                                } else if ($ubicacion->Qty >= $_GET['Cantidad']){
                                                        $noalcanzo=FALSE;
                                                        echo "<tr class=\"tablemakerow\"><td><font size=\"-2\" color=\"GREEN\"><B>$ubicacion->Qty</B></td><td><a href=\"#\" onClick=\"Addposition('$ubicacion->Ubicacion'); return false;\">$ubicacion->Ubicacion </a></td><td><a href=\"#\" onClick=\"AddNUI('$ubicacion->NUI'); return false;\">$ubicacion->NUI</a></td><td>$ubicacion->Serial1</td><td>$ubicacion->Serial2</td><td>$ubicacion->Serial3</td><td>$ubicacion->Serial4</td><td> $ubicacion->Nombre_sede </font></td></tr>\n";                                                }
                                        }
                                        if($noalcanzo){
                                                echo "<tr class=\"tablemakerow\"><td colspan=8><font size=\"-1\" color=\"ORANGE\"><B>Advertencia</B>- Ninguna Ubicación con cantidad suficiente<br></font></td></tr>\n";
                                                foreach ( $ubicaciones as $ubicacion )
                                                {
                                                        if ($ubicacion->Seguro==1) {
                                                                echo "<tr class=\"tablemakerow\"><td><font size=\"-2\" color=\"RED\"><B>$ubicacion->Qty</B></font></td><td  colspan=7>$ubicacion->Ubicacion - Bloqueada</td></tr>\n";
                                                        } else {
                                                                echo "<tr class=\"tablemakerow\"><td><font size=\"-2\" color=\"GREEN\"><B>$ubicacion->Qty</B></td><td><a href=\"#\" onClick=\"Addposition('$ubicacion->Ubicacion'); return false;\">$ubicacion->Ubicacion </a></td><td>$ubicacion->NUI</td><td>$ubicacion->Serial1</td><td>$ubicacion->Serial2</td><td>$ubicacion->Serial3</td><td>$ubicacion->Serial4</td><td>$ubicacion->Nombre_sede</font></td></tr>\n";
                                               
                                                        }
                                                }                                         
                                        }
                                }
                        } else {
                               echo "<tr class=\"tablemakerow\"><td colspan=8><font size=\"+1\" color=\"RED\">No existe la cantidad sufciente, Cantidad actual = <B>$ubicacionesa</B><br></font></td></tr>\n"; 
                        }
						echo "</table>";
                } else { // si va a validar una ubicacion
                        $totales=0;
                        $ubicaciones=explode(',', $_GET['Ubicacion']);
                        foreach ($ubicaciones AS $ubicacion) {
                                $qtynow = $db->get_row("SELECT SUM(Qty - Seguro) AS Qty, Seguro, id FROM Existencia WHERE Uso = '".$_GET['Uso']."' AND Estado = '".$_GET['Estado']."' AND id_producto = '$idprod' AND Ubicacion = '$ubicacion' AND Existe = '1' AND ".$visualizacion." GROUP BY Seguro");
								 if ($qtynow->Qty == 0 AND $qtynow->id != '' ) {
                                       echo "<font size=\"-2\" color=\"RED\"><B>$ubicacion</B> - Sin cantidad suficiente<br></font>\n"; 
                                } else if ($qtynow->Qty == 0) {
                                       echo "<font size=\"-2\" color=\"RED\"><B>$ubicacion</B> - No existe o sin partes asociadas<br></font>\n"; 
                                } else {
                                       $totales=$totales+$qtynow->Qty;
                                }
                        }
                        if ($totales<$_GET['Cantidad']) {
                               echo "<font size=\"-2\" color=\"RED\"><B>Error</B> No hay cantidad suficiente<br></font>\n"; 
                        } else if ($totales==$_GET['Cantidad']){
                               echo "<font size=\"-2\" color=\"GREEN\"><B>OK</B> Cantidad exacta<br></font>\n"; 
                        } else if ($totales>$_GET['Cantidad']) { 
                               $totales=$totales-$_GET['Cantidad'];
                               echo "<font size=\"-2\" color=\"GREEN\"><B>OK - No se usan $totales</B><br></font>\n";
                        }
                }
        } else if (!$single){
                echo "<font size=\"-2\" color=\"ORANGE\"><B>Existen mas de un producto con esa Referncia o Nombre</B></font><br>\n";
        } else {
                echo "<font size=\"-2\" color=\"ORANGE\"><B>Sin existencias de ".$_GET[Codigobarra]."<br>con las opciones seleccionadas</B></font><br>\n";
        } 
        if ($_GET['Destino'] != '') {
                if ($destino = $db->get_row("SELECT Nombre1, Nombre2, Direccion FROM Actor WHERE id = '".$_GET['Destino']."' AND Estado = '1'")) {
                        echo "<font size=\"-2\" color=\"GREEN\"><B>*</B> $destino->Nombre1 $destino->Nombre2 $destino->Direccion<br></font>\n"; 
                } else {
                        echo "<font size=\"-2\" color=\"RED\"><B>*Error</B> No existe un destino valido con ese Codigo<br></font>\n"; 
                }
        }
		exit;
} 
	
if(isset($_GET['division'])){

	$conDpto = $db->get_results("SELECT id, nombre FROM ciudad WHERE division = '".$_GET['division']."' ORDER BY nombre");
	foreach ( $conDpto as $cgDpto )
	{
	
	         echo "obj.options[obj.options.length] = new Option('".$cgDpto->nombre."','".$cgDpto->id."');\n";
	}
	
}
	
if(isset($_GET['selecsedes'])){
	$id = $_GET['selecsedes'];

	$sql="SELECT  Sedes_cliente.id, Nombre_sede, ciudad.nombre FROM `Sedes_cliente` LEFT JOIN ciudad ON Sedes_cliente.Ciudad = ciudad.id  WHERE Actor = '".$id."'";


	 if ($sesion->IsRoot()) {
		$ignoresedes=TRUE;
	} else {
		$ignoresedes=FALSE;
	}

	$sedes = array();
	
	$PermsSedes=$sesion->get_var('PermsSedes');
	
	if($PermsSedes) {
		foreach($PermsSedes as $values) { // filtro para sedes
			foreach($values as $val) {
				if ($stinsedes=='') {
					$sedes[]=$val;
				} else {
					$sedes[]=$val;
				}
			}
		}
	}	

	if ($inforegistro= $db->get_results($sql)) {
		echo "obj.options[obj.options.length] = new Option('-','');\n";

		foreach( $inforegistro as $inforeg )
		{
			if ((in_array($inforeg->id, $sedes)) OR $ignoresedes) {
				echo "obj.options[obj.options.length] = new Option('".$inforeg->Nombre_sede." - ".$inforeg->nombre."','".$inforeg->id."');\n";			
			}
		}
	} else {
	         echo "obj.options[obj.options.length] = new Option('Sin sedes','');\n";			
	}
	
}

if(isset($_GET['getCodigobarra']) && isset($_GET['letters'])){  // para el dynamic list
	$globalvar=$sesion->get_var('globalvar');
	$letters = $_GET['letters'];
	$letters = preg_replace("/[^a-z0-9 ]/si","",$letters);
	//id valor en el select
	$conNombre = $db->get_results("SELECT Codigobarra, Codigobarra AS Codigobarra2 FROM `Producto` WHERE `Codigobarra`  like '".$letters."%'  AND Linea IN ( SELECT id FROM Linea WHERE Actor = '".$globalvar."') ");
		
	#echo "1###select ID,countryName from ajax_countries where countryName like '".$letters."%'|";
	foreach ( $conNombre as $cNombre )
	{
		echo "".$cNombre->Codigobarra.""."###"."".$cNombre->Codigobarra2.""."|";
	}	
	
}

if(isset($_GET['getNombre']) && isset($_GET['letters'])){  // para el dynamic list
	$globalvar=$sesion->get_var('globalvar');
	$letters = $_GET['letters'];
	$letters = preg_replace("/[^a-z0-9 ]/si","",$letters);
	//id valor en el select
	$conNombre = $db->get_results("SELECT Nombre, Nombre AS Nombre2 FROM `Producto` WHERE `Nombre`  like '".$letters."%'  AND Linea IN ( SELECT id FROM Linea WHERE Actor = '".$globalvar."') ");
		
	#echo "1###select ID,countryName from ajax_countries where countryName like '".$letters."%'|";
	foreach ( $conNombre as $cNombre )
	{
		echo "".$cNombre->Nombre.""."###"."".$cNombre->Nombre2.""."|";
	}	
	
}

if(isset($_GET['getReferencia']) && isset($_GET['letters'])){  // para el dynamic list
	$globalvar=$sesion->get_var('globalvar');
	$letters = $_GET['letters'];
	$letters = preg_replace("/[^a-z0-9 ]/si","",$letters);
	//id valor en el select
	$conNombre = $db->get_results("SELECT Referencia, Referencia AS Referencia2 FROM `Producto` WHERE `Referencia`  like '".$letters."%'  AND Linea IN ( SELECT id FROM Linea WHERE Actor = '".$globalvar."') ");
		
	#echo "1###select ID,countryName from ajax_countries where countryName like '".$letters."%'|";
	foreach ( $conNombre as $cNombre )
	{
		echo "".$cNombre->Referencia.""."###"."".$cNombre->Referencia2.""."|";
	}	
	
}

// para el Ajax de permisos de usuario	
if(isset($_GET['userpermsform'])){

    // para cargar el formulario con los permisos que tenga el usuario
    $sql = "SELECT menu1, menu2, menu3, menu4, menu5, menu6, menu7, menu8, menu9 FROM User_perms WHERE id = '".$_GET['userpermsform']."'";
    $perm=$db->get_row($sql);
    
    // guarda info de root
    if($db->get_var("SELECT TipoActor FROM Actor WHERE id = '".$_GET['userpermsform']."' AND TipoActor like '%-0-%'")){
        $check="checked";
    } else {
        $check="";
    }
    
    $html="<form action=\"";
    $html.="$nada";
    $html.="\"  method=\"POST\">";    
    $html.= "<table border=\"0\" align=\"center\">";
    $html.="<tr class=\"tablemakeheader\"><td colspan=\"2\"> No <input type=\"radio\" name=\"x1\" checked> User <input type=\"radio\" name=\"x2\" checked> Admin <input type=\"radio\" name=\"x3\" checked></td></td></tr>";    
    
    if($numeromenus > 0) {
        if ($perm->menu1 == 0) { $checked1='checked';$checked2='';$checked3='';} else if ($perm->menu1 == 1) { $checked1='';$checked2='checked';$checked3='';} else { $checked1='';$checked2='';$checked3='checked';}
        $html.="<tr class=\"tablemakeheader\"><td> "._MENU1."</td><td><input type=\"radio\" name=\"maenu1\" value=\"0\" $checked1> <input type=\"radio\" name=\"maenu1\" value=\"1\" $checked2> <input type=\"radio\" name=\"maenu1\" value=\"2\" $checked3></td></tr>";
    }

    if($numeromenus > 1) {
        if ($perm->menu2 == 0) { $checked1='checked';$checked2='';$checked3='';} else if ($perm->menu2 == 1) { $checked1='';$checked2='checked';$checked3='';} else { $checked1='';$checked2='';$checked3='checked';}
        $html.="<tr class=\"tablemakeheader\"><td> "._MENU2."</td><td><input type=\"radio\" name=\"maenu2\" value=\"0\" $checked1> <input type=\"radio\" name=\"maenu2\" value=\"1\" $checked2> <input type=\"radio\" name=\"maenu2\" value=\"2\" $checked3></td></tr>";
    }
 
    if($numeromenus > 2) {
        if ($perm->menu3 == 0) { $checked1='checked';$checked2='';$checked3='';} else if ($perm->menu3 == 1) { $checked1='';$checked2='checked';$checked3='';} else { $checked1='';$checked2='';$checked3='checked';}
        $html.="<tr class=\"tablemakeheader\"><td> "._MENU3."</td><td><input type=\"radio\" name=\"maenu3\" value=\"0\" $checked1> <input type=\"radio\" name=\"maenu3\" value=\"1\" $checked2> <input type=\"radio\" name=\"maenu3\" value=\"2\" $checked3></td></tr>";
    }
 
    if($numeromenus > 3) {
        if ($perm->menu4 == 0) { $checked1='checked';$checked2='';$checked3='';} else if ($perm->menu4 == 1) { $checked1='';$checked2='checked';$checked3='';} else { $checked1='';$checked2='';$checked3='checked';}
        $html.="<tr class=\"tablemakeheader\"><td> "._MENU4."</td><td><input type=\"radio\" name=\"maenu4\" value=\"0\" $checked1> <input type=\"radio\" name=\"maenu4\" value=\"1\" $checked2> <input type=\"radio\" name=\"maenu4\" value=\"2\" $checked3></td></tr>";
    }
 
    if($numeromenus > 4) {
        if ($perm->menu5 == 0) { $checked1='checked';$checked2='';$checked3='';} else if ($perm->menu5 == 1) { $checked1='';$checked2='checked';$checked3='';} else { $checked1='';$checked2='';$checked3='checked';}
        $html.="<tr class=\"tablemakeheader\"><td> "._MENU5."</td><td><input type=\"radio\" name=\"maenu5\" value=\"0\" $checked1> <input type=\"radio\" name=\"maenu5\" value=\"1\" $checked2> <input type=\"radio\" name=\"maenu5\" value=\"2\" $checked3></td></tr>";
    }
 
    if($numeromenus > 5) {
        if ($perm->menu6 == 0) { $checked1='checked';$checked2='';$checked3='';} else if ($perm->menu6 == 1) { $checked1='';$checked2='checked';$checked3='';} else { $checked1='';$checked2='';$checked3='checked';}
        $html.="<tr class=\"tablemakeheader\"><td> "._MENU6."</td><td><input type=\"radio\" name=\"maenu6\" value=\"0\" $checked1> <input type=\"radio\" name=\"maenu6\" value=\"1\" $checked2> <input type=\"radio\" name=\"maenu6\" value=\"2\" $checked3></td></tr>";
    }
 
    if($numeromenus > 6) {
        if ($perm->menu7 == 0) { $checked1='checked';$checked2='';$checked3='';} else if ($perm->menu7 == 1) { $checked1='';$checked2='checked';$checked3='';} else { $checked1='';$checked2='';$checked3='checked';}
        $html.="<tr class=\"tablemakeheader\"><td> "._MENU7."</td><td><input type=\"radio\" name=\"maenu7\" value=\"0\" $checked1> <input type=\"radio\" name=\"maenu7\" value=\"1\" $checked2> <input type=\"radio\" name=\"maenu7\" value=\"2\" $checked3></td></tr>";
    }
 
    if($numeromenus > 7) {
        if ($perm->menu8 == 0) { $checked1='checked';$checked2='';$checked3='';} else if ($perm->menu8 == 1) { $checked1='';$checked2='checked';$checked3='';} else { $checked1='';$checked2='';$checked3='checked';}
        $html.="<tr class=\"tablemakeheader\"><td> "._MENU8."</td><td><input type=\"radio\" name=\"maenu8\" value=\"0\" $checked1> <input type=\"radio\" name=\"maenu8\" value=\"1\" $checked2> <input type=\"radio\" name=\"maenu8\" value=\"2\" $checked3></td></tr>";
    }
 
    if($numeromenus > 8) {
        if ($perm->menu9 == 0) { $checked1='checked';$checked2='';$checked3='';} else if ($perm->menu9 == 1) { $checked1='';$checked2='checked';$checked3='';} else { $checked1='';$checked2='';$checked3='checked';}
        $html.="<tr class=\"tablemakeheader\"><td> "._MENU9."</td><td><input type=\"radio\" name=\"maenu9\" value=\"0\" $checked1> <input type=\"radio\" name=\"maenu9\" value=\"1\" $checked2> <input type=\"radio\" name=\"maenu9\" value=\"2\" $checked3></td></tr>";
    }
    
    $html.="</table>\n";
    $html.="<input type=\"hidden\" name=\"id_personal\" value=\"".$_GET['userpermsform']."\">";
    $html.="<div align=\"center\">  <input type=\"checkbox\" value=\"1\" name=\"root\" ".$check."> <font color=\"red\">root</font> &nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" class=\"tablemakebutton\" name=\"editarperms\" value=\""._EDIT."\"></div>";
    $html.="</form>";    
    echo $html;
}

?>
