<?php namespace CODERS\Framework\Views;

defined('ABSPATH') or die;

/**
 * 
 */
 class DocumentRender extends Renderer{
    /**
     * @var URL
     */
    const GOOGLE_FONTS_URL = 'https://fonts.googleapis.com/css?family';

    /**
     * @var array Scripts del componente
     */
    private $_scripts = array();
    /**
     * @var array Estilos del componente
     */
    private $_styles = array();
    /**
     * @var array Links del componente
     */
    private $_links = array();
    /**
     * @var array Metas adicionales del componente
     */
    private $_metas = array();
    /**
     * @var array List all body classes here
     */
    private $_classes = array();
    /**
     * Layout and context to display the view
     * @var string
     */
    private $_layout = 'default';
    private $_context;
    private $_title = '';
    /**
     * @param \CodersApp $app
     */
    function __construct( ) {
        
        $this->__registerAssets( );
        
        //$this->registerClass( array( $appName(),'app-key-'.$appKey) );
    }
    /**
     * 
     * @param string $name
     * @return string|html
     */
    public function __get($name) {
        
        if( $name === 'display_logo' ){
            
            return $this->renderLogo();
        }
        else{
            return parent::__get($name);
        }
    }
    /**
     * Inicializa las dependencias del componente
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function __registerAssets(  ){

        $metas = $this->_metas;
        $links = $this->_links;
        $styles = $this->_styles;
        $scripts = $this->_scripts;
        //public metas and linnks
        if (!is_admin() && class_exists('\CODERS\Framework\Views\HTML')) {

            add_action('wp_head', function() use( $metas, $links ) {

                foreach ($metas as $meta_id => $atts) {

                    print \CODERS\Framework\Views\HTML::meta($atts, $meta_id);
                }

                foreach ($links as $link_id => $atts) {

                    print \CODERS\Framework\Views\HTML::link(
                                    $atts['href'], $atts['type'], 
                                    array_merge($atts, array('id' => $link_id)));
                }
            });
        }
        //styles
        add_action( is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts', function() use( $styles ) {

            foreach ($styles as $style_id => $url) {

                wp_enqueue_style($style_id, $url);
            }
        });
        //Scripts
        add_action( is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts' , function() use( $scripts ) {

            foreach ($scripts as $script_id => $content) {

                if (isset($content['deps'])) {
                    wp_enqueue_script($script_id, $content['url'], $content['deps'], false, TRUE);
                } else {
                    wp_enqueue_script(
                            $script_id, $content['url'], array(), false, TRUE);
                }
            }
        });

        return $this;
    }
    /**
     * 
     * @param string $input
     * @return boolean
     */
    protected function containsUrl( $input ){
        
        return preg_match('/^(http|https):\/\//',$input) > 0;
    }
    /**
     * @param string $classes
     * @return \CODERS\Framework\Views\DocumentRender
     */
    protected function registerClass( $classes ){
        
        if(!is_array($classes)){
            $classes = explode(' ', $classes);
        }
        
        foreach( $classes as $cls ){
            if( !in_array($cls, $this->_classes)){
                $this->_classes[] = $cls;
            }
        }
        
        return $this;
    }
    /**
     * Registra un meta en la cabecera
     * @param string $meta_id
     * @param array $attributes
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function registerMeta( $meta_id , array $attributes ){
        
        if( !isset( $this->_metas[ $meta_id ] ) ){
            $this->_metas[$meta_id] = $attributes;
        }
        
        return $this;
    }
    /**
     * Registra un link en la cabecera
     * @param string $link_id
     * @param string $link_url
     * @param array $attributes
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function registerLink( $link_id , $link_url , array $attributes = null ){
        
        if( !isset( $this->_links[ $link_id ] ) ){
            
            if(is_null($attributes)){
                $attributes[ 'href' ] = $link_url;
            }
            else{
                $attributes[ 'href' ] = $link_url;
            }
            
            $this->_links[$link_id] = $attributes;
        }
        
        return $this;
    }
    /**
     * Registra un estilo
     * @param string $style_id
     * @param string $style_url
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function registerStyle( $style_id , $style_url ){
        
        if(strlen($style_url)){

            if( !isset( $this->_styles[ $style_id ] ) ){

                if( !$this->containsUrl($style_url) ){

                    $style_url = $this->getLocalStyleUrl($style_url);
                }

                $this->_styles[$style_id] = $style_url;
            }
        }
        else{
            /**
             * @todo WARNING!!!!
             * no se ha encontrado el recurso CSS definido!!!! anotar en algún log
             */
        }
        
        return $this;
    }
    /**
     * Registra un script
     * @param string $script_id
     * @param string $script_url
     * @param mixed $deps Dependencias del script
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function registerScript( $script_id , $script_url , $deps = null ){
        
        if( !isset( $this->_scripts[ $script_id ] ) ){
            
            if( !$this->containsUrl($script_url) ){
                
                $script_url = $this->getLocalScriptUrl($script_url);
            }

            $this->_scripts[$script_id] = array( 'url' => $script_url );
            
            if( !is_null($deps)){
                $this->_scripts[$script_id]['deps'] = !is_array($deps) ? explode( ',', $deps ) : $deps;
            }
        }
        
        return $this;
    }
    /**
     * Registra una fuente de Google Fonts
     * @param string $font
     * @param mixed $weight
     */
    protected final function registerGoogleFont( $font , $weight = null ){
        
        $font_id = 'font-' . preg_replace( '/ /' , '-' , strtolower($font));

        $font_url = self::GOOGLE_FONTS_URL . '=' . $font ;
        
        if( !is_null($weight)){

            if( !is_array($weight)){
 
                $weight = explode( ',' , $weight );
            }
            
            $font_url .= ':' . implode(',', $weight);
        }
        
        return $this->registerStyle( $font_id, $font_url );
    }
    /**
     * 
     * @return string
     */
    protected function renderLogo(){
        
        //métodos de retorno del bloque como texto html
        return function_exists('get_custom_logo') ? get_custom_logo() :
                sprintf('<a class="theme-logo" href="%s" target="_self">%s</a>',
                    get_site_url(),
                    get_bloginfo('name'));
    }
    /**
     * @return \CODERS\Framework\Views\DocumentRenderer
     */
    protected function renderHeader(){
        
        wp_head();
        
        printf('<body class="%s">' , implode(' ', $this->mergeBodyClasses()));
        
        return $this;
    }
    /**
     * 
     * @return \CODERS\Framework\Views\DocumentRenderer
     */
    protected function renderFooter(){
        
        wp_footer();
        
        printf('</body>');
        
        return $this;
    }
    /**
     * @return array
     */
    protected function mergeBodyClasses(){
        
        $classes = get_body_class();

        foreach( $this->_classes as $cls ){
            if( !in_array($cls, $classes)){
                $classes[] = $cls;
            }
        }
        
        return $classes;
    }
    /**
     * @return \CODERS\Framework\Views\DocumentRenderer
     */
    protected function renderContent(){
        
        //$layout = $this->getLayout($this->_layout);
        $layout = $this->getView($this->_layout, 'layout');
        
        if(file_exists($layout)){

            require $layout;
        }
        else{
            printf('<h1> LAYOUT %s NOT FOUND </h1>',$layout);
            printf('<!-- LAYOUT %s NOT FOUND -->',$layout);
        }

        return $this;
    }
    /**
     * @return URL Url de desconexión de la sesión de WP
     */
    public static final function renderLogOut(){
        return wp_logout_url( site_url() );
    }
    /**
     * Retorna la ruta URI del layout de la vista seleccionada o devuelve nulo si no existe
     * @param string $layout
     * @return string | boolean
     */
    protected final function getLayout( ){
        
        $app = \CodersApp::current();
        
        $layout = strpos($this->_layout,'.') !== FALSE ?
                explode('.', $this->_layout) :
                array( 'public' , $this->_layout );
         
        if( $app !== FALSE ){
            return sprintf('%s/modules/%s/views/layouts/%s.layout.php',
                    $app->appPath(),
                    $layout[0],
                    $layout[1]);
        }
        
        return FALSE;
    }
    /**
     * @return string
     */
    protected function getContext(){ return $this->_context; }
    /**
     * @return string
     */
    protected function getTitle(){ return $this->_title; }
    /**
     * @param string $layout
     * @param string $context
     * @param string $title
     * @return \CODERS\Framework\Views\DocumentRender
     */
    public function setLayout( $layout = 'public.default' , $context = 'main' , $title = '' ){
        
        $this->_layout = strpos($layout, '.') === FALSE ? 'public.'. $layout : $layout;
        
        $this->_context = $context;
        
        $this->_title = $title;
        
        return $this;
    }
    /**
     * 
     * @return \CODERS\Framework\Views\DocumentRenderer
     */
    public function display( $view = 'default' ) {

        //HEADER & OPEN DOCUMENT
        return $this->renderHeader()
                ->renderContent()
                ->renderFooter();
    }
}



