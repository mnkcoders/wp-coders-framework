<?php CODERS\Framework\Services;

defined('ABSCLASS') or die;
/**
 * send a mail template through wp_mail
 */
class MailerService extends \CODERS\Framework\Service{

    protected final function __construct(array $settings = array()) {

        $this->define('sender',$this->adminEmail())
                ->define('subject','')
                ->define('template','')
                ->define('headers','');
        
        parent::__construct($settings);
        
        
        apply_filters( 'wp_mail_from', array( $this , 'mailFrom' ));
    }
    /**
     * @param array $settings
     * @return \CODERS\Framework\Services\MailerService
     */
    protected final function import(array $settings = array()) {
        
        if( isset($settings['sender']) && !self::match($settings['sender'])){
            $settings['sender'] = $this->adminEmail();
        }
        
        return parent::import($settings);
    }
    /**
     * @return string
     */
    private final function adminEmail(){
        return get_option('admin_email');
    }
    /**
     * @return string
     */
    public final function mailFrom(){
        return $this->sender;
    }
    /**
     * @param string $receiver
     * @param string $subject
     * @param string $template
     * @param string $headers
     * @return boolean
     */
    private final function send( $receiver , $subject , $template , $headers ){
        
        return wp_mail($receiver, $subject, $template, $headers );
    }
    /**
     * @param string $message
     * @param array $contet
     * @return string
     */
    private final function parseTemplate( $message , array $contet = array()){
        
        foreach( $contet as $map => $replacer ){
            $message = str_replace(sprintf('{%s}', strtoupper($map)), $replacer, $message );
        }
        return $message;
    }
    /**
     * @param string $subject
     * @param array $contet
     * @return string
     */
    private final function parseSubject( $subject , array $contet = array()){
        
        foreach( $contet as $map => $replacer ){
            $subject = str_replace(sprintf('{%s}', strtoupper($map)), $replacer, $subject );
        }
        return $subject;
    }
    /**
     * @return string
     */
    private final function packHeaders(){
        $headers = $this->headers;
        return is_array($headers) ? implode(' ', $headers) : $headers;
    }
    /**
     * @param array $data
     * @return boolean
     */
    protected final function dispatch(array $data ) {
        if (isset($data['receiver']) && self::match($data['receiver'])) {
            $headers = $this->packHeaders();
            return $this->send(
                    $data['receiver'],
                    $this->parseSubject($this->subject, $data),
                    $this->parseTemplate($this->template, $data),
                    $headers);
        }

        return false;
    }
    
    /**
     * @return string
     */
    protected static final function match( $email ){
        return preg_match( "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i" , $email) > 0;
    }
}

