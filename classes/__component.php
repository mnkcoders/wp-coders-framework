<?php namespace CODERS\Framework;
/**
 * Description of context/controller
 */
abstract class Component{
    
    /**
     * @return string|path
     */
    public static final function __path(){
        $ref = new \ReflectionClass(get_called_class());
        return preg_replace('/\\\\/', '/',  dirname( $ref->getFileName() ) );

        //$path = preg_replace( '/\\/' , '/' ,__DIR__);
        //return explode('/wp-content/plugins/', $path)[1];
    }
    /**
     * @return String
     */
    protected final function __importEndpoint(){
        return explode('/',self::__path())[0];
    }
    
    /**
     * @param array $route
     * @return string
     */
    private static final function importClass( array $route ){
        return count($route) > 1 ?
                    sprintf('\CODERS\%s\%sModel',
                            \CodersApp::Class($route[0]),
                            \CodersApp::Class($route[1])) :
                    sprintf('\CODERS\Framework\Models\%s',
                            \CodersApp::Class($route[0]));
    }
    /**
     * @param array $route
     * @return String|PAth
     */
    private static final function importPath( array $route ){
        return count($route) > 1 ?
                    sprintf('%s/components/models/%s.php',
                            \CodersApp::path($route[0]),
                            $route[1]) :
                    sprintf('%s/components/models/%s.php',
                            \CodersApp::path(),
                            $route[0]);
    }

}