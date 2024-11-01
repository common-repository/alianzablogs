<?php
/**
 * @package AlianzaBlogs
 * @author Hector Cabrera
 * @version 1.90
 */
/*
Plugin Name: Intercambio de Contenido, AlianzaBlogs
Plugin URI: http://wordpress.org/#
Description: Intercambio de Contenido, noticias, analisis, etc... para Wordpress desde la red AlianzaBlogs. Consigue contenido de forma totalmente gratuita para tu Blog mientras que a su vez compartes tu contenido con otros usuarios.
Author: Hector Cabrera
Version: 1.80
Author URI: http://www.alianzablogs.com/
*/

add_action('publish_post', 'notifica');

if($_POST['ametakey']!=""){
$dir="../wp-content/uploads/alianzablogs/".date('Y');
if (!is_dir($dir)){
@mkdir($dir, 0777,true);
}

$desc=catch_images(stripslashes($_POST['content']))."<p>&nbsp;</p><div height=\"60\">".$_POST['pdraft']."</div>";
$tags=str_replace(";",", ",$_POST['categories']);
$post = array(
  'post_content' => $desc,
  'post_status' => 'draft',
  'post_title' => $_POST['post_title'],
  'post_type' => 'post',
  'tags_input' => "AlianzaBlogs,".$tags,
  'post_author' => 1
);
$id=wp_insert_post($post);
add_post_meta($id,'_alianza_pid', $_POST["ametakey"]);
$stat=load("http://".get_option("ufeedNews")."?p=".$_POST["ametakey"]."&u=".get_option('alianzablog_user'));
header("location:post.php?action=edit&post=".$id);
}


function catch_images($original) {
$mod=$original;
$i=0;
preg_match_all("/<img[\s]+[^>]*?src[\s]?=[\s\"\']+(.*\.([gif|jpg|png|bmp|jpeg|tiff]{3,4}))[\"\']+.*?>/", $original, $images);
$images = $images[1];
foreach($images as $img){
        $imagen=load($img);
        $temporal=tempnam("", "temp_");
        $f=fopen($temporal,"rw+");
        fwrite($f,$imagen);
        if(!file_exists($_SERVER['DOCUMENT_ROOT']."/wp-content/uploads/alianzablogs/".date('Y')."/".md5($original.$i).".jpg")){
        rename($temporal,$_SERVER['DOCUMENT_ROOT']."/wp-content/uploads/alianzablogs/".date('Y')."/".md5($original.$i).".jpg");
        }
        
        fclose($f);
        $mod=str_replace($img,get_option('siteurl')."/wp-content/uploads/alianzablogs/".date('Y')."/".md5($original.$i).".jpg",$mod);
        $i++;
        }
        return $mod;
        }




function myplugin_menu() {
  if ( function_exists('add_management_page') ) {
    $page = add_management_page( 'myplugin', 'myplugin', 9, __FILE__, 'myplugin_admin_page' );
    add_action( "admin_print_scripts-$page", 'myplugin_admin_head' );
  }
} 



register_activation_hook( __FILE__, 'instalar' );
register_deactivation_hook( __FILE__, 'desinstalar' );


function notifica($post_ID){

if(get_option('alianzablog_user')){
$postid=get_post($post_ID);
$ametakey=get_post_meta($post_ID,'_alianza_pid',true);

$_title=base64_encode(htmlspecialchars($postid->post_title,ENT_QUOTES));
$_content=base64_encode(htmlspecialchars($postid->post_content,ENT_QUOTES));
$categories = get_the_category($post_ID);
$_categories="";
foreach($categories as $cada){
$_categories.=$cada->cat_name.";";
}
$_categories=base64_encode(htmlspecialchars($_categories,ENT_QUOTES));
$_dategmt=base64_encode(htmlspecialchars($postid->post_date_gmt,ENT_QUOTES));
$_author=base64_encode(htmlspecialchars(get_bloginfo('name'),ENT_QUOTES));
$_url=base64_encode(htmlspecialchars(get_bloginfo('url'),ENT_QUOTES));
$_useralianza=base64_encode(get_option('alianzablog_user'));

if(!$ametakey){
$form_values="pid=".$post_ID."&t=".$_title."&c=".$_content."&cats=".$_categories."&gmt=".$_dategmt."&a=".$_author."&u=".$_url."&uid=".$_useralianza;
$options=array('method'=>'post','post_data'=>$form_values);

load("http://".get_option('alianzablog_user').":".get_option('alianzablog_passwd')."@".get_option('recibe'),$options);
}

}

}


function instalar(){
$ahost="alianzablog.com/wplugin";
$ufeed=$ahost."/members/noticias.php";
$ufeedCats=$ahost."/members/cats.php";
$ufeedNews=$ahost."/members/news.php";
$recibe=$ahost."/members/recibe.php";
add_option("ufeed",$ufeed);
add_option("ufeedCats",$ufeedCats);
add_option("ufeedNews",$ufeedNews);
add_option("recibe",$recibe);
}

if(!get_option('alianzablog_user')){
add_action('admin_notices', 'alianza_warning');
}


		function alianza_warning() {

	echo "
			<div id='alianza-warning' class='updated fade'><p><strong>".__('AlianzaBlogs esta casi listo!.')."</strong> ".__('Debes introducir tu Nombre de usuario y Password para comenzar a usar el PlugIn')."</p></div>";
	

	} 




function desinstalar(){
delete_option("ufeed");
delete_option("ufeedCats");
delete_option("recibe");
delete_option('alianzablog_user');
delete_option('alianzablog_passwd');
delete_option('alianza_blog');
}

   function check_user() 
   {       
   $link="http://".get_option('recibe');
   
       $url_parts = @parse_url( $link ); 

       if ( empty( $url_parts["host"] ) ) return( false ); 

       if ( !empty( $url_parts["path"] ) ) 
       { 
           $documentpath = $url_parts["path"]; 
       } 
       else 
       { 
           $documentpath = "/"; 
       } 

       if ( !empty( $url_parts["query"] ) ) 
       { 
           $documentpath .= "?" . $url_parts["query"]; 
       } 

       $host = $url_parts["host"]; 
       $port = $url_parts["port"]; 
       // Now (HTTP-)GET $documentpath at $host"; 

       if (empty( $port ) ) $port = "80"; 
       $socket = @fsockopen( $host, $port, $errno, $errstr, 30 ); 
       if (!$socket) 
       { 
           return(false); 
       } 
       else 
       { 
       $user=base64_encode(get_option('alianzablog_user').":".get_option('alianzablog_passwd'));
           fwrite ($socket, "HEAD ".$documentpath." HTTP/1.0\r\nHost: $host\r\nAuthorization: Basic ".$user."\n\r\n"); 
           $http_response = fgets( $socket, 22 ); 
            
           if ( ereg("200 OK", $http_response, $regs ) ) 
           { 
               return(true);
               fclose( $socket ); 
           } else 
           { 
//                echo "HTTP-Response: $http_response<br>"; 
               return(false); 
           } 
       } 
   }


function paso1(){
global $ufeed, $ufeedCats;
if(!get_option('alianzablog_user')){
echo'<div class="wrap">
<div id="icon-edit" class="icon32"><br /></div>
<h2>Advertencia!</h2>
	<p>Antes de poder acceder al listado de entradas de la red de la Alianza, debes configurar conrrectamente este PlugIn.</p>
	<p>Ve al panel del PlugIn AlianzaBlogs que tienes a tu izquierda y luego accede a la subseccion "Configuracion", ahi deber&aacute;s introducir tu USUARIO y PASSWORD de la red.</p>
</div>
<h1></h1>';

}elseif(check_user()==false){
echo'<div class="wrap">
<div id="icon-edit" class="icon32"><br /></div>
<h2>Advertencia!</h2>
	<p><b>ERROR LOGIN INCORRECTO!.</b></p>
	<p>Verifica que has introducido tus credenciales de acceso correctamente.</p>
	<p>Ve al panel del PlugIn AlianzaBlogs que tienes a tu izquierda y luego accede a la subseccion "Configuracion", ahi deber&aacute;s introducir tu USUARIO y PASSWORD de la red.</p>
</div>
<h1></h1>';
}else{

//Capturar XML desde la fuente de la alianza.

$acategories="http://".get_option('alianzablog_user').":".get_option('alianzablog_passwd')."@".get_option('ufeedCats');
$adata="http://".get_option('alianzablog_user').":".get_option('alianzablog_passwd')."@".get_option('ufeed')."?u=".get_option('alianzablog_user')."&max=".$_GET['max']."&mes=".$_GET['mes']."&cat=".$_GET['cat'];
$pdraft="http://".get_option('ufeedNews');






function cargarNoticia($content){
$content=load($content);
$content=preg_replace("/<script.*<\/script>/i"," ",$content);
$content=preg_replace("/<noscript.*<\/noscript>/i"," ",$content);
$content=preg_replace("/<\!.*-->/i"," ",$content);
$doc = new DOMDocument();
	$doc->loadXML($content);
	$arrFeeds = array();
	foreach ($doc->getElementsByTagName('item') as $node) {
		$itemRSS = array ( 
			'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
			'id' => $node->getElementsByTagName('id')->item(0)->nodeValue,
			'category' => $node->getElementsByTagName('category')->item(0)->nodeValue,
			'author' => $node->getElementsByTagName('author')->item(0)->nodeValue,
			'desc' => $node->getElementsByTagName('description')->item(0)->nodeValue,
			'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
			'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue
			);
		array_push($arrFeeds, $itemRSS);
	}
return $arrFeeds;
}


$datos=cargarNoticia($adata);
$cdatos=cargarNoticia($acategories);
$pdraft=load($pdraft);


echo'<div class="wrap">
<div id="icon-edit" class="icon32"><br /></div>
<h2>Intercambio de Contenido</h2>';

	echo'<p>Categorias:&nbsp;<select onchange="window.location.href=\'?page=alianzablogs/alianzablogs.php&max='.$_GET['max']."&mes=".$_GET['mes'].'&cat=\' + this.options
    [this.selectedIndex].value">';
    $i=0;
foreach($cdatos as $cesto){
if($cesto['id']==$_GET['cat']){
	echo'<option value="'.$cesto['id'].'" selected>'.$cesto['title'].'</option>';
	}else{
	echo'<option value="'.$cesto['id'].'">'.$cesto['title'].'</option>';	
	}
	}
	echo'</select>';
	
	echo'&nbsp;Mostrar:&nbsp;<select onchange="window.location.href=\'?page=alianzablogs/alianzablogs.php&cat='.$_GET['cat']."&mes=".$_GET['mes'].'&max=\' + this.options
    [this.selectedIndex].value">>';

$maximo=array(10,25,50,100,150,200,250);	
	
	foreach($maximo as $numax){
if($numax==$_GET['max']){	
	echo'<option value="'.$numax.'" selected>'.$numax.'</option>';
	}else{
	echo'<option value="'.$numax.'">'.$numax.'</option>';
	}
	}
	echo'</select>';
	
	
		echo'&nbsp;Mes:&nbsp;<select onchange="window.location.href=\'?page=alianzablogs/alianzablogs.php&cat='.$_GET['cat']."&max=".$_GET['max'].'&mes=\' + this.options
    [this.selectedIndex].value">>';
$ano=date('Y');
$maximos=array('Enero'=>$ano."01",'Febrero'=>$ano."02",'Marzo'=>$ano."03",'Abril'=>$ano."04",'Mayo'=>$ano."05",'Junio'=>$ano."06",'Julio'=>$ano."07",'Agosto'=>$ano."08",'Septiembre'=>$ano."09",'Octubre'=>$ano."10",'Noviembre'=>$ano."11",'Diciembre'=>$ano."12");	
	
	foreach($maximos as $key=>$numaxs){
if($numaxs==$_GET['mes']){	
	echo'<option value="'.$numaxs.'" selected>'.$key.'</option>';
	}else{
	if(($numaxs==$ano.date('m') AND (!$_GET['mes']))){
	echo'<option value="'.$numaxs.'" selected>'.$key.'</option>';
	}else{
	echo'<option value="'.$numaxs.'">'.$key.'</option>';
	}
	}
	}
	echo'</select></p>';
	
	
	?><table class="widefat" cellspacing="0" id="<?php echo $context ?>-plugins-table">
	<thead>
	<tr>
		<th scope="col" class="manage-column check-column"></th>
		<th scope="col" class="manage-column"><?php _e('Title'); ?></th>
		<th scope="col" class="manage-column"><?php _e('Description'); ?></th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<th scope="col" class="manage-column check-column"></th>
		<th scope="col" class="manage-column"><?php _e('Title'); ?></th>
		<th scope="col" class="manage-column"><?php _e('Description'); ?></th>
	</tr>
	</tfoot>
	<tbody class="plugins">

<?php

foreach($datos as $esto){
if(($i==$_GET['max']) AND ($i>0)){
break;
}else{

	echo "<tr>
		<th scope='row' class='check-column'><form name=\"post\" action=\"\" method=\"post\" id=\"post\"></th>
		<td><strong><input type=\"hidden\" name=\"pdraft\" id=\"pdraft\" value=\"".esc_attr(htmlspecialchars($pdraft))."\"><input type=\"hidden\" name=\"post_title\" id=\"title\" value=\"".esc_attr( htmlspecialchars( $esto['title'] ) )."\">".$esto['title']."</strong></td>";


		
		
		echo"<td><p><input type=\"hidden\" name=\"content\" id=\"content\" value=\"".htmlspecialchars($esto['desc'])."\">";
		
		
		echo breve_descripcion(strip_tags(htmlspecialchars_decode($esto['desc'])));
		echo"</p></td>
	</tr>
	<tr>
		<td></td>
		<td>";
		echo '<div><b>Fuente:</b> <a href="'.$esto['link'].'" target="_blank">'.$esto['link'].'</a></div></td>
		<td><b>Autor:</b> '.$esto['author'].' <b>Fecha:</b> '.$esto['date'].'</td>
	</tr>';
	echo"<tr>
		<td></td>
		<td></td><td><b>Categoria y Tags de la Fuente:</b> ";
		if(is_array($esto['category'])){
		$cats="";
		foreach($esto['category'] as $cat){
		echo $cat.", ";
		$cats.=$cat.",";
		}
		
		}else{
		echo $esto['category'];
		$cats=$esto['category'];
		}
		
		echo"<input type=\"hidden\" name=\"categories\" id=\"post_tag\" value=\"".esc_attr( htmlspecialchars( $cats ) )."\">";
		
				echo"<input type=\"hidden\" name=\"ametakey\" id=\"ametakey\" value=\"".$esto['id']."\">";

		
		$cantidad=query_posts('meta_key=_alianza_pid&meta_value='.$esto['id']);

	if(count($cantidad)==0){
		echo'<p align="right"><input name="publish" type="submit" class="button-primary" id="publish" value="Seleccionar" /></p>';
		}else{
				echo'<p align="right"><a href="post.php?action=edit&post='.$cantidad[0]->ID.'"><-- EDITAR NOTICIA --></a></p>';
		}
	

	
	echo"</form></td></tr>";	
	echo "<tr>
		<td style='border-bottom:1px solid;border-color:#ccc;'></td>
		<td style='border-bottom:1px solid;border-color:#ccc;'></td>
		<td style='border-bottom:1px solid;border-color:#ccc;'></td>
	</tr>";
	$i++;
	}
}
	
?>
	</tbody>
</table>
<?php

	echo'</form>
<p align="right"><a href="http://www.alianzablogs.com">AlianzaBlogs - La Red de Intercambio de Contenido Web.</a></p>
	</div>';
	}
}



add_action('admin_menu', 'alianza_menu'); 
function alianza_menu()
{
	// Extraemos el directorio en el que estamos para ir usándolo luego
	$pluginDir = pathinfo( __FILE__ );
	$pluginDir = $pluginDir['dirname'];
	
	// Titulo de la página
	$page_title = "Intercambio de Contenido AlianzaBlogs.";
	
	// Título que aparece en el menú					
	$menu_title = "Auto Entradas";

	// Nivel de acceso
	$access_level = "1";

	// página de destino
	$content_file = $pluginDir . '/alianzablogs.php'; 
	
	// Función para cargar dentro de la página incluida para generar el menú
	// Si no se indica, se asume que al incluir el fichero ya se ha generado todo el
	// contenido necesario.
	$content_function = paso1;
	
	// url del icono para el menú
	$menu_icon_url = '/wp-content/plugins/alianzablogs/alianza.png';
	
	add_object_page($page_title, "AlianzaBlogs", $access_level, $content_file,$content_function,$menu_icon_url);
		// Declaramos también como primer submenú la misma página con los mismos datos
		add_submenu_page($content_file,$page_title, $menu_title, $access_level, $content_file, $content_function); 
		
	
	//////// DECLARACION DE CADA SUBMENU ////////////
	// La funcón para declarar submenús es igual que la anterior
	// pero se añade como primer parámetro el nombre usado como
	// $content_file en la página padre
	// Además, ya no se informa nunca el icon file.
	
	$page_parent = $pluginDir . '/alianzablogs.php';// $content_file del padre
	$page_title = "Configuracion AlianzaBlogs";							// Titulo de la página
	$menu_title = "Configuracion";							// Título que aparece en el menú
	$access_level = "0";									// Nivel de acceso
	$content_file = '/alianzablogs.php'; // página de destino
	$content_function = alianza_plugin_options;								// función a lanzar en la página de destino
	add_submenu_page($page_parent, $page_title, $menu_title, $access_level, $content_file, $content_function); 
}




function alianza_plugin_options() {
echo'
<div class="wrap">
<h2>Configuracion AlianzaBlogs PlugIn</h2>

<form method="post" action="options.php">';

wp_nonce_field('update-options');

echo'<table class="form-table">

<tr valign="top">
<th scope="row">Usuario:</th>
<td><input type="text" name="alianzablog_user" value="';
echo get_option('alianzablog_user');
echo'" /></td>
</tr>
 
<tr valign="top">
<th scope="row">Password:</th>
<td><input type="password" name="alianzablog_passwd" value="';
echo get_option('alianzablog_passwd');
echo'" /></td>
</tr>



</table>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="alianzablog_user,alianzablog_passwd,alianza_blog" />

<p class="submit">
<input type="submit" class="button-primary" value="';
_e('Save Changes');
echo'" />
</p>

</form>
<hr>
<p align="right"><a href="http://www.alianzablogs.com">AlianzaBlogs - La Red de Intercambio de Contenido Web.</a></p>
</div>';
}



function load($url,$options=array()) {
    $default_options = array(
        'method'        => 'get',
        'post_data'        => false,
        'return_info'    => false,
        'return_body'    => true,
        'cache'            => false,
        'referer'        => '',
        'headers'        => array(),
        'session'        => false,
        'session_close'    => false,
    );
    // Sets the default options.
    foreach($default_options as $opt=>$value) {
        if(!isset($options[$opt])) $options[$opt] = $value;
    }

    $url_parts = parse_url($url);
    $ch = false;
    $info = array(//Currently only supported by curl.
        'http_code'    => 200
    );
    $response = '';
    
    $send_header = array(
        'Accept' => 'text/*',
        'User-Agent' => 'BinGet/1.00.A (http://www.bin-co.com/php/scripts/load/)'
    ) + $options['headers']; // Add custom headers provided by the user.
    
    if($options['cache']) {
        $cache_folder = joinPath(sys_get_temp_dir(), 'php-load-function');
        if(isset($options['cache_folder'])) $cache_folder = $options['cache_folder'];
        if(!file_exists($cache_folder)) {
            $old_umask = umask(0); // Or the folder will not get write permission for everybody.
            mkdir($cache_folder, 0777);
            umask($old_umask);
        }
        
        $cache_file_name = md5($url) . '.cache';
        $cache_file = joinPath($cache_folder, $cache_file_name); //Don't change the variable name - used at the end of the function.
        
        if(file_exists($cache_file)) { // Cached file exists - return that.
            $response = file_get_contents($cache_file);
            
            //Seperate header and content
            $separator_position = strpos($response,"\r\n\r\n");
            $header_text = substr($response,0,$separator_position);
            $body = substr($response,$separator_position+4);
            
            foreach(explode("\n",$header_text) as $line) {
                $parts = explode(": ",$line);
                if(count($parts) == 2) $headers[$parts[0]] = chop($parts[1]);
            }
            $headers['cached'] = true;
            
            if(!$options['return_info']) return $body;
            else return array('headers' => $headers, 'body' => $body, 'info' => array('cached'=>true));
        }
    }

    if(isset($options['post_data'])) { //There is an option to specify some data to be posted.
        $options['method'] = 'post';
        
        if(is_array($options['post_data'])) { //The data is in array format.
            $post_data = array();
            foreach($options['post_data'] as $key=>$value) {
                $post_data[] = "$key=" . urlencode($value);
            }
            $url_parts['query'] = implode('&', $post_data);
        } else { //Its a string
            $url_parts['query'] = $options['post_data'];
        }
    } elseif(isset($options['multipart_data'])) { //There is an option to specify some data to be posted.
        $options['method'] = 'post';
        $url_parts['query'] = $options['multipart_data'];
        /*
            This array consists of a name-indexed set of options.
            For example,
            'name' => array('option' => value)
            Available options are:
            filename: the name to report when uploading a file.
            type: the mime type of the file being uploaded (not used with curl).
            binary: a flag to tell the other end that the file is being uploaded in binary mode (not used with curl).
            contents: the file contents. More efficient for fsockopen if you already have the file contents.
            fromfile: the file to upload. More efficient for curl if you don't have the file contents.

            Note the name of the file specified with fromfile overrides filename when using curl.
         */
    }

    ///////////////////////////// Curl /////////////////////////////////////
    //If curl is available, use curl to get the data.
    if(function_exists("curl_init") 
                and (!(isset($options['use']) and $options['use'] == 'fsocketopen'))) { //Don't use curl if it is specifically stated to use fsocketopen in the options
        
        if(isset($options['post_data'])) { //There is an option to specify some data to be posted.
            $page = $url;
            $options['method'] = 'post';
            
            if(is_array($options['post_data'])) { //The data is in array format.
                $post_data = array();
                foreach($options['post_data'] as $key=>$value) {
                    $post_data[] = "$key=" . urlencode($value);
                }
                $url_parts['query'] = implode('&', $post_data);
            
            } else { //Its a string
                $url_parts['query'] = $options['post_data'];
            }
        } else {
            if(isset($options['method']) and $options['method'] == 'post') {
                $page = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
            } else {
                $page = $url;
            }
        }

        if($options['session'] and isset($GLOBALS['_binget_curl_session'])) $ch = $GLOBALS['_binget_curl_session']; //Session is stored in a global variable
        else $ch = curl_init($url_parts['host']);
        
        curl_setopt($ch, CURLOPT_URL, $page) or die("Invalid cURL Handle Resouce");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Just return the data - not print the whole thing.
        curl_setopt($ch, CURLOPT_HEADER, true); //We need the headers
        curl_setopt($ch, CURLOPT_NOBODY, !($options['return_body'])); //The content - if true, will not download the contents. There is a ! operation - don't remove it.
        $tmpdir = NULL; //This acts as a flag for us to clean up temp files
        if(isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
            curl_setopt($ch, CURLOPT_POST, true);
            if(is_array($url_parts['query'])) {
                //multipart form data (eg. file upload)
                $postdata = array();
                foreach ($url_parts['query'] as $name => $data) {
                    if (isset($data['contents']) && isset($data['filename'])) {
                        if (!isset($tmpdir)) { //If the temporary folder is not specifed - and we want to upload a file, create a temp folder.
                            //  :TODO:
                            $dir = sys_get_temp_dir();
                            $prefix = 'load';
                            
                            if (substr($dir, -1) != '/') $dir .= '/';
                            do {
                                $path = $dir . $prefix . mt_rand(0, 9999999);
                            } while (!mkdir($path, $mode));
                        
                            $tmpdir = $path;
                        }
                        $tmpfile = $tmpdir.'/'.$data['filename'];
                        file_put_contents($tmpfile, $data['contents']);
                        $data['fromfile'] = $tmpfile;
                    }
                    if (isset($data['fromfile'])) {
                        // Not sure how to pass mime type and/or the 'use binary' flag
                        $postdata[$name] = '@'.$data['fromfile'];
                    } elseif (isset($data['contents'])) {
                        $postdata[$name] = $data['contents'];
                    } else {
                        $postdata[$name] = '';
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $url_parts['query']);
            }
        }

        //Set the headers our spiders sends
        curl_setopt($ch, CURLOPT_USERAGENT, $send_header['User-Agent']); //The Name of the UserAgent we will be using ;)
        $custom_headers = array("Accept: " . $send_header['Accept'] );
        if(isset($options['modified_since']))
            array_push($custom_headers,"If-Modified-Since: ".gmdate('D, d M Y H:i:s \G\M\T',strtotime($options['modified_since'])));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
        if($options['referer']) curl_setopt($ch, CURLOPT_REFERER, $options['referer']);

        curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/binget-cookie.txt"); //If ever needed...
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $custom_headers = array();
        unset($send_header['User-Agent']); // Already done (above)
        foreach ($send_header as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $custom_headers[] = "$name: $item";
                }
            } else {
                $custom_headers[] = "$name: $value";
            }
        }
        if(isset($url_parts['user']) and isset($url_parts['pass'])) {
            $custom_headers[] = "Authorization: Basic ".base64_encode($url_parts['user'].':'.$url_parts['pass']);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);

        $response = curl_exec($ch);

        if(isset($tmpdir)) {
            //rmdirr($tmpdir); //Cleanup any temporary files :TODO:
        }

        $info = curl_getinfo($ch); //Some information on the fetch
        
        if($options['session'] and !$options['session_close']) $GLOBALS['_binget_curl_session'] = $ch; //Dont close the curl session. We may need it later - save it to a global variable
        else curl_close($ch);  //If the session option is not set, close the session.

    //////////////////////////////////////////// FSockOpen //////////////////////////////
    } else { //If there is no curl, use fsocketopen - but keep in mind that most advanced features will be lost with this approch.

        if(!isset($url_parts['query']) || (isset($options['method']) and $options['method'] == 'post'))
            $page = $url_parts['path'];
        else
            $page = $url_parts['path'] . '?' . $url_parts['query'];
        
        if(!isset($url_parts['port'])) $url_parts['port'] = ($url_parts['scheme'] == 'https' ? 443 : 80);
        $host = ($url_parts['scheme'] == 'https' ? 'ssl://' : '').$url_parts['host'];
        $fp = fsockopen($host, $url_parts['port'], $errno, $errstr, 30);
        if ($fp) {
            $out = '';
            if(isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
                $out .= "POST $page HTTP/1.1\r\n";
            } else {
                $out .= "GET $page HTTP/1.0\r\n"; //HTTP/1.0 is much easier to handle than HTTP/1.1
            }
            $out .= "Host: $url_parts[host]\r\n";
        foreach ($send_header as $name => $value) {
        if (is_array($value)) {
            foreach ($value as $item) {
            $out .= "$name: $item\r\n";
            }
        } else {
            $out .= "$name: $value\r\n";
        }
        }
            $out .= "Connection: Close\r\n";
            
            //HTTP Basic Authorization support
            if(isset($url_parts['user']) and isset($url_parts['pass'])) {
                $out .= "Authorization: Basic ".base64_encode($url_parts['user'].':'.$url_parts['pass']) . "\r\n";
            }

            //If the request is post - pass the data in a special way.
            if(isset($options['method']) and $options['method'] == 'post') {
                if(is_array($url_parts['query'])) {
                    //multipart form data (eg. file upload)

                    // Make a random (hopefully unique) identifier for the boundary
                    srand((double)microtime()*1000000);
                    $boundary = "---------------------------".substr(md5(rand(0,32000)),0,10);

                    $postdata = array();
                    $postdata[] = '--'.$boundary;
                    foreach ($url_parts['query'] as $name => $data) {
                        $disposition = 'Content-Disposition: form-data; name="'.$name.'"';
                        if (isset($data['filename'])) {
                            $disposition .= '; filename="'.$data['filename'].'"';
                        }
                        $postdata[] = $disposition;
                        if (isset($data['type'])) {
                            $postdata[] = 'Content-Type: '.$data['type'];
                        }
                        if (isset($data['binary']) && $data['binary']) {
                            $postdata[] = 'Content-Transfer-Encoding: binary';
                        } else {
                            $postdata[] = '';
                        }
                        if (isset($data['fromfile'])) {
                            $data['contents'] = file_get_contents($data['fromfile']);
                        }
                        if (isset($data['contents'])) {
                            $postdata[] = $data['contents'];
                        } else {
                            $postdata[] = '';
                        }
                        $postdata[] = '--'.$boundary;
                    }
                    $postdata = implode("\r\n", $postdata)."\r\n";
                    $length = strlen($postdata);
                    $postdata = 'Content-Type: multipart/form-data; boundary='.$boundary."\r\n".
                                'Content-Length: '.$length."\r\n".
                                "\r\n".
                                $postdata;

                    $out .= $postdata;
                } else {
                    $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
                    $out .= 'Content-Length: ' . strlen($url_parts['query']) . "\r\n";
                    $out .= "\r\n" . $url_parts['query'];
                }
            }
            $out .= "\r\n";

            fwrite($fp, $out);
            while (!feof($fp)) {
                $response .= fgets($fp, 128);
            }
            fclose($fp);
        }
    }

    //Get the headers in an associative array
    $headers = array();

    if($info['http_code'] == 404) {
        $body = "";
        $headers['Status'] = 404;
    } else {
        //Seperate header and content
        $header_text = substr($response, 0, $info['header_size']);
        $body = substr($response, $info['header_size']);
        
        foreach(explode("\n",$header_text) as $line) {
            $parts = explode(": ",$line);
            if(count($parts) == 2) {
                if (isset($headers[$parts[0]])) {
                    if (is_array($headers[$parts[0]])) $headers[$parts[0]][] = chop($parts[1]);
                    else $headers[$parts[0]] = array($headers[$parts[0]], chop($parts[1]));
                } else {
                    $headers[$parts[0]] = chop($parts[1]);
                }
            }
        }

    }
    
    if(isset($cache_file)) { //Should we cache the URL?
        file_put_contents($cache_file, $response);
    }

    if($options['return_info']) return array('headers' => $headers, 'body' => $body, 'info' => $info, 'curl_handle'=>$ch);
    return $body;
} 

function breve_descripcion($texto) {
$cTexto=strip_tags($texto);
$cad = (string) "";
$aPalabras = array();
$aPalabras = split(" ",$cTexto);
for ($i = 0; $i <50; $i++) $cad .= $aPalabras[$i].' ';
echo $cad." ..."; 
}

?>
