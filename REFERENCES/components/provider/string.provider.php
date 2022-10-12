<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

/**
 * Gestor de cadenas de traducción
 * 
 * Actúa como singleton para proveer un sistema unificado de traducciones pypaseando
 * la porquería de traducciones WP
 * 
 * Constantes y mensajes traducibles en la intranet
 */
class String{
    /**
     * Idioma por defecto
     */
    const LANGUAGE_DEFAULT = 'es_ES';
    /**
     * @var array Lista de cadenas
     */
    private $_stringData = array();
    /**
     * Idioma de la instancia
     * @var string
     */
    private $_lang = self::LANGUAGE_DEFAULT;
    /**
     * Ocultar constructor
     * 
     * La instancia de este gestor de idiomas solo como método alternativo si
     * no llega a funcionar la gestión de idiomas nativa de WP PO/MO.
     * 
     * Hay varios strings que no pegan bien. Cuando resuelvan la traducción y
     * se revise si funciona se resuelve.
     */
    public final function __construct( \CodersApp $app , $locale ) {
        
        $this->_lang = $locale;
        
        $path = self::getTranslationPath( $app , $locale );
        
        $count = $this->loadStrings($path);
    }
    /**
     * @return string
     */
    public final function __toString() {
        //return get_class($this);
        return $this->_lang;
    }
    /**
     * Importa un set de cadenas y contenidos desde el fichero de idiomas seleccionado
     * @param type $lang_path
     * @return int Num de cadenas procesadas
     */
    private final function loadStrings( $lang_path ){

        $counter = 0;
        
        if(file_exists( $lang_path ) ){
            //importar el contenido y parsear los carácteres especiales
            $content = file_get_contents($lang_path);
            
            $lines = explode("\n", $content);
            
            foreach( $lines as $text ){
                
                $translation = explode("\t", $text );
                
                $this->registerString(
                        $translation[0],
                        count($translation) > 1 ? $translation[1] : $translation[0]);
                
                $counter++;
            }
        }
        
        return $counter;
    }
    /**
     * 
     * @param string $string
     * @param string $translation
     */
    private final function registerString( $string, $translation ){
        if( !isset( $this->_stringData[$string] ) ){
            $this->_stringData[$string] = $translation;
        }
    }
    /**
     * Idioma del gestor de cadenas
     * @return string
     */
    public final function getLanguage(){
        return $this->_lang;
    }
    /**
     * Traduce el contenido de la cadena según el mapa de cadenas para el idioma actual
     * @param strint $stringId
     * @return string
     */
    public final function translate( $stringId ){
        return isset( $this->_stringData[$stringId] ) ?
            $this->_stringData[$stringId] : 
            $stringId;
    }
    /**
     * Fecha
     * @param mixed $date
     * @return string
     */
    public final function displayDate( $date ){
        
        $time = is_numeric($date) ? $date : strtotime( $date );
        
        $weekDay = intval(date('w',$time));
        $day = date('d',$time);
        $month = intval(date('m',$time));
        $year = date('Y',$time);
        
        return vsprintf( $this->translate('%s %s de %s, %s'), array(
            $this->displayWeekDay($weekDay), $day,
            $this->displayMonth($month), $year,
        ));
    }
    /**
     * Fecha
     * @param string $timestamp
     * @return string
     */
    public final function displayDateTime( $timestamp ){
        
        if(is_null($timestamp)){
            $timestamp = TripManager::getTimeStamp();
        }
        
        $time = strtotime( $timestamp );
        
        $weekDay = intval(date('w',$time));
        $year = date('Y',$time);
        $month = intval(date('m',$time));
        $day = date('d',$time);
        
        $hour = date('H:i',$time);
        
        return self::__( '%s %s de %s, %s<br/>a las %s Hs.',array(
                $this->displayWeekDay(  $weekDay ), $day,
                $this->displayMonth( $month ), $year,  $hour ));
    }
    /**
     * Muestra el día (nombde del día y número)
     * @param string $date
     * @return string
     */
    public final function translateDay( $date ){
        
        $time = strtotime($date);
        $weekDay = intval(date('w',$time));
        
        return sprintf('%s, %s',
                $this->displayWeekDay($weekDay),
                date('d',$time));
    }
    /**
     * Muestra el día del mes ( Mayo, 5)
     * @param string $date
     * @return string
     */
    public final function displayMonthDay( $date ){
        
        $time = strtotime($date);
        $month = intval(date('m',$time));
        
        return sprintf('%s, %s',
                $this->displayMonth($month),
                date('d',$time));
    }
    /**
     * Traduce el día
     * @param int $day
     * @return string
     */
    public final function displayWeekDay( $day ){
        switch($day ){
            case 1:
                return $this->translate('Lunes');
            case 2:
                return $this->translate('Martes');
            case 3:
                return $this->translate('Mi&eacute;rcoles');
            case 4:
                return $this->translate('Jueves');
            case 5:
                return $this->translate('Viernes');
            case 6:
                return $this->translate('S&aacute;bado');
            case 0:
                return $this->translate('Domingo');
            default:
                return '';
        }
    }
    /**
     * Traduce el mes
     * @param int $month
     * @return string
     */
    public final function displayMonth( $month ){
        switch( $month ){
            case 1:
                return $this->translate( 'Enero' );
            case 2:
                return $this->translate('Febrero');
            case 3:
                return $this->translate('Marzo');
            case 4:
                return $this->translate('Abril');
            case 5:
                return $this->translate('Mayo');
            case 6:
                return $this->translate('Junio');
            case 7:
                return $this->translate('Julio');
            case 8:
                return $this->translate('Agosto');
            case 9:
                return $this->translate('Septiembre');
            case 10:
                return $this->translate('Octubre');
            case 11:
                return $this->translate('Noviembre');
            case 12:
                return $this->translate('Diciembre');
            default:
                return '';
        }
    }
    /**
     * Carga un idioma para utilizar como modelo de traducciones en plantillas y 
     * referencias que difieran del idioma cargado actualmente.
     * 
     * @param string $locale
     * @return \TripManStringProvider
     */
    public static final function loadLanguage( $locale ){
        
        return new TripManStringProvider($locale);
    }
    /**
     * Permite la traducción de la cadena en el contexto de la aplicación de la intranet
     * 
     * En esta versión la traducción nativa de WP es inútil, delegando toda la funcionalidad
     * de traducciones al gestor integrado en esta clase mediante el método interno translate()
     * 
     * @param string $string
     * @param mixed $data Valor o valores opcionales a inyectar dentro de la cadena
     * @return String
     */
    public final function __( $string , $data = null ){

        if( !is_null($data) ){
            //preparar candena con valores adjuntos
            return is_array($data) ?
                    vsprintf( $this->translate($string), $data ) :
                    sprintf( $this->translate($string), $data );
        }
        else{
            return $this->translate($string);
        }
    }
    /**
     * Convierte todos los carácteres especiales no unicode en su corresponsidente código HTML
     * @param string $content
     * @return string
     */
    public static final function encodeHTML( $content ){

        $output = preg_replace("/\n/", '<br />', $content);
        
        $output = preg_replace('/á/', '&aacute;', $output);
        $output = preg_replace('/é/', '&eacute;', $output);
        $output = preg_replace('/í/', '&iacute;', $output);
        $output = preg_replace('/ó/', '&oacute;', $output);
        $output = preg_replace('/ú/', '&uacute;', $output);
        
        $output = preg_replace('/Á/', '&Aacute;', $output);
        $output = preg_replace('/É/', '&Eacute;', $output);
        $output = preg_replace('/Í/', '&Iacute;', $output);
        $output = preg_replace('/Ó/', '&Oacute;', $output);
        $output = preg_replace('/Ú/', '&Uacute;', $output);
        
        $output = preg_replace('/â/', '&acirc;', $output);
        $output = preg_replace('/ê/', '&ecirc;', $output);
        $output = preg_replace('/î/', '&icirc;', $output);
        $output = preg_replace('/ô/', '&ocirc;', $output);
        $output = preg_replace('/û/', '&ucirc;', $output);
        
        return $output;
    }
    /**
     * Definir aquí un diccionario de códigos html para transformar a texto plano UNICODE
     * @param html $html
     * @return string
     */
    public static final function decodeHTML( $html ){
        
        //$content = html_entity_decode( $html, ENT_COMPAT | ENT_HTML401 , 'ISO-8859-1' );
        //fuera html
        $content = strip_tags( $html );
        //tildes agudas
        $content = preg_replace('/&aacute;/', 'á', $content);
        $content = preg_replace('/&eacute;/', 'é', $content);
        $content = preg_replace('/&iacute;/', 'í', $content);
        $content = preg_replace('/&oacute;/', 'ó', $content);
        $content = preg_replace('/&uacute;/', 'ú', $content);
        //acentos graves
        $content = preg_replace('/&agrave;/', 'à', $content );
        $content = preg_replace('/&egrave;/', 'è', $content );
        $content = preg_replace('/&igrave;/', 'ì', $content );
        $content = preg_replace('/&ograve;/', 'ò', $content );
        $content = preg_replace('/&ugrave;/', 'ù', $content );
        
        //campos de moneda
        $content = preg_replace('/&euro;/', '€', $content );

        return $content;
    }
    /**
     * Genera un fichero modelo de cadenas
     * @return array Lista de cadenas detectadas en el fichero
     */
    private static final function importTranslationStrings(){
        
        $source_path = MNK__TRIPMAN__DIR . 'classes/models/string.model.php';
        
        if(file_exists($source_path)){
            
            $handle = fopen( $source_path, 'r' );
            
            if( $handle ){

                $string_def = array();

                $input = fread( $handle, filesize($source_path));
                
                $string = ( $input !== false ) ? explode("\n", $input ) : '';
                
                for( $line=0 ; $line < count($string) ; $line++ ){

                    if(strpos($string[$line], 'const')){
                        $from = strpos($string[ $line ], " = '");

                        $to = strrpos($string[$line], "'");

                        //echo ($from . ': '$to.'<br/>');

                        if( $from !== false && $to !== false ){
                            $extract = substr($string[ $line ],
                                    $from + 4,
                                    $to - ($from + 4) );
                            $string_def[] = array( 'line' => $line+1, 'string' => $extract );
                        }
                    }
                }

                fclose($handle);
                
                return $string_def;
            }
        }

        return array();
    }
    /**
     * @param string $locale
     * @return bool FALSE si ya existe el fichero
     */
    public static final function generateTranslationModel( $locale ){

        $output_path = self::getTranslationPath( $locale);
        
        if(file_exists($output_path)){ return false; }
        
        $strings = self::importTranslationStrings();
        
        $handle = fopen($output_path,'w');

        if( $handle ){

            foreach( $strings as $content ){
                fwrite($handle, sprintf("\n#: ../classes/models/string.model.php:%s\n",$content['line']) );
                fwrite($handle, sprintf("msgid \"%s\"\n",$content['string']) );
                fwrite($handle, sprintf("msgstr \"%s\"\n\n",$content['string']) );
            }
            
            return fclose($handle);
        }
        return false;
    }
    /**
     * Obtiene una ruta para el archivo de idioma solicitado
     * 
     * @param string $lang
     * @param string $locale
     * @return URI
     */
    private static final function getTranslationPath( $locale ){
        return sprintf( '%slanguages/%s-%s.cfg',
                MNK__TRIPMAN__DIR,
                TripManager::PLUGIN_DOMAIN ,
                $locale );
    }
    /**
     * Días de la semana
     * @param int $day
     * @return string
     */
    public final function displayWeekDayShort( $day ){
        
        $weekDay = $this->displayWeekDay($day);
        
        return substr($weekDay, 0, 3 );
    }
    /**
     * Días de la semana
     * @param int $day
     * @return string
     */
    public final function displayWeekDayCap( $day ){
        
        $weekDay = $this->displayWeekDay($day);
        
        return substr($weekDay, 0, 1 );
    }
    /**
     * Muestra el idioma indicado
     * @param string $language
     * @return string
     */
    public static final function displayLanguage( $language ){
        
        $list = self::listLanguages();
        
        return isset($list[$language]) ? $list[$language] : $language;
    }
    /**
     * Parsea el ID del post relacionado para las traducciones
     * @param int $post_id ID del post a traducir
     * @param int $get_original Devuelve el ID de post original a partir de una traducción
     * @return int Retorna la traducción del ID proveido, o en su caso, el ID de la traducción del idioma original
     */
    public static final function parseTranslationId( $post_id, $get_original = false ){

        $apply_translations = TripManager::getOption(
                'tripman_post_translation',
                TripManager::PLUGIN_OPTION_DISABLED ) === TripManager::PLUGIN_OPTION_ENABLED;

        if( !$apply_translations ){ return $post_id; }
        
        if( $post_id > 0 && function_exists('wpml_object_id_filter')){

            return $get_original ?
                wpml_object_id_filter($post_id,'page' ,true , 'es' ) :
                wpml_object_id_filter($post_id,'page' ,true ) ;
        }

        return $post_id;
    }
    /**
     * Lista los códigos de lennguage soportados (es, en, fr ...)
     * @return string
     */
    public static final function listLanguageCodes(){
        
        $langCodes = array();
        
        foreach( array_keys( self::listLanguages() ) as $lang_id ){
            $langCodes[] = substr($lang_id, 0,2);
        }
        
        return $langCodes;
    }
    /**
     * Lista de idiomas con sus respectivas etiquetas
     * @param boolean $valuesOnly Extrae solo los valores
     * @return array
     */
    public static final function listLanguages( $valuesOnly = FALSE ){
        
        $languages = array(
            'es_ES' => self::__('Español'),
            //'en_GB' => self::__('Ingl&eacute;s (Reino Unido)'),
            'en_US' => self::__('Ingl&eacute;s (Estados Unidos)'),
            'fr_FR' => self::__('Franc&eacute;s'),
            //...
        );
        
        return $valuesOnly ? array_keys($languages) : $languages;
    }
    /**
     * Lista los meses del año
     * @return array
     */
    public final function listMonths(){
        
        $months = array();
        
        for( $m = 1 ; $m <= 12 ; $m++ ){
            $months[$m] = $this->displayMonth($m);
        }
        
        return $months;
    }
    /**
     * Lista los días de la semana
     * @return array
     */
    public final function listWeekDays(){
        
        $days = array();
        
        for( $d = 0 ; $d < 7 ; $d++ ){
            $days[$d] = $this->displayWeekDay($d);
        }
        
        return $days;
    }
    /**
     * Retorna el idioma acutal del sitio desde la función get_locale()
     * @param boolean $short Permite cambiar el formato de salida del idioma a codigo_localizacion (es-ES => es_es)
     * @return string
     */
    public static final function getLocale( $short = false ){
        
        $locale = get_locale();
        
        if( $short ){

            return substr($locale, 0,2);
        } 
        
        return $locale;
    }
    /**
     * Lista los gestores de idiomas disponibles en el sistema para facilitar las traducciones
     * de modelos y plantillas que requieran un idioma diferente al cargado actualmente en el sistema
     * @return \TripManStringProvider[]
     */
    public final function listTranslations(){
        
        return array();
    }
    /**
     * Recorta las cadenas de texto en functión de la longitud indicada, controlando
     * el desplazamiento para casos donde haya carácteres especiales codificados eh HTML
     * &aacute;
     * 
     * @param string $string
     * @param int $length
     * @return string
     */
    public static final function shorten( $string, $length = 0 ){
        
        if ($length) {

            $pos = strpos( substr($string,0,$length)  , '&');

            if( $pos !== false ){

                $length = strpos($string, ';', $pos );

            }

            return substr($string, 0, $length);
        }
        
        return $string;
    }
}