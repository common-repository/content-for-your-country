<?php
/**
 * Plugin Name: Content by Country
 * Plugin URI: http://www.soluciones-internet.es/content-by-country/
 * Description: Only shows the content for the desired Country. Use [privatecontent] ONLY SHOW FOR DESIRED COUNTRY  [/privatecontent] in posts. Widget included.
 * Version: 1.1
 * Author: Yannick Arrimadas Bot
 * Author URI: http://www.soluciones-internet.es
 *
 */

// SET CONSTANTS
define ( 'cn_VERSION', '1.0' );
// Plugin's basename
if (! defined ( 'CN_PLUGIN_BASENAME' ))
define ( 'CN_PLUGIN_BASENAME', plugin_basename ( __FILE__ ) );
//Plugin's Path
if (! defined ( 'CN_PATH' ))
define ( 'CN_PATH', dirname ( __FILE__ ) );
//Plugins name
if (! defined ( 'CN_PLUGIN_NAME' ))
define ( 'CN_PLUGIN_NAME', trim ( dirname ( SP_PLUGIN_BASENAME ), '/' ) );
//Plugins URL
if (! defined ( 'CN_URL' ))
define ( 'CN_URL', WP_PLUGIN_URL . '/' . SP_PLUGIN_NAME );

//SHORTCODE CONSTANTS
DEFINE ( "CN_NACIONAL", "1" );


/**
 * AÐadimos la funcion que cargara nuestro widget al evento widgets_init.
*/
add_action( 'widgets_init', 'cbc_cargar_widget' );

function cbc_cargar_widget() {
    register_widget( 'cbc_widget' );
}


/**
 * Clase del Widget.
 * Clase encargada de todas las funciones del nuestro Widget:
 * Preferencias, form, mostrar, y actualizar!
 *
 */

class cbc_widget extends WP_Widget {


    function cbc_widget() {


        /* Preferencias del Widget. */
        $widget_ops = array( 'classname' => 'cbc', 'description' => __('Widget for show content only to desired country.', 'cbc') );

        /* Widget control settings. */
        $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'cbc-widget' );

        /* Creamos el widget. */
        $this->WP_Widget( 'cbc-widget', __('Content for your country', 'cbc'), $widget_ops, $control_ops );
    }

    /**
	 * Como se muestra el widget en pantalla.
	 */
    function widget( $args, $instance ) {
        extract( $args );

        /* Las variables del widget. */
        $title = apply_filters('widget_title', $instance['title'] );
        $html = $instance['html'];

        /* Before widget (defined by themes). */
        echo $before_widget;


        /* Display the image if the url was entered. */
        // if ( $this->nacional()==true)
        if ( ContentByCountry::nacional()==true)
        //		echo '<a href="'.$link.'"><img src="'.$url.'"  alt="'.$title.'" /></a>';
        echo $html;

        /* After widget (defined by themes). */
        echo $after_widget;
    }


    /**
	 * Update the widget settings.
	 */
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        /* Strip tags for title and name to remove HTML (important for text inputs). */
        $instance['html'] = $new_instance['html'];


        return $instance;
    }

    /**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
    function form( $instance ) {

        /* Set up some default widget settings. */
        $defaults = array( 'html' => __('Here goes the html', 'cbc'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>



		<!-- HTML: Text Area -->
		<p>
			<label for="<?php echo $this->get_field_id( 'html' ); ?>"><?php _e('Html:', 'cbc'); ?></label>
            <textarea name="<?php echo $this->get_field_name( 'html' ); ?>" id="<?php echo $this->get_field_id( 'html' ); ?>" class="widefat" rows="16" cols="20"><?php echo $instance['html']; ?></textarea>
		</p>


	<?php
    }

}





/**
 * Clase del Plugin.
 * Clase encargada de todas las funciones del nuestro plugin:
 * Preferencias, form, mostrar, y actualizar!
 *
 */

class  ContentByCountry {

    //Datos del Plugin
    var $plugin_name = 'Content by Country';
    var $plugin_ref = 'CountryByContent';
    var $plugin_abv = 'cbc';

    //BBDD
    var $opt;
    var $table_config;
    // Some Defaults
    var $country = 'Spain';

    function ContentByCountry(){

        global $wpdb;

        //Definimos el nombre de las tablas con las que trabajaremos
        $this->table_config = $wpdb->prefix . 'cbc_config';

        //Hook para la instalacion
        register_activation_hook ( __FILE__, array ($this, 'install' ) );
        //Hook para desinstalar
        register_deactivation_hook ( __FILE__, array ($this, 'uninstall' ) );
        //aÌ±adimos las pagina de opciones del administrador
        add_action ( 'admin_menu', array ($this, 'config_page' ) );
        //Obtenemos las variables de la tabla de configuracion
        add_action ( 'admin_init', array ($this, 'getConfig' ) );
        //Iniciamos el shortcode
        add_shortcode ( 'privatecontent', array ($this, 'init' ) );
    }


    // instalar cosas del plugin
    function install() {
        global $wpdb;

        //Creamos la table donde se alojaran los datos de configuracion del plugin
        $sql = "CREATE TABLE " . $this->table_config . " (
id int(11) NOT NULL,
api MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
pais VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";

        //Ejecutamos la consulta
        $wpdb->query ( $sql );

        //Insertamos los datos por defecto
        $sql = "INSERT INTO " . $this->table_config . " (id, api, pais) VALUES
(1, '', 'Spain');";

        //Ejecutamos la consulta
        $wpdb->query ( $sql );

    }

    // desinstalar
    function uninstall() {
        global $wpdb;

        //Borramos la tablas previamente creadas
        $sql = "drop table " . $this->table_config;
        //Ejecutamos la consulta
        $wpdb->query ( $sql );

    }

    // pÌÁgina de configuraciÌ?n
    function config_page() {
        add_menu_page ( 'Content by Country', 'CbC', 'manage_options', 'cbc', array ($this, 'options_plugin' ) );
        //add_submenu_page ( 'cbc', 'Content by Country', 'Info', 'manage_options', 'config-info', array ($this, 'options_plugin' ) );
        add_submenu_page ( 'cbc', 'Content by Country', 'Config', 'manage_options', 'config-cbc', array ($this, 'options_plugin' ) );
    }

    //opciones de configuraciÌ?n
    function options_plugin() {
        global $wpdb;

        // Actualizaciones de las opciones de menu
        $accion = $_POST ["accion"];

        switch ($accion) {
            case "actualizar-configuracion" :
                include ("admin/actualizar-configuracion.php");
                break;
            case "config-info" :
                include ("admin/form-info.php");
                break;
            default :
                //include ("admin/form-info.php");
                break;
        }

        //Comprobamos a que pagina quiere acceder
        $pagina_acceso = $_GET ["page"];

        switch ($pagina_acceso) {
            case "config-cbc" :
                include ("admin/form-config.php");
                break;
            default :
                include ("admin/form-info.php");
                break;
        }

    }


    function getConfig() {
        global $wpdb;
        // Obtenemos los datos de la BBDD para mostrarlos
        $sql = "SELECT * FROM " . $this->table_config;
        //die($sql);
        $config = $wpdb->get_row ( $sql, ARRAY_A );

        $this->api = $config ["api"];
        $this->pais = $config ["pais"];
    }




     function obtener_ip() {
        global $HTTP_SERVER_VARS;
        if ($HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"] != "") {
            $ip = $HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"];
        }else{ $ip = $HTTP_SERVER_VARS["REMOTE_ADDR"]; }
        return($ip);
    }



    //ejecuta resultado na vez encontrado el shortcode
    function init($atts, $contenido = null) {
        global $wpdb;

        //Obtengo los datos de la BBDD
        $this->opt = $wpdb->get_row ( 'SELECT * FROM ' . $this->table_name . ' LIMIT 1', ARRAY_A );

        $ip_visitante = $this->obtener_ip();

        //Si el API esta vacia ponemos la por defecto
        if ($this->api == ''){
            $key = 'aa093c244a5b66fcb925c4bbc34f8a4c2eed6c26b8e57e43b0cfa7412cff7703';
        }else{
            $key = $this->api;
        }
        $url_a_consultar = 'http://api.ipinfodb.com/v2/ip_query_country.php?key='.$key.'&ip='.$ip_visitante;

        $cadena = file_get_contents($url_a_consultar);


        //Si el usuario es espaÌ±ol mostramos el contenido
        if($this->nacional() == true)
        return  $contenido;

    }



    function nacional(){
        global $wpdb;
        $datos = $wpdb->get_row ( 'SELECT * FROM ' . $this->table_config . ' LIMIT 1', ARRAY_A );


        $api = $datos["api"];
        $pais = $datos["pais"];


        $ip_visitante = $this->obtener_ip();

        //Si el API esta vacia ponemos la por defecto
        if ($api == ''){
            $key = 'aa093c244a5b66fcb925c4bbc34f8a4c2eed6c26b8e57e43b0cfa7412cff7703';
        }else{
            $key = $api;
        }
        $url_a_consultar = 'http://api.ipinfodb.com/v2/ip_query_country.php?key='.$key.'&ip='.$ip_visitante;

        $cadena = file_get_contents($url_a_consultar);
        //Defino el pais que ha seleccionado el usuario
        $buscar = $pais;

        if (strstr($cadena,$buscar)){
            return true;
        }else{
            return false;
        }
    }




    /**
	 * Update the widget settings.
	 */
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        /* Strip tags for title and name to remove HTML (important for text inputs). */
        $instance['content'] = $new_instance['content'];


        return $instance;
    }

    /**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
    function form( $instance ) {

        /* Set up some default widget settings. */
        $defaults = array( 'title' => __('Content by Country', 'nacional'), 'content' => __('html', 'nacional'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>



		<!-- HTML: Text Area -->
		<p>
			<label for="<?php echo $this->get_field_id( 'content' ); ?>"><?php _e('Html:', 'nacional'); ?></label>
            <textarea name="<?php echo $this->get_field_name( 'content' ); ?>" id="<?php echo $this->get_field_id( 'content' ); ?>" class="widefat" rows="16" cols="20"><?php echo $instance['content']; ?></textarea>
		</p>


	<?php
    }
}

$cbc = new ContentByCountry ();

?>