<?php
    include 'buscador.conf';
    
    echo "Conectando a la base de datos... \n";
    
    $db = pg_connect($basededatos);
    function query($sql){
	global $db;
	$r = pg_query($db,$sql);
	$rr = pg_fetch_all($r);
	return $rr;
    }
    
    echo "Abriendo archivo... \n";
    
    $f=fopen('../estadisticas.tmp','w');
    fputs($f,"<?php \n");
    
    echo "Calculando total de archivos... \n";
    
    //Total de archivos
    $r=query("select count(ruta) as cant from descriptions");
    fputs($f,'$total = '.$r[0]["cant"].";\n");
    

    echo "Calculando totales de archivos por servidor ...\n";
    
    //Total de archivos por servidor
    
    foreach ($ftp_servers as $ftp){
	echo "...$ftp... ";
	$r=query("select count(ruta) as cant from descriptions where ruta ~ '$ftp';");
	fputs($f,'$totales["'.$ftp.'"] = array();'."\n");
	fputs($f,'$totales["'.$ftp.'"]["total"] = '.$r[0]["cant"]."; \n");
	echo $r[0]["cant"]."\n";
	foreach ($extensiones as $c){
	    $r=query("select count(ruta) as cant from knows where ruta ~ '$ftp' AND description ~ '.$c';");
	    fputs($f,'$totales["'.$ftp.'"]["'.$c.'"] = '.$r[0]["cant"]."; \n");
	    echo '    '.$c.' = '.$r[0]["cant"]."\n";
	    
	}
    }
    
    pg_close($db);
    fputs($f,"?> \n");
    fclose($f);
    
    $f = fopen ("../estadisticas.tmp","r");
    $ff = fopen ("../estadisticas.php","w");
    while (!feof($f)){
	$s=fgets($f);
	fputs($ff,$s);
    }
    fclose($f);
    fclose($ff);
?>