<?php

/*
    Indexador de servidores FTP
    por Rafael Rodriguez Ramirez e Irving Leonard Perez de Alejo
    2007-2008-2009
*/

include "buscador.conf";
echo '----------------------------------------------------------------';
echo 'Indexador de servidores FTP'."\n";
echo 'por Rafael Rodriguez Ramirez e Irving Leonard Perez de Alejo'."\n";
echo '2007-2008-2009'."\n";
echo '----------------------------------------------------------------'."\n";

function listar($conn,$from,$db,$ftp_server,$level){
	global $hoy;
	global $MAX_LEVEL;
	global $agregar;
	global $ignorar;
	global $extensiones;
	global $verbose;
	global $limpiar;
	global $countpack;
	$voypack=0;
	$level++;	
	$sql='';
	if ($level < $MAX_LEVEL){
	$contents = ftp_nlist($conn, $from);	
	if (ftp_size($conn, $from)==-1)
		if (is_array($contents)) {
			foreach ($contents as $item) {
				$voypack++;
				if($voypack>$countpack) {
					$voypack = 0;
				//	echo $sql;
					pg_query($db,$sql);
					$sql='';
				    }
				$item=str_replace("\\","/",$item);
				$p=strripos($item,"/");
				$descrip=substr($item,$p+1);
				$agrega ="";
    				if (isset($agregar[$ftp_server])) $agrega = $agregar[$ftp_server];
				if (substr($item,0,1)!="/") $agrega="/";
				$ruta="ftp://".$ftp_server.$agrega.$item;				
				$descrip=str_replace("'","\'",$descrip);
				$ruta=str_replace("'","\'",$ruta);
				$tabla="descriptions";
				foreach ($extensiones as $ext){
				   if (substr($ruta,strlen($ruta)-(strlen($ext)+1),strlen($ext)+1)==".".$ext) {				    
				    $tabla="knows";
				    break;
				    }
				}
				
				$tamano = ftp_size($conn,$item);
				if ($verbose==true) echo "[NIVEL $level] ($tamano bytes)$ruta \n";				
    				$sql=$sql."insert into $tabla (description,fecha,hora,ruta,tamano,nivel) values ('$descrip','".$hoy['mday'].'/'.$hoy['mon'].'/'.$hoy['year']."','".$hoy['hours'].':'.$hoy['minutes'].':'.$hoy['seconds']."','$ruta','$tamano',$level);";
				if ($limpiar==false) $sql=$sql."UPDATE $tabla SET description='$descrip',fecha='".$hoy['mday'].'/'.$hoy['mon'].'/'.$hoy['year']."',hora='".$hoy['hours'].':'.$hoy['minutes'].':'.$hoy['seconds']."', tamano='$tamano', nivel=$level WHERE ruta='$ruta';";				
				listar($conn,$item,$db,$ftp_server,$level);
			}
			if ($sql!='') pg_query($db,$sql);
		}
	}
}

echo "[INFO] Conectando...\n";
$db=pg_connect($basededatos);
pg_set_client_encoding($db,'SQL_ASCII');

if ($limpiar==true) {
    echo "[INFO] LIMPIANDO...";
    pg_query($db,"delete from descriptions");
    echo "[OK] \n";
}

$hoy=getdate();

foreach ($ftp_servers as $ftp){
	echo "[INFO] Conectando al FTP... ".$ftp;
	$conn_id = ftp_connect($ftp); 
	if ($conn_id) {
    	    echo "[OK] Indexando...";
	    $login_result = ftp_login($conn_id, "ftp", "buscadordearchivos@electrica.cujae.edu.cu"); 
    	    if ($login_result) listar($conn_id,"/",$db,$ftp,0);	
	    ftp_close($conn_id); 
	    echo "[OK] \n";
	} else {
	    echo "[FAILED] \n";
	}
	
}

if ($eliminar_antiguos==true){
    pg_query($db,"delete from descriptions where fecha <> '".$hoy['mday'].'/'.$hoy['mon'].'/'.$hoy['year']."';");
    pg_query($db,"delete from knows where fecha <> '".$hoy['mday'].'/'.$hoy['mon'].'/'.$hoy['year']."';");
}
/*
	$conn_id = ftp_connect('teleco.cujae.edu.cu'); 
	$login_result = ftp_login($conn_id, "ftp", "buscadordearchivos@electrica.cujae.edu.cu"); 
	if ($login_result) listar($conn_id,"/Incoming",$db,$ftp);
	ftp_close($conn_id); 
*/
pg_close($db);
?>