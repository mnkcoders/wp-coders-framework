<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

/**
 * Descriptor de LOGS, mensajes visuales y notificaciones del componente.
 */
class Log{
    
    const LOG_TYPE_DEBUG = 0;
    const LOG_TYPE_INFO = 1;
    const LOG_TYPE_NOTIFY = 2;
    const LOG_TYPE_ADVICE = 3;
    const LOG_TYPE_WARNING = 4;
    const LOG_TYPE_ERROR = 5;
    const LOG_TYPE_EXCEPTION = 6;
    const LOG_TYPE_SYSTEM = 7;      //MENSAJES SECRETOS (SOLO PARA EL LOG)
    
    const LOG_DUMP_OUT = 1;
    const LOG_DUMP_DB = 2;
    const LOG_DUMP_FILE = 3;
    
    private static $_LOGFILE = 'tripman';
    /**
     * Tipo de volcado LOG
     * @var int
     */
    private static $_output = self::LOG_DUMP_DB;
    /**
     * Nombre de usuario si se aplica
     * @var string
     */
    private static $_agent = '';

    /**
     * @var TripManLogProvider[]
     */
    private static $_logs = array();
    /**
     * @var string
     */
    private $_message;
    /**
     * Formato aaaammddhhmmss
     * @var string
     */
    private $_timestamp;
    /**
     * Contexto del evento (sirve para filtrar en admin)
     * @var string
     */
    private $_context;
    /**
     * Tipo.ID del Registro afectado (detalle de reserva/salida/agente/modelo que lo genera)
     * @var string
     */
    private $_attached;
    /**
     * Tipo de evento
     * @var int
     */
    private $_type = self::LOG_TYPE_INFO;
    /**
     * 
     * @param string $message Mensaje del registro
     * @param int $type Tipo de registro
     * @param mixed $context Origen del registro (objeto, texto descriptivo, se utiliza para filtrar)
     */
    private function __construct( $message, $type = self::LOG_TYPE_INFO, $context = null ) {
        
        $this->_message = $message;
        $this->_type = $type;
        $this->_timestamp = date('YmdHis');
        $this->_context = \CodersApp::class;

        if( !is_null($context)){
            if(is_object($context)){
                $this->_context = get_class($context);
                $this->_attached = strval($context);
            }
            elseif(is_string($context)){
                $this->_context = $context;
            }
            else{
                $this->_context = \CodersApp::class;
            }
        }
        
        self::$_logs[] = $this;
    }
    /**
     * @return String Mensaje del log
     */
    public final function __toString(){
        
        $context = !is_null($this->_context) ? $this->_context : 'log';

        return sprintf('<span class="log coders-log-container %s context-%s"><i>[ %s ]</i> %s</span>',
                $this->getType(true),
                strtolower( $context ),
                $context, $this->_message );
    }
    /**
     * @return String
     */
    public final function getMessage( ){
        return  $this->_message;
    }
    /**
     * @return int|String
     */
    public final function getType( $displayName = false ){
        return $displayName ?
                self::displayType($this->_type) :
                $this->_type;
    }
    /**
     * @param bool $dateTimeFormat Determina si se mostrará con formato de fecha-hora
     * @return string TimeStamp
     */
    public final function getTimeStamp( $dateTimeFormat = false ){
        
        if( $dateTimeFormat ){
            //mostrar en formato aaaa-mm-dd hh-mm-ss
            //20160727112015
            return sprintf('%s-%s-%s %s:%s:%s',
                substr($this->_timestamp,0,4),
                substr($this->_timestamp,4,2),
                substr($this->_timestamp,6,2),
                substr($this->_timestamp,8,2),
                substr($this->_timestamp,10,2),
                substr($this->_timestamp,12,2));
        }
        
        return $this->_timestamp;
    }
    /**
     * @return String
     */
    public final function getContext(){
        return $this->_context;
    }
    /**
     * Datos en formato array
     * @return array
     */
    public final function getLogData(){
        return array(
            'timestamp' => $this->getTimeStamp(),
            'type' => $this->_type,
            'context' => $this->_context,
            //elimina los tags HTML de formato
            'message' => strip_tags( $this->_message ),
            'agent' => self::$_agent,
        );
    }
    /**
     * Convierte el tipo de notificación en representación textual
     * @param int $type
     * @return string
     */
    public static final function displayType( $type ){
        switch( $type ){
            case self::LOG_TYPE_DEBUG:
                return 'debug';
            case self::LOG_TYPE_NOTIFY:
                return 'notify';
            case self::LOG_TYPE_INFO:
                return 'info';
            case self::LOG_TYPE_ADVICE:
                return 'advice';
            case self::LOG_TYPE_WARNING:
                return 'warning';
            case self::LOG_TYPE_ERROR:
                return 'error';
            case self::LOG_TYPE_EXCEPTION:
                return 'exception';
            default:
                return 'log';
        }
    }
    /**
     * Retorna la ruta del fichero log y lo inicializa si es necesario
     * @return string
     */
    public static final function getLogFile(){
        
        $log_folder = MNK__TRIPMAN__DIR.'logs';
        
        if( !file_exists( $log_folder ) ){
            mkdir($log_folder);
        }
        
        return sprintf('%s/%s.html', $log_folder,self::$_LOGFILE);
    }
    /**
     * Vuelca todos los logs desde el nivel indicado sobre el fichero de logs
     * 
     * @param int $level Nivel de los mensajes a exportar, por defecto errores
     * @return bool resultado
     */
    public static final function dumpOutputLog( $level = self::LOG_TYPE_ERROR ){
        
        $logs = self::listLogs( intval( $level ), self::LOG_TYPE_SYSTEM );

        if( count($logs) ){
            switch( self::$_output ){
                case self::LOG_DUMP_DB:
                    self::dumpOutputDb( $logs );
                    break;
                case self::LOG_DUMP_FILE:
                    self::dumpOutputFile($logs);
                    break;
                case self::LOG_DUMP_OUT:
                default:
                    self::dumpOutputHTML($logs);
                    break;
            }
        }
    }
    /**
     * @param TripManLogProvider[] $logData
     */
    private static final function dumpOutputHTML( array $logData ){
        
        $level = self::LOG_TYPE_INFO;
        
        foreach( $logData as $log ){

            if( $log->getType() >= $level ){
                print $log->getHTML();
            }
        }
    }
    /**
     * @param TripManLogProvider[] $logData
     */
    private static final function dumpOutputFile( array $logData ){
        
        $level = self::LOG_TYPE_INFO;
        
        $log_file = self::getLogFile();
        
        if( ($handle = fopen($log_file, 'a'))){
            
            foreach( $logData as $log ){
                if( $log->getType() >= $level ){
                    fwrite( $handle, $log->getHTML() );
                }
            }
            
            return fclose($handle);
        }
        return false;
    }
    /**
     * Inserta un volcado de logs en la bd
     * @param TripManLogProvider[] $logData
     * @return int
     */
    private static final function dumpOutputDb( array $logData ){
        
        $level = self::LOG_TYPE_INFO;
        
        $db = new TripManDBProvider();
        
        $counter = 0;
        
        foreach( $logData as $log ){
            if( $log->getType() >= $level ){
                $counter += $db->insert('logs', $log->getLogData());
            }
        }
        
        return $counter;
    }
    /**
     * Registra un LOG
     * @param String $message Mensaje a generar
     * @param int $type Tipo de notificación, por defecto informativa
     * @param mixed $context
     * @return \TripManLogProvider
     */
    private static final function log( $message, $type = self::LOG_TYPE_INFO, $context = null ){
        
        return new TripManLogProvider($message,$type, $context );
    }
    /**
     * Registrar Log
     * @param string $message
     * @param string|null $context
     * @return \TripManLogProvider
     */
    public static final function debug( $message, $context = null ){
        return new TripManLogProvider( $message, self::LOG_TYPE_DEBUG, $context );
    }
    /**
     * Registra una notificación
     * @param string $message
     * @return \TripManLogProvider
     */
    public static final function notify( $message, $context = null ){
        return self::log($message, self::LOG_TYPE_NOTIFY, $context );
    }
    /**
     * Registra una entrada de información en el Log
     * @param string $message
     * @return \TripManLogProvider
     */
    public static final function info( $message, $context = null ){
        return self::log($message, self::LOG_TYPE_INFO, $context );
    }
    /**
     * Registra un aviso/consejo en el log
     * @param string $message
     * @return \TripManLogProvider
     */
    public static final function advice( $message, $context = null ){
        return self::log($message, self::LOG_TYPE_ADVICE, $context );
    }
    /**
     * Registra una advertencia en el log
     * @param string $message
     * @return \TripManLogProvider
     */
    public static final function warning( $message, $context = null ){
        return self::log($message, self::LOG_TYPE_WARNING, $context );
    }
    /**
     * Registra un mensaje de sistema, solo visible para la administración desde el diario de logs
     * (nuca se notificará por pantalla)
     * 
     * Será útil para registrar información técnica relevante, sobretodo de errores y posibles
     * alertas durante la ejecución de la aplicación.
     * 
     * @param string $message
     * @param mixed $context
     * @return \TripManLogProvider
     */
    public static final function system( $message, $context = null ){
        return self::log($message, self::LOG_TYPE_SYSTEM, $context );
    }
    /**
     * Registra un error en el log
     * @param string $message
     * @return \TripManLogProvider
     */
    public static final function error( $message, $context = null ){
        return self::log($message, self::LOG_TYPE_ERROR, $context);
    }
    /**
     * Genera una excepción para la aplicación
     * @param String $message
     * @param array $arguments
     * @return TripManLogProvider Log
     */
    public static final function exception( Exception $ex , $context = null ){
        
        $attached = array(
                'exception_code'=>$ex->getCode(),
                'exception_source'=>$ex->getFile(),
                'exception_line'=>$ex->getLine(),
                'exception_trace'=>$ex->getTrace());
        
        if( is_array($context) ){
            foreach($context as $value){
                $attached[] = $value;
            }
        }
        elseif(is_string($context)){
            $attached[] = $context;
        }

        return new TripManLogProvider(
                $ex->getMessage(),
                self::LOG_TYPE_EXCEPTION,
                $attached );
    }
    /**
     * @return \TripManLogProvider Recupera el último error
     */
    public static final function getLastError(){
        
        for( $i = count( self::$_logs ) -1 ; $i >= 0 ; $i-- ){
            
            $type = self::$_logs[$i]->getType();
            
            if( $type >= self::LOG_TYPE_WARNING && $type < self::LOG_TYPE_SYSTEM ){
            
                return self::$_logs[$i];
            }
        }
        
        return self::debug('No hay errores que mostrar');
    }
    /**
     * @param int $from Nivel mínimo para la extracción de logs
     * @param int $to Nivel máximo para la extracción de logs
     * @return TripManLogProvider[] Lista de logs generados
     */
    public static final function listLogs( $from = self::LOG_TYPE_INFO, $to = self::LOG_TYPE_EXCEPTION ){
        
        $list = array();
        
        foreach( self::$_logs as $logData ){
            if( $logData->_type >= $from && $logData->_type <= $to ){
                $list[] = $logData;
            }
        }

        return $list;
    }
    /**
     * Ejecuta el cargador de logs de la vista de administración en el back-end
     * @param int $type Tipo de mensajes
     */
    public static final function showAdminMessages( $type = self::LOG_TYPE_INFO ){
        
        add_action( 'admin_notices', function() use( $type ){ 
            foreach( self::listLogs(self::LOG_TYPE_INFO) as $log ){
                if( $log->getType() >= $type ){
                    echo sprintf('<div class="%s"><p>%s</p></div>',
                            $log->getType( true ),
                            TripManStringProvider::__($log->_message));
                }
            }
        } );
    }
    /**
     * Establece el usuario que registra los logs
     * @param \TripManAgentModel $agent
     */
    public static final function setAgent( TripManAgentModel $agent ){
        if( strlen(self::$_agent) === 0 && $agent->getId() > 0 ){
            self::$_agent = $agent->getUserName();
        }
    }
}