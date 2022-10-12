<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

/**
 * Gestor de tokens/sesiones de agente/acceso a la reserva.
 * 
 * un token incluye información implícitamente del perfil que lo utiliza,
 * por tanto, el token solo puede y debe utilizarse en un solo contexto dentro
 * de un modulo del TRipManager:
 *  - ticket: para acceder al ticket de reserva (caducidad, 1h)
 *  - agente/turoperador: para acceder al form de reserva (caducidad, 20 minutos)
 *  - checkin ( API json para app de checkins ): para acceder al input de checkins (caducidad, 5 meses)
 *  - otros ...
 */
final class Token{
    /**
     * Id de agente/usuario con token abierto
     * @var int
     */
    private $_ID = 0;
    /**
     * Punto de entrada para capturar el token (Token utilizable en un solo contexto para cada modulo)
     * @var string
     */
    private $_entryPoint;
    /**
     * Hash de sesión (token) de 32 o 64 carácteres
     * @var string
     */
    private $_tokenId = NULL;
    /**
     * Tiempo de permanencia (en segundos)
     * @var int
     */
    private $_expiration;
    /**
     * 
     * @param int $related_id
     * @param mixed $expiration
     */
    public final function __construct( \CodersApp $app, $related_id , $expiration = null ) {
        
        $this->_ID = $related_id;
        
        $this->_entryPoint = strval($app);
        
        $this->_tokenId = $app->generateId($this->_ID);
        
        if(is_null($expiration)){
            $this->_expiration = time() + 3600;
        }
        elseif(is_numeric($expiration)){
            $this->_expiration = time() + $expiration;
        }
        elseif(is_string($expiration)){
            $this->_expiration = strtotime( $expiration );
        }
    }
    /**
     * TOken generado
     * @return string
     */
    public final function __toString() {
        return !is_null( $this->_tokenId ) ? $this->_tokenId : '';
    }
    /**
     * @param string $name
     * @return string
     */
    public final function __get($name) {
        switch( $name ){
            case 'token':
                return $this->_tokenId;
            case 'profile':
            case 'entry_point':
                return $this->_entryPoint;
            case 'expiration':
                return $this->_expiration;
            case 'id':
                return $this->_ID;
        }
        return '';
    }
    /**
     * @return int
     */
    public final function id(){ return $this->_ID; }
    /**
     * @return string
     */
    public final function profile(){ return $this->_entryPoint; }
    /**
     * @param boolean $display Muestra el formato texto, establecido por defecto
     * @return mixed
     */
    public final function expiration( $display = true){
        return $display ? date('Y-m-d H:i:s', $this->_expiration ) : $this->_expiration;
    }
    /**
     * Cancela el token concedido
     * @return boolean
     */
    public final function purge(){

        $this->_expiration = 0;

        $db = new TripManDBProvider();
        
        $updated = $db->update(TripManDBProvider::TABLE_TOKENS,
                array('expiration'=>$this->_expiration),
                array('token_id'=>$this->_tokenId,'entry_point'=>$this->_entryPoint));
        
        return $updated > 0;
    }
    /**
     * Actualiza el tiempo de expiración del token
     * @param string $expiration
     */
    public final function update( $expiration = null ){
        
        if(is_null($expiration)){
            //agrega 5 minutos
            $expiration = time() + 300;
        }
        elseif(is_string($expiration)){
            $timestamp = strtotime($expiration);
            if( $this->_expiration < $timestamp ){
                $this->_expiration = $timestamp;
            }
            else{
                return false;
            }
        }
        
        $db = new TripManDBProvider();
        
        $updated = $db->update(TripManDBProvider::TABLE_TOKENS,
                array('expiration'=>$expiration),
                array('token_id'=>$this->_tokenId,'entry_point'=>$this->_entryPoint));
        
        return $updated > 0;
    }
    /**
     * @param int $id
     * @param mixed $expiration
     * @return TripManTokenModel
     */
    public static final function create( $id , $expiration = 3600 ){
        
        $token = new TripManTokenModel($id, $expiration );
        
        $db = new TripManDBProvider();
        
        $createed = $db->insert_query( TripManDBProvider::TABLE_TOKENS, array(
            'token_id' => $token->_tokenId,
            'entry_point' => $token->_entryPoint,
            'related_id' => $token->_ID,
            'expiration' => $token->_expiration,
        ));
        
        return $createed ? $token : null;
    }
    /**
     * Importa un token existente desde la base de datos
     * @param string $token_id
     * @param string $entry_point
     * @return \TripManTokenProvider|NULL
     */
    public static final function import( $token_id , $entry_point = null ){
        
        $db = new TripManDBProvider();
        
        $sql_token = sprintf("SELECT * FROM `%s` WHERE `token_id`='%s' AND `entry_point`='%s' AND `expiration`>'%s'",
                TripManDBProvider::getTable(TripManDBProvider::TABLE_TOKENS),
                $token_id,
                $entry_point,
                date('Y-m-d H:i:s'));
        
        $selection = $db->getResults($sql_token);
        
        if( count( $selection ) ){
            $token_data = $selection[0];
            $token = new TripManTokenProvider(
                    $token_data['related_id'],
                    $token_data['expiration'],
                    $token_data['entry_point']);
            
            $token->_tokenId = $selection['token_id'];
            
            return $token;
        }
        
        return null;
    }
}




