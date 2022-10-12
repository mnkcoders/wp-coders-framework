<?php namespace CODERS\Framework\Views;

defined('ABSPATH') or die;

use CODERS\Framework\Dictionary;
/**
 * 
 */
abstract class Renderer extends \CODERS\Framework\Component{
    /**
     * @var \CODERS\Framework\IModel
     */
    private $_model = NULL;

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
     * @param string $appName
     * @param string $module
     */
    protected function __construct( $appName , $module ) {
        
        $this->set('endpoint', $appName)
                ->set('module', $module)
                ->registerAssets();
        
    }
    /**
     * @param string $view
     * @param string $type
     * @return string
     */
    protected function viewPath( $view , $type = 'html' ){
        
        //$path = sprintf('%s/%s/%s.php',$this->getPath(),$type,$view);
        
        $path = sprintf('%smodules/%s/views/%s/%s.php',
                \CodersApp::appRoot($this->get('endpoint')),
                $this->get('module'), $type, $view);
        return $path;
    }
    /**
     * @param string $asset
     * @return URL|String
     */
    protected function assetUrl( $asset ) {
        return sprintf('%s/plugins/%s/modules/%s/assets/%s',
                get_site_url(),
                $this->get('endpoint'),
                $this->get('module'),
                $asset);
    }
    /**
     * @param string $url
     * @return boolean
     */
    private function matchUrl( $url ){
        return preg_match('/^(http|https):\/\//',$url) > 0;
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
    
        switch( true ){
            case preg_match('/^input_/', $name):
                return $this->__input(substr($name, 6));
            case preg_match(  '/^list_[a-z_]*_options$/' , $name ):
                return $this->__options(substr($name, 5, strlen($name) - 5 - 8 ) );
            case preg_match(  '/^list_/' , $name ):
                return $this->__options(substr($name, 5));
            case preg_match(  '/^value_/' , $name ):
                return $this->__value(substr($name, 6));
            case preg_match(  '/^import_/' , $name ):
                return $this->__import(substr($name, 7));
            case preg_match(  '/^display_/' , $name ):
                return $this->__display(substr($name, 8));
            case preg_match(  '/^label_/' , $name ):
                return $this->__label(substr($name, 6));
            case preg_match(  '/^get_/' , $name ):
                $get = substr($name, 6);
                return !is_null($this->_model) && $this->_model->has($get) ?
                    $this->_model->get($get) : '';
        }
        
        return parent::get($name);
    }
    /**
     * @param string $tag
     * @param mixed $attributes
     * @param mixed $content
     * @return String|HTML HTML output
     */
    protected static function __HTML( $tag, $attributes = array( ), $content = NULL ){
        
        if( isset( $attributes['class'])){
            if(is_array($attributes['class'])){
                $attributes['class'] = implode(' ', $attributes['class']);
            }
        }
        
        $serialized = array();
        
        foreach( $attributes as $att => $val ){
        
            $serialized[] = sprintf('%s="%s"',$att,$val);
        }
        
        if( !is_null($content) ){

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
        
        return sprintf('<%s %s />' , $tag , implode(' ', $attributes ) );
    }
    /**
     * @param string $name
     * @return string|HTML
     */
    protected function __input( $name ){
        
        if( !is_null( $this->_model) &&  $this->_model->hasField($name)){
            
            switch( $this->_model->getFieldType($name)){
                case Dictionary::TYPE_DROPDOWN:
                    return self::inputDropDown(
                            $name,
                            $this->__options($name),
                            $this->__value($name),
                            array('class' => 'form-input'));
                case Dictionary::TYPE_DROPDOWN:
                    return self::inputList(
                            $name,
                            $this->__options($name),
                            $this->__value($name),
                            array('class' => 'form-input'));
                case Dictionary::TYPE_OPTION:
                    return self::inputOptionList(
                            $name,
                            $this->__options($name),
                            $this->__value($name),
                            array('class' => 'form-input'));
                case Dictionary::TYPE_USER:
                case Dictionary::TYPE_ID:
                    return '<b>not implemented</b>';
                case Dictionary::TYPE_CHECKBOX:
                    return self::inputCheckBox($name,
                            $this->__value($name),
                            1,
                            array('class'=>'form-input'));
                case Dictionary::TYPE_NUMBER:
                case Dictionary::TYPE_FLOAT:
                case Dictionary::TYPE_PRICE:
                    return self::inputNumber(
                            $name,
                            $this->__value($name),
                            array('class'=>'form-input'));
                case Dictionary::TYPE_FILE:
                    return self::inputFile(
                            $name,
                            array( 'class' => 'form-input' ));
                case Dictionary::TYPE_DATE:
                case Dictionary::TYPE_DATETIME:
                    return self::inputDate(
                            $name,
                            $this->__value($name),
                            array('class'=>'form-input'));
                case Dictionary::TYPE_EMAIL:
                    return self::inputEmail(
                            $name,
                            $this->__value($name),
                            array('class'=>'form-input'));
                case Dictionary::TYPE_TELEPHONE:
                    return self::inputTelephone(
                            $name,
                            $this->__value($name),
                            array('class' => 'form-input'));
                case Dictionary::TYPE_PASSWORD:
                    return self::inputPassword(
                            $name,
                            array('class' => 'form-input' ) );
                case Dictionary::TYPE_TEXTAREA:
                    return  self::inputTextArea(
                            $name,
                            $this->__value($name),
                            array( 'cass' => 'form-input' ) );
                default:
                    return self::inputText(
                            $name,
                            $this->__value($name),
                            array('class'=>'form-input'));
            }
        }

        return sprintf('<!-- INPUT %s NOT FOUND -->',$name);
    }
    /**
     * @param string $name
     * @return string
     */
    protected function __value( $name ){
        
        if( !is_null( $this->_model)){

            return $this->_model->$name;
        }

        return sprintf('<!-- DATA %s NOT FOUND -->',$name);
    }
    /**
     * @param string $field
     * @return array
     */
    protected function __options( $field ){
        
        return !is_null($this->_model) ?
                $this->_model->listOptions($field) :
                array();
    }
    /**
     * @param string $name
     * @return string
     */
    protected function __label( $name ){

        if( !is_null( $this->_model)){
            $meta = $this->_model->getFieldMeta($name);
            return array_key_exists('label', $meta) ? $meta['label'] : $name;
        }

        return $name;
    }
    /**
     * @param string $display
     * @return string|html
     */
    protected function __display( $display ){
        
        if(strlen($display)){
            $callback = sprintf('display%s',$display);

            return method_exists($this, $callback) ?
                    $this->$callback() :
                    sprintf('<!-- display_%s not found -->',$display);

        }
        
        return '<!-- no display defined -->';
    }
    /**
     * 
     * @param string $display
     * @return string
     */
    protected function __import( $display ){
        $path = $this->viewPath($display);
        if(file_exists($path )){
            require $path;
        }
        else{
            return sprintf('<!-- display_%s not found -->',$display);
        }
    }
    /**
     * @return string | HTML
     */
    protected function displayLogo() {

        return function_exists('get_custom_logo') ?
                get_custom_logo() :
                self::__HTML('a', array(
                    'class'=>'theme-logo',
                    'href'=> get_site_url(),
                    'target' => '_self'
                ), get_bloginfo('name'));
    }
    /**
     * @return URL Url de desconexión de la sesión de WP
     */
    public static final function displayLogOut(){
        return wp_logout_url( site_url() );
    }
    /**
     * @return \CODERS\Framework\Views\Renderer
     */
    protected function renderHeader(){
        wp_head();
        printf('<body class="%s">' , implode(' ', $this->mergeBodyClasses()));
        return $this;
    }
    /**
     * @return \CODERS\Framework\Views\Renderer
     */
    protected function renderFooter(){
        wp_footer();
        printf('</body>');
        return $this;
    }
    /**
     * @param strig $layout
     * @return \CODERS\Framework\Views\Renderer
     */
    protected function renderContent( $layout ){
        
        $path = $this->viewPath($layout, 'layout');
        if(file_exists($path)){
            require $path;
        }
        else{
            printf('<!-- LAYOUT %s NOT FOUND -->',$path);
        }

        return $this;
    }
    /**
     * 
     * @return \CODERS\Framework\Views\Renderer
     */
    public function render( $layout ) {

        //HEADER & OPEN DOCUMENT
        return $this->renderHeader()
                ->renderContent( $layout )
                ->renderFooter();
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
     * <meta />
     * @param array $attributes
     * @param string $name
     * @return HTML
     */
    public static function renderMeta( array $attributes , $name = null ){
        
        if( !is_null($name)){

            $attributes['name'] = $name;
        }
        
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
        
        return self::__HTML('meta', $attributes );
    }
    /**
     * <link />
     * @param URL $url
     * @param string $type
     * @param array $attributes
     * @return HTML
     */
    public static final function renderLink( $url , $type , array $attributes = array( ) ){
        
        $attributes[ 'href' ] = $url;
        
        $attributes[ 'type' ] = $type;
        
        return self::__HTML( 'link', $attributes );
    }
    /**
     * <a href />
     * @param type $url
     * @param type $label
     * @param array $atts
     * @return HTML
     */
    protected static function renderAction( $url , $label , array $atts = array( ) ){
        
        $atts['href'] = $url;
        
        if( !isset($atts['target'])){
            $atts['target'] = '_self';
        }
        
        return self::__HTML('a', $atts, $label);
    }
    /**
     * <ul></ul>
     * @param array $content
     * @param array $atts
     * @param mixed $itemClass
     * @return HTML
     */
    protected static function renderListUnsorted( array $content , array $atts , $itemClass = '' ){
        
        $collection = array();
        
        foreach( $content as  $item ){
            $collection[] = !empty($itemClass) ?
                    self::__HTML('li', array('class'=>$itemClass) , $item ) :
                    self::__HTML('li', array(), $item ) ;
        }
        
        return self::__HTML( 'ul' , $atts ,  $collection );
    }
    /**
     * <ol></ol>
     * @param array $content
     * @param array $atts
     * @param mixed $itemClass
     * @return HTML
     */
    protected static function renderListOrdered( array $content , array $atts , $itemClass = '' ){
        
        $collection = array();
        
        foreach( $content as  $item ){
            $collection[] = !empty($itemClass) ?
                    self::__HTML('li', array('class'=>$itemClass) , $item ) :
                    self::__HTML('li', array(), $item ) ;
        }
        
        return self::__HTML( 'ol' , $atts ,  $collection );
    }
    /**
     * <span></span>
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    protected static final function renderSpan( $content , $atts = array( ) ){
        return self::__HTML('span', $atts , $content );
    }
    /**
     * <img src />
     * @param string/URL $src
     * @param array $atts
     * @return HTML
     */
    protected static final function renderImage( $src , array $atts = array( ) ){
        
        $atts['src'] = $src;
        
        return self::__HTML('img', $atts);
    }
    /**
     * <label></label>
     * @param string $input
     * @param string $text
     * @param mixed $class
     * @return HTML
     */
    protected static function renderLabel( $text , array $atts = array() ){

        return self::__HTML('label', $atts, $text);
    }
    /**
     * <input type="number" />
     * @param String $name
     * @param int $value
     * @param array $atts
     * @return HTML
     */
    protected static function inputNumber( $name, $value = 0, array $atts = array() ){
        
        if( !isset($atts['min'])){ $atts['min'] = 0; }

        if( !isset($atts['step'])){ $atts['step'] = 1; }
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        
        $atts['name'] = $name;
        
        $atts['value'] = $value;
        
        $atts['type'] = 'number';
        
        return self::__HTML('input', $atts);
    }
    /**
     * <span class="price" />
     * @param string $name
     * @param int $value
     * @param string $coin
     * @return HTML
     */
    protected static function price( $name, $value = 0.0, $coin = '&eur', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        
        return self::__HTML('span',
                $atts ,
                $value . self::__HTML('span', array('class'=>'coin'), $coin));
    }
    /**
     * <textarea></textarea>
     * @param string $name
     * @param string $value
     * @param array $atts
     */
    protected static function inputTextArea( $name, $value = '', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        
        return self::__HTML('textarea', $atts, $value);
    }
    /**
     * <input type="text" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    protected static function inputText($name, $value = '', array $atts = array() ){
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'text';
        
        return self::__HTML( 'input' , $atts );
    }
    /**
     * <input type="password" />
     * @param string $name
     * @param array $atts
     * @return HTML
     */
    protected static function inputPassword( $name, array $atts = array() ){
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['type'] = 'password';
        return self::__HTML( 'input' , $atts );
    }
    /**
     * <input type="search" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    protected static function inputSearch( $name, $value = '' , array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'search';
        return self::__HTML( 'input' , $atts );
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
    protected static function inputDate($name, $value = '', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'date';
        return self::__HTML( 'input' , $atts );
    }
    /**
     * <input type="tel" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    protected static function inputTelephone($name, $value = null, array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'tel';
        return self::__HTML( 'input' , $atts );
    }
    /**
     * <input type="email" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    protected static function inputEmail($name, $value = '', array $atts = array() ){
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'email';
        return self::__HTML( 'input' , $atts );
    }
    /**
     * <input type="checkbox" />
     * @param string $name
     * @param boolean $checked
     * @param array $atts
     * @return HTML
     */
    protected static function inputCheckBox( $name, $checked = false , $value = 1, array $atts = array() ){
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'checkbox';
        if($checked){ $atts['checked'] = 1; }
        return self::__HTML( 'input' , $atts );
    }
    /**
     * Lista de opciones <input type="radio" />
     * @param String $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    protected static function inputOptionList( $name, array $options, $value = null, array $atts = array( ) ){


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
            
            $radioItems[ ] = self::__HTML(
                    'li',
                    array(),
                    self::__HTML( 'input', $optionAtts, $label) );
        }
        
        return self::__HTML('ul', $atts, implode('</li><li>',  $radioItems));
    }
    /**
     * <select size="5" />
     * @param string $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    protected static function inputList($name, array $options, $value = null, array $atts = array() ){
        
        if( !isset($atts['id']) ){
            preg_replace('/-/', '_',  $name );
        }
        
        if( !isset($atts['size'])){
            $atts['size'] = 5;
        }
        
        $items = array();
        
        if( isset($atts['placeholder'])){
            $items[''] = sprintf('- %s -', $atts['placeholder'] );
            unset($atts['placeholder']);
        }
        
        foreach( $options as $option => $label ){
            $items[] = self::__HTML(
                    'option',
                    $option == $value ? array('value'=> $option,'selected') : array('value'=>$option),
                    $label);
        }
        
        return self::__HTML('select', $atts, $options );
    }
    /**
     * <select size="1" />
     * @param string $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    protected static function inputDropDown($name, array $options, $value = null, array $atts = array() ){
        
        $atts['size'] = 1;
        
        return self::inputList( $name ,
                $options,
                $value, $atts);
    }
    /**
     * <input type="hidden" />
     * @param string $name
     * @param string $value
     * @return HTML
     */
    public static function inputHidden( $name, $value ){
        
        return self::__HTML('input', array(
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
    protected static function inputFile( $name , array $atts = array( ) ){
        
        $max_filesize = 'MAX_FILE_SIZE';
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_', $name);
        $atts['name'] = $name;
        $atts['type'] = 'file';
        
        $file_size =  pow(1024, 2);
        
        if( isset($atts[$max_filesize]) ){
            $file_size = $atts[$max_filesize];
            unset($atts[$max_filesize]);
        }
        
        return self::inputHidden( $max_filesize, $file_size ) . self::__HTML('file', $atts );
    }
    /**
     * <button type="*" />
     * @param string $name
     * @param string $value
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    protected static function inputButton( $name, $value , $content, array $atts = array( ) ){
        
        $atts['value'] = $value;
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name ) . '_' . $value;
        $atts['name'] = $name;
        if( !isset($atts['type'])){
            $atts['type'] = 'button';
        }
        return self::__HTML('button', $atts, $content);
    }
    /**
     * <button type="submit" />
     * @param string $name
     * @param string $value
     * @param string $label
     * @param array $atts
     * @return HTML
     */
    protected static function inputSubmit( $name , $value , $label , array $atts = array( ) ){
        
        return self::inputButton($name,
                $value,
                $label,
                array_merge( $atts , array( 'type'=>'submit' ) ));
    }


    /**
     * @return string
     */
    public final function getEndPoint(){
        return $this->get('endpoint', '');
    }
    /**
     * @return string
     */
    public final function getModule(){
        return $this->get('module', '');
    }
    /**
     * @return string
     */
    public final function getContext(){
        return $this->get('context', '');
    }
    /**
     * @param string $title
     * @return \CODERS\Framework\Views\Renderer
     */
    public final function setTitle( $title ){
        
        return $this->set('title', $title );
    }
    /**
     * @return string
     */
    public final function getTitle(){
        return $this->get('title', '');
    }
    /**
     * @param \CODERS\Framework\IModel $model
     * @return \CODERS\Framework\Views\Renderer
     */
    public function setModel( \CODERS\Framework\IModel $model ){

        $this->_model = $model;

        return $this;
    }
    /**
     * @return \CODERS\Framework\IModel
     */
    protected function getModel(){ return $this->_model; }

    
    /**
     * Inicializa las dependencias del componente
     * @return \CODERS\Framework\Views\Renderer
     */
    private final function registerAssets(  ){

        $metas = $this->_metas;
        $links = $this->_links;
        $styles = $this->_styles;
        $scripts = $this->_scripts;
        
        $hook = is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts';
        
        //public metas and linnks
        if ( !is_admin() && class_exists('\CODERS\Framework\Views\HTML')) {

            add_action('wp_head', function() use( $metas, $links ) {
                foreach ($metas as $meta_id => $atts) {
                    print \CODERS\Framework\Views\Renderer::renderMeta($atts, $meta_id);
                }
                foreach ($links as $link_id => $atts) {
                    print \CODERS\Framework\Views\Renderer::renderLink(
                                    $atts['href'], $atts['type'], 
                                    array_merge($atts, array('id' => $link_id)));
                }
            });
        }
        //styles
        add_action( $hook, function() use( $styles ) {       
            foreach ($styles as $style_id => $url) {
                wp_enqueue_style($style_id, $url);
            }
        });
        //Scripts
        add_action( $hook , function() use( $scripts ) {
            foreach ($scripts as $script_id => $content) {
                if (isset($content['deps'])) {
                    wp_enqueue_script(
                            $script_id,
                            $content['url'],
                            $content['deps'],
                            false, TRUE);
                }
                else {
                    wp_enqueue_script(
                            $script_id,
                            $content['url'],
                            array(), false, TRUE);
                }
            }
        });
        return $this;
    }
    /**
     * @param string $classes
     * @return \CODERS\Framework\Views\DocumentRender
     */
    protected function addClass( $classes ){
        
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
    protected final function addMeta( $meta_id , array $attributes ){
        
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
    protected final function addLink( $link_id , $link_url , array $attributes = null ){
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
     * @param string $id
     * @param string $style
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function addStyle( $id , $style ){
        
        if(strlen($style)){

            if( !isset( $this->_styles[ $id ] ) ){

                if( !$this->matchUrl($style) ){

                    $style = $this->assetUrl($style);
                }

                $this->_styles[$id] = $style;
            }
        }
        
        return $this;
    }
    /**
     * Registra un script
     * @param string $id
     * @param string $script
     * @param mixed $deps Dependencias del script
     * @return \CODERS\Framework\Views\Renderer
     */
    protected final function addScript( $id , $script , $deps = null ){
        
        if( !isset( $this->_scripts[ $id ] ) ){
            
            if( !$this->matchUrl($script) ){
                
                $script = $this->assetUrl($script);
            }

            $this->_scripts[$id] = array( 'url' => $script );
            
            if( !is_null($deps)){
                $this->_scripts[$id]['deps'] = !is_array($deps) ? explode( ',', $deps ) : $deps;
            }
        }
        
        return $this;
    }
    /**
     * Registra una fuente de Google Fonts
     * @param string $font
     * @param mixed $weight
     */
    protected final function addFont( $font , $weight = null ){
        
        $font_id = 'font-' . preg_replace( '/ /' , '-' , strtolower($font));

        $font_url = self::GOOGLE_FONTS_URL . '=' . $font ;
        
        if( !is_null($weight)){

            if( !is_array($weight)){
 
                $weight = explode( ',' , $weight );
            }
            
            $font_url .= ':' . implode(',', $weight);
        }
        
        return $this->addStyle( $font_id, $font_url );
    }
    /**
     * 
     * @param string $endpoint
     * @param string $module
     * @return boolean|\CODERS\Framework\Views\Renderer
     */
    public static final function create( $endpoint , $module ){
        
        if( \CodersApp::loaded($endpoint)){
            
            $path = sprintf( '%s/modules/%s/views/%s.view.php',
                    \CodersApp::appRoot($endpoint),
                    $module, strtolower($module) );

            $class = $module.'View';
            
            if(file_exists($path)){

                require_once $path;
                
                if(class_exists($class) && is_subclass_of($class, self::class)){
                    return new $class( $endpoint , $module );
                }
            }
        }
        
        return FALSE;
    }
}




