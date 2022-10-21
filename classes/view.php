<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

//use CODERS\Framework\Dictionary;
/**
 * 
 */
abstract class View{
    /**
     * @var \CODERS\Framework\Model
     */
    private $_model = NULL;
    private $_layout = 'default';
    private $_endpoint = array();
    
    private $_strings = array();
    
    private $_activeForm = '';

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
    //private $_styles = array();
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
    private $_classes = array('coders-framework');

    /**
     * @param string $route
     */
    protected function __construct( $route ) {

        $this->_endpoint = explode('.', $route);
    }
    /**
     * @return String
     */
    public function __toString() {
        return $this->endpoint(TRUE);
    }
    /**
     * @return boolean
     */
    protected final function __debug(){ return \CodersApp::debug(); }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        switch( true ){
            case $name === 'debug':
                return $this->__debug();
            case preg_match('/^input_/', $name):
                return $this->__input(substr($name, 6));
            case preg_match(  '/^list_[a-z_]*_options$/' , $name ):
                return $this->__options(substr($name, 5, strlen($name) - 5 - 8 ) );
            case preg_match(  '/^list_/' , $name ):
                return $this->__list(substr($name, 5));
            case preg_match(  '/^value_/' , $name ):
                return $this->value(substr($name, 6));
            case preg_match(  '/^import_/' , $name ):
                return $this->__import(substr($name, 7));
            case preg_match(  '/^display_/' , $name ):
                return $this->__display(substr($name, 8));
            case preg_match(  '/^label_/' , $name ):
                return $this->label(substr($name, 6));
            case preg_match(  '/^get_/' , $name ):
                $get = substr($name, 6);
                return $this->has($name) ? $this->_model->get($get) : '';
        }
        return '';
    }
    
    public function __call($name, $arguments) {
        switch( true ){
            case $name === 'debug':
                return $this->__debug();
            case $name === 'open_form':
                return $this->openForm( $arguments );
            case $name === 'close_form':
                return $this->closeForm();
            case $name === 'html':
                return count($arguments) > 1 ? Renderer::html(
                            $arguments[0],
                            is_array($arguments[1]) ? $arguments[1] : array(),
                            count($arguments) > 2 ? $arguments[2] : null ) :
                            '<!-- empty html -->';
            case preg_match('/^submit_/', $name):
                $action = substr($name, 7);
                $content = count($arguments) && is_string($arguments[0])? $arguments[0] : $this->string( $name);
                $attributes = count($arguments) > 1 ? $arguments[1] : $arguments[0];
                return $this->__submit($action,$content, is_array( $attributes) ? $attributes : array() );
            case preg_match('/^button_/', $name):
                $action = substr($name, 7);
                $content = count($arguments) && is_string($arguments[0])? $arguments[0] : $this->string( $name);
                $attributes = count($arguments) > 1 ? $arguments[1] : $arguments[0];
                return $this->__button($action,$content, is_array( $attributes) ? $attributes : array() );
            case preg_match('/^action_/', $name):
                $action = substr($name, 7);
                if( count( $arguments )){
                    return $this->__action(
                            $arguments[0],
                            count($arguments) > 1 ? $arguments[1] : $this->label($action),
                            array('class'=>$action));
                }
                return sprintf('<!-- invalid action [%s]-->',$action);
            case preg_match('/^input_/', $name):
                return $this->__input(substr($name, 6));
            case preg_match(  '/^list_[a-z_]*_options$/' , $name ):
                return $this->__options(substr($name, 5, strlen($name) - 5 - 8 ) );
            case preg_match(  '/^list_/' , $name ):
                return $this->__list(substr($name, 5));
            case preg_match(  '/^value_/' , $name ):
                return $this->value(substr($name, 6));
            case preg_match(  '/^import_/' , $name ):
                return $this->__import(substr($name, 7));
            case preg_match(  '/^display_/' , $name ):
                return $this->__display(substr($name, 8));
            case preg_match(  '/^label_/' , $name ):
                return $this->label(substr($name, 6));
            case preg_match(  '/^get_/' , $name ):
                $get = substr($name, 6);
                return $this->has($name) ? $this->_model->get($get) : '';
        }
        return '';
    }
    /**
     * @param string $name
     * @param string $content
     * @param array $args
     * @return String
     */
    protected function __submit( $name , $content = '' , array $args = array()){

        $args['class'] = array_key_exists('class',$args) ?
            $args['class'] :
                'button ' . preg_replace('/\./', '-', $name);
        
        return Renderer::submit($name, $this->string(is_string($content) ? $content : 'submit_'.$name) , $args );
    }
    /**
     * @param string $name
     * @param string $content
     * @param array $args
     * @return String
     */
    protected function __button( $name , $content = '', array $args = array()){
        
        $args['class'] = array_key_exists('class',$args) ?
            $args['class'] :
                'button ' . preg_replace('/\./', '-', $name);

        return Renderer::button($name, $this->string( is_string($content) ? $content : 'button_'.$name), $args );
    }
    /**
     * @param string $action
     * @param string $label
     * @param array $args
     * @return String
     */
    protected function __action( $action , $label , array $args = array()){
        
        $url = Request::createLink($action, $args );
        
        return Renderer::action($url, $label , array('class'=> preg_replace('/\./', '-', $action)));
    }
    /**
     * List to override and add custom inputs
     * @param string $name
     * @param string $type
     * @return String|HTML
     */
    protected function __input( $name , $type = '' ){
        
        if(strlen($type) === 0 ){
            $type = $this->type($name);
        }
        
        switch ( $type ) {
            case Model::TYPE_DROPDOWN:
                return Renderer::inputDropDown(
                                $name, $this->__options($name),
                                $this->value($name),
                                array('class' => 'form-input'));
            case Model::TYPE_LIST:
                return Renderer::inputList(
                                $name, $this->__options($name),
                                $this->value($name),
                                array('class' => 'form-input'));
            case Model::TYPE_OPTION:
                return Renderer::inputOptionList(
                                $name, $this->__options($name),
                                $this->value($name),
                                array('class' => 'form-input'));
            case Model::TYPE_CHECKBOX:
                return Renderer::inputCheckBox($name,
                                $this->value($name), 1,
                                array('class' => 'form-input'));
            case Model::TYPE_NUMBER:
            case Model::TYPE_FLOAT:
                //case Model::TYPE_PRICE:
                return Renderer::inputNumber(
                                $name, $this->value($name),
                                array('class' => 'form-input'));
            case Model::TYPE_FILE:
                return Renderer::inputFile(
                                $name,
                                array('class' => 'form-input'));
            case Model::TYPE_DATE:
            case Model::TYPE_DATETIME:
                return Renderer::inputDate(
                                $name, $this->value($name),
                                array('class' => 'form-input'));
            case Model::TYPE_EMAIL:
                return Renderer::inputEmail(
                                $name, $this->value($name),
                                array('class' => 'form-input'));
            //case Model::TYPE_TELEPHONE:
            //    return Renderer::inputTelephone(
            //            $name, $this->value($name),
            //            array('class' => 'form-input'));
            case Model::TYPE_PASSWORD:
                return Renderer::inputPassword(
                                $name, array('class' => 'form-input'));
            case Model::TYPE_TEXTAREA:
                return Renderer::inputTextArea(
                                $name, $this->value($name),
                                array('cass' => 'form-input'));
            case Model::TYPE_TEXT:
                return Renderer::inputText(
                                $name, $this->value($name),
                                array('class' => 'form-input',
                                    'placeholder' => $this->meta($name, 'placeholder')));
            default:
                return $this->__debug() ?
                    sprintf('<span class="error ivalid">%s not found</span>',$name):
                    sprintf('<!-- [%s] not found -->',$name);
        }
    }
    /**
     * @param string|array $class
     * @return \CODERS\Framework\View
     */
    protected function addBodyClass( $class ){
        if( !is_array($class)){
            $class = explode(' ', $class);
        }
        $this->_classes = array_merge($this->_classes,$class);
        return $this;
    }
    /**
     * @param array $content
     * @return \CODERS\Framework\View
     */
    protected function addMeta( array $content ){
        $this->_metas[] = $content;
        return $this;
    }
    /**
     * @param string $key
     * @param string $string
     * @return \CODERS\Framework\View
     */
    protected function addString( $key , $string ){
        if(is_string($key) && is_string($string)){
            $this->_strings[$key] = $string;
        }
        return $this;
    }
    /**
     * @param string $asset
     * @return String|URL
     */
    protected function contentUrl( $asset ){
        //$path = explode('/wp-content/plugins/',  $this->__path() )[1];
        return sprintf('%s/contents/%s',
                plugins_url( $this->endpoint() ),
                $asset);
    }
    /**
     * @param string $link_id
     * @param string $url
     * @param string $type
     * @return \CODERS\Framework\View
     */
    protected function addLink( $link_id , $url , $type , array $atts = array()){
        if( !array_key_exists($link_id, $this->_links)){
            $atts['id'] = $link_id;
            $atts['type'] = $type;
            $atts['href'] = $url;
            $this->_links[$link_id] = $atts;
        }
        return $this;
    }
    /**
     * @param string $style_id
     * @param string $url
     * @param string $type text/css default
     * @return \CODERS\Framework\View
     */
    protected function addStyle( $style_id , $url , $type = 'text/css'){
        return $this->addLink( $style_id, $url, $type, array('rel'=>'stylesheet'));
    }
    /**
     * @param string $script_id
     * @param string $url
     * @return \CODERS\Framework\View
     */
    protected function addScript( $script_id , $url = '', $dependencies = '' , array $localized = array() ){
        if( !array_key_exists($script_id, $this->_scripts)){
            $atts = array(
                'id' => $script_id,
                'src' => $url,
                'type' => 'text/javascript', //parameter this?
            );
            if(strlen($dependencies)){
                $atts['deps'] = $dependencies;
            }
            if( count( $localized )){
                $atts['localized'] = $localized;
            }
            $this->_scripts[$script_id] = $atts;
        }
        return $this;
    }
    /**
     * @param array $args name action method enctype
     * @return String
     */
    protected function openForm( array $args ){
        if( count($args) && $this->_activeForm  === '' ){
            $name = $args[0];
            $action = count($args) > 1 ? $args[1] : filter_input(INPUT_SERVER, 'PHP_SELF' );
            $method = count($args) > 2 ? $args[2] : 'post';
            $type = count($args) > 3 ? $args[3] : Renderer::FORM_TYPE_PLAIN;
            
            $this->_activeForm = $name;
            return sprintf('<!-- opening form [%s] --><form name="%s" method="%s" action="%s" encType="%s">',
                    $name, $name, $method, $action, $type);
        } 
        return sprintf('<!-- form [%s] is open -->',$this->_activeForm);
    }
    /**
     * @return string
     */
    protected function closeForm(){
        if( strlen($this->_activeForm)){
            $form = $this->_activeForm;
            $this->_activeForm = '';
            return sprintf('</form><!-- form [%s] closed -->',$form);
        }
        return '<!-- no active form to close -->';
    }
    /**
     * @param string $name
     * @return string
     */
    protected function value( $name ){
        return $this->has($name) ? $this->_model->$name : '';
    }
    /**
     * @param string $name
     * @param string $meta
     * @return string
     */
    protected function meta( $name , $meta ){

        return $this->has($name) ?
                $this->_model->meta($name, $meta ):
                sprintf('<!-- DATA %s NOT FOUND -->',$name);
    }
    /**
     * @return string
     */
    protected function endpoint( $full = FALSE ){
        return $full ? implode('.', $this->_endpoint) : $this->_endpoint[0];
    }
    /**
     * @param string $name
     * @return array
     */
    protected function __options( $name ){
        
        return $this->has($name) ? $this->_model->options($name) : array();
    }
    /**
     * @param string $list
     * @return array
     */
    protected function __list( $list ){
        
        $call = 'list' . ucfirst($list);
        
        if(method_exists($this, $call)){
            return $this->$call( );
        }
        
        return $this->hasModel() ? $this->_model->list($list) : array();
    }
    /**
     * @param string $name
     * @return string
     */
    protected function label( $name ){
        return $this->string($name);
        return $this->has($name) ?
                $this->_model->meta($name,'label',$name) :
                        $this->string($name);
    }
    /**
     * @param string $key
     * @return string
     */
    protected function string( $key ){
        return \CodersApp::__($key);
        //return array_key_exists($key, $this->_strings) ? $this->_strings[$key] : $key;
    }

    
    /**
     * @param string $element
     * @return boolean
     */
    public function has( $element ){
        return $this->hasModel() && $this->_model->has($element);
    }
    /**
     * @return boolean
     */
    public function hasModel(){
        return !is_null($this->_model);
    }
    /**
     * @param string $name
     * @return string
     */
    public function type( $name ){
        return $this->has($name) ? $this->_model->type($name) : '';
    }

        
    /**
     * @param \CODERS\Framework\Model $model
     * @return \CODERS\Framework\View
     */
    public function setModel( Model $model = null ){
        if(!is_null($model) && is_null($this->_model)){
            $this->_model = $model;
        }
        return $this;
    }
    /**
     * @param string $layout
     * @return \CODERS\Framework\View
     */
    public function setLayout( $layout ){
        $this->_layout = $layout;
        return $this;
    }
    /**
     * @return string |PATH
     */
    protected function __path(){
        // within either sub or parent class in a static method
        $ref = new \ReflectionClass(get_called_class());
        return preg_replace('/\\\\/', '/',  dirname( $ref->getFileName() ) );
        //return preg_replace('/\\\\/', '/', __DIR__);
    }
    /**
     * @param string $view
     * @param string $type
     * @return string
     */
    protected function __display( $view ){
        
        $path = sprintf('%s/html/%s.php',$this->__path(),$view);
        
        if(file_exists($path)){
            require $path;
        }
        elseif(\CodersApp::debug()){
            //printf('<!-- html: %s.%s not found -->',$this->endpoint(), $view);
            printf('<p class="info">html layout not found [%s]</p>',$path);
        }
        else{
            printf('<!-- html: %s not found -->',$path);
        }
    }
    /**
     * Setup all view contents
     * @return \CODERS\Framework\View
     */
    protected function prepare(){
        
        $this->addMeta( array( 'charset'=> get_bloginfo('charset')));
        $this->addMeta(array('name'=>'viewport','content' => 'width=device-width, initial-scale=1.0'));
        $this->addBodyClass(preg_replace('/\./', '-', implode(' ', $this->_endpoint)));
        
        $metas = $this->_metas;
        $links = $this->_links;
        $scripts = $this->_scripts;

        add_action('wp_head', function() use( $metas, $links ) {
            foreach ($metas as $content) {
                print \CODERS\Framework\Renderer::html('meta', $content);
            }
            foreach ($links as $link_id => $content) {
                $content['id'] = $link_id;
                print \CODERS\Framework\Renderer::html('link', $content);
            }
        });
        add_action( 'wp_enqueue_scripts' , function() use( $scripts ) {
            foreach ($scripts as $script_id => $content) {
                if(strlen($content['src'])) {
                    wp_enqueue_script(
                            $script_id, $content['src'],
                            strlen($content['deps']) ? explode(' ', $content['deps']) : array(),
                            false, TRUE);
                }
                else{
                    wp_enqueue_script( $script_id );
                }
                if( isset( $content['localized'])){
                    wp_localize_script($script_id,
                            preg_replace('/\s/', '', ucwords(preg_replace('/[\-_]/', ' ', $script_id ) )  ),
                            $content['localized']);
                }
            }
        });
        
        return $this;
    }
    /**
     * Start the rendering
     * @return \CODERS\Framework\View
     */
    protected function renderHeader( ){
        if(is_admin()){
            printf('<!-- [%s] opener --><div class="wrap"><h1>%s</h1>', $this->endpoint(true), get_admin_page_title());
        }
        else{
            printf('<html %s ><head>',get_language_attributes());
            wp_head();
            printf('</head><body class="%s">', implode(' ',  get_body_class(implode(' ', $this->_classes))));
        }
        return $this;
    }
    /**
     * Render the application content
     * @return \CODERS\Framework\View
     */
    protected function renderContent(){
        
        printf('<div class="container %s %s">',$this->endpoint(),$this->_layout);
        
        if( $this->__debug() && !$this->hasModel()){
            printf('<!--No model set for view [%s] -->',$this->endpoint(true));
        }
        
        //override here
        $this->__display($this->_layout);
        
        print('</div>');
        
        return $this;
    }
    /**
     * Stop and complete the rendering
     * @return \CODERS\Framework\View
     */
    protected function renderFooter(){
        if(is_admin()){
            //finalize the admin view setup
            printf('</div><!-- [%s] closer -->',$this->endpoint(true) );
        }
        else{
            wp_footer();
            print '</body></html>';            
        }
        return $this;
    }

        /**
     * @return \CODERS\Framework\View
     */
    public function show(){

        $this->prepare()
                ->renderHeader()
                ->renderContent()
                ->renderFooter();

        return $this;
    }
    
    /**
     * @param array $route
     * @return string
     */
    private static final function __modClass( array $route ){
        //$route = explode('.', $route);
        return count($route) > 1 ?
                    sprintf('\CODERS\%s\%sView',
                            \CodersApp::Class($route[0]),
                            \CodersApp::Class($route[1])) :
                    sprintf('\CODERS\Framework\Views\%s',
                            \CodersApp::Class($route[0]));
    }
    /**
     * @param array $route
     * @return String|PAth
     */
    private static final function __modPath( array $route ){
        return count($route) > 1 ?
                    sprintf('%s/components/views/%s/view.php',
                            \CodersApp::path($route[0]),
                            $route[1]) :
                    sprintf('%s/components/views/%s/view.php',
                            \CodersApp::path(),
                            $route[0]);
    }
    /**
     * @param string $route
     * @return boolean|\CODERS\Framework\Views\Renderer
     */
    public static final function create( $route ){
        
        //$package = self::package($model);
        $namespace = explode('.', $route);
        $path = self::__modPath($namespace);
        $class = self::__modClass($namespace);
        try{
            if(file_exists($path)){
                require_once $path;
                if(class_exists($class) && is_subclass_of($class, self::class)){
                    return new $class( $route );
                }
                else{
                    throw new \Exception(sprintf('Invalid View [%s]',$class) );
                }
            }
            else{
                throw new \Exception(sprintf('Invalid path [%s]',$path) );
            }
        }
        catch (\Exception $ex) {
            \CodersApp::notice($ex->getMessage());
        }
        
        return null;
    }
}
/**
 * 
 */
class Renderer{
    
    const FORM_TYPE_DATA = 'multipart/form-data';
    const FORM_TYPE_ENCODED = 'application/x-www-form-urlencoded';
    const FORM_TYPE_PLAIN = 'text/plain';
    
    /**
     * @param string $string
     * @return string
     */
    public static final function __( $string ){
        return \CodersApp::__($string);
    }

    /**
     * @param string $name
     * @param string $action
     * @param string $type
     * @param Mixed $content
     * @return HTML | String
     */
    public static function form( $name , $action = '' , $type = self::FORM_TYPE_PLAIN , $content = null ){
        
        return self::html('form', array(
            'name' => $name,
            'action' => strlen($action) ? $action : filter_input(INPUT_SERVER, 'PHP_SELF'),
            'encType' => $type,
        ), $content);
    }

    /**
     * @param string $tag
     * @param mixed $attributes
     * @param mixed $content
     * @return String|HTML HTML output
     */
    public static function html( $tag, $attributes = array( ), $content = null ){
        if( isset( $attributes['class'])){
            $attributes['class'] = is_array($attributes['class']) ?
                    implode(' ', $attributes['class']) :
                    $attributes['class'];
        }
        $serialized = array();
        foreach( $attributes as $att => $val ){
            $serialized[] = sprintf('%s="%s"',$att,$val);
        }
        if(!is_null($content) ){
            if(is_object($content)){
                $content = strval($content);
            }
            elseif(is_array($content)){
                $content = implode(' ', $content);
            }
            return sprintf('<%s %s>%s</%s>' , $tag ,
                    implode(' ', $serialized) , strval( $content ) ,
                    $tag);
        }
        return sprintf('<%s %s />' , $tag , implode(' ', $serialized ) );
    }
    
    /**
     * <meta />
     * @param array $attributes
     * @return HTML
     */
    public static function meta( array $attributes ){
        
        foreach( $attributes as $attribute => $value ){
            if(is_array($value)){
                $valueInput = array();
                foreach( $value as $valueVar => $valueVal ){
                    $valueInput[] = sprintf('%s=%s',$valueVar,$valueVal);
                }
                $attributes[] = sprintf('%s="%s"',$attribute, implode(', ', $valueInput) );
            }
            else{
                $attributes[] = sprintf('%s="%s"',$attribute,$value);
            }
        }
        
        return self::html('meta', $attributes );
    }
    /**
     * <link />
     * @param URL $url
     * @param string $type
     * @param array $attributes
     * @return HTML
     */
    public static final function link( $url , $type , array $attributes = array( ) ){
        $attributes[ 'href' ] = $url;
        $attributes[ 'type' ] = $type;
        return self::html( 'link', $attributes );
    }
    /**
     * <a href />
     * @param type $url
     * @param type $label
     * @param array $atts
     * @return HTML
     */
    public static function action( $url , $label , array $atts = array( ) ){
        
        $atts['href'] = $url;
        
        if( !isset($atts['target'])){
            $atts['target'] = '_self';
        }
        
        return self::html('a', $atts, $label);
    }
    /**
     * <button type="submit"></button>
     * @param string $name
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    public static final function submit( $name, $content = '' , array $atts = array()){
        
        $atts['type'] = 'submit';
        if(!array_key_exists('value', $atts)){
            $atts['value'] = $name;
        }
        
        return self::button($name , $content, $atts);
    }
    /**
     * <button></button>
     * @param string $name
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    public static function button( $name , $content = '' , array $atts = array( ) ){
        
        $atts['name'] = $name;
        
        if( !array_key_exists('type', $atts)){
            $atts['type'] = 'button';
        }
        
        return self::html('button', $atts, strlen($content) ? $content : self::__($name));
    }
    /**
     * <ul></ul>
     * @param array $content
     * @param array $atts
     * @param mixed $itemClass
     * @return HTML
     */
    public static function listUnsorted( array $content , array $atts , $itemClass = '' ){
        
        $collection = array();
        
        foreach( $content as  $item ){
            $collection[] = !empty($itemClass) ?
                    self::html('li', array('class'=>$itemClass) , $item ) :
                    self::html('li', array(), $item ) ;
        }
        
        return self::html( 'ul' , $atts ,  $collection );
    }
    /**
     * <ol></ol>
     * @param array $content
     * @param array $atts
     * @param mixed $itemClass
     * @return HTML
     */
    public static function listSorted( array $content , array $atts , $itemClass = '' ){
        
        $collection = array();
        
        foreach( $content as  $item ){
            $collection[] = !empty($itemClass) ?
                    self::html('li', array('class'=>$itemClass) , $item ) :
                    self::html('li', array(), $item ) ;
        }
        
        return self::html( 'ol' , $atts ,  $collection );
    }
    /**
     * <span></span>
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    public static final function span( $content , $atts = array( ) ){
        return self::html('span', $atts , $content );
    }
    /**
     * <img src />
     * @param string/URL $src
     * @param array $atts
     * @return HTML
     */
    public static final function image( $src , array $atts = array( ) ){
        
        $atts['src'] = $src;
        
        return self::html('img', $atts);
    }
    /**
     * <label></label>
     * @param string $input
     * @param string $text
     * @param mixed $class
     * @return HTML
     */
    public static function label( $text , array $atts = array() ){

        return self::html('label', $atts, $text);
    }
    /**
     * <span class="price" />
     * @param string $name
     * @param int $value
     * @param string $coin
     * @return HTML
     */
    public static function price( $name, $value = 0.0, $coin = '&eur', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        
        return self::html('span',
                $atts ,
                $value . self::html('span', array('class'=>'coin'), $coin));
    }
    /**
     * <input type="number" />
     * @param String $name
     * @param int $value
     * @param array $atts
     * @return HTML
     */
    public static function inputNumber( $name, $value = 0, array $atts = array() ){
        
        if( !isset($atts['min'])){ $atts['min'] = 0; }

        if( !isset($atts['step'])){ $atts['step'] = 1; }
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        
        $atts['name'] = $name;
        
        $atts['value'] = $value;
        
        $atts['type'] = 'number';
        
        return self::html('input', $atts);
    }
    /**
     * <textarea></textarea>
     * @param string $name
     * @param string $value
     * @param array $atts
     */
    public static function inputTextArea( $name, $value = '', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['placeholder'] = array_key_exists('placeholder', $atts)? $atts['placeholder'] : '';

        return self::html('textarea', $atts, $value);
    }
    /**
     * <input type="text" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputText($name, $value = '', array $atts = array() ){
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['placeholder'] = array_key_exists('placeholder', $atts)? $atts['placeholder'] : '';
        $atts['type'] = 'text';
        
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="password" />
     * @param string $name
     * @param array $atts
     * @return HTML
     */
    public static function inputPassword( $name, array $atts = array() ){
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['type'] = 'password';
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="search" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputSearch( $name, $value = '' , array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'search';
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="date" />
     * Versión con jQuery UI
     * <input type="text" class="hasDatepicker" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputDate($name, $value = '', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'date';
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="tel" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputTelephone($name, $value = null, array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'tel';
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="email" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputEmail($name, $value = '', array $atts = array() ){
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'email';
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="checkbox" />
     * @param string $name
     * @param boolean $checked
     * @param array $atts
     * @return HTML
     */
    public static function inputCheckBox( $name, $checked = false , $value = 1, array $atts = array() ){
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'checkbox';
        if($checked){ $atts['checked'] = 1; }
        return self::html( 'input' , $atts );
    }
    /**
     * Lista de opciones <input type="radio" />
     * @param String $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputOptionList( $name, array $options, $value = null, array $atts = array( ) ){


        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        
        $radioItems = array();
        
        $baseAtts = array( 'type' => 'radio' , 'name' => $name );
        
        if( isset($atts['disabled']) ){
            $baseAtts['disabled'] = 'disabled';
            unset($atts['disabled']);
        }
        
        foreach( $options as $option => $label){
            
            $optionAtts = array_merge($baseAtts,array('value'=>$option));
            
            if( !is_null($value) && $option == $value ){
                $optionAtts['checked'] = 'checked';
            }
            
            $radioItems[ ] = self::html(
                    'li',
                    array(),
                    self::html( 'input', $optionAtts, $label) );
        }
        
        return self::html('ul', $atts, implode('</li><li>',  $radioItems));
    }
    /**
     * <select size="5" />
     * @param string $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputList($name, array $options, $value = null, array $atts = array() ){

        if( !isset($atts['id']) ){
            preg_replace('/-/', '_',  $name );
        }
        
        if( !isset($atts['size'])){
            $atts['size'] = 5;
        }
        
        $atts['id'] = 'id_'.$name;
        
        $atts['name'] = $name;
        
        $items = array();
        
        if( isset($atts['placeholder'])){
            $items[''] = sprintf('- %s -', $atts['placeholder'] );
            unset($atts['placeholder']);
        }

        foreach( $options as $option => $label ){
            $items[] = self::html('option', $option == $value ?
                    array('value'=> $option,'selected'=>'true') :
                    array('value'=>$option),
                $label);
        }
        
        return self::html('select', $atts, $items );
    }
    /**
     * <select size="1" />
     * @param string $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputDropDown($name, array $options, $value = null, array $atts = array() ){
        
        $atts['size'] = 1;
        
        return self::inputList( $name , $options, $value, $atts);
    }
    /**
     * <input type="hidden" />
     * @param string $name
     * @param string $value
     * @return HTML
     */
    public static function inputHidden( $name, $value ){
        
        return self::html('input', array(
            'type' => 'hidden',
            'name' => $name,
            'value' => $value,
        ));
    }
    /**
     * <input type="file" />
     * @param string $name
     * @return HTML
     */
    public static function inputFile( $name , array $atts = array( ) ){
        
        $max_filesize = 'MAX_FILE_SIZE';
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_', $name);
        $atts['name'] = $name;
        $atts['type'] = 'file';
        
        $file_size =  pow(1024, 2);
        
        if( isset($atts[$max_filesize]) ){
            $file_size = $atts[$max_filesize];
            unset($atts[$max_filesize]);
        }
        
        return self::inputHidden( $max_filesize, $file_size ) . self::html('file', $atts );
    }
    /**
     * <button type="*" />
     * @param string $name
     * @param string $value
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    public static function inputButton( $name, $value , $content, array $atts = array( ) ){
        
        $atts['value'] = $value;
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name ) . '_' . $value;
        $atts['name'] = $name;
        if( !isset($atts['type'])){
            $atts['type'] = 'button';
        }
        return self::html('button', $atts, $content);
    }
    /**
     * <button type="submit" />
     * @param string $name
     * @param string $value
     * @param string $label
     * @param array $atts
     * @return HTML
     */
    public static function inputSubmit( $name , $value , $label , array $atts = array( ) ){
        
        return self::inputButton($name,
                $value,
                $label,
                array_merge( $atts , array( 'type'=>'submit' ) ));
    }
}


