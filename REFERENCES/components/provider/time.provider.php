<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

/**
 * Gestión de fechas y marcas de tiempo
 */
final class Time{
    /**
     * Marca de tiempo
     * @var int
     */
    private $_time;
    /**
     * Formato utilizado
     * @var string
     */
    private $_format;
    /**
     * @param int $time
     * @param string $format
     */
    private function __construct( $time = null, $format = null ) {

        $this->_format = !is_null($format) ?
                $format :
                TripManager::getOption('tripman_date_format');
        
        if(is_null($time)){
            $this->_time = time();
        }
        if(is_string($time)){
            $this->_time = strtotime($time);
        }
        elseif(is_numeric($time)){
            $this->_time = $time;
        }
    }
    /**
     * Fecha
     * @return string
     */
    public final function __toString() {
        return $this->getDate();
    }
    /**
     * Año
     * @return int
     */
    public final function getYear(){
        return intval( date( 'Y' ,$this->_time ) );
    }
    /**
     * Mes
     * @return int
     */
    public final function getMonth(){
        return intval( date( 'm' ,$this->_time ) );
    }
    /**
     * Número de días del mes
     * @return int
     */
    public final function getMonthDays(){
        
        return intval( date(  't', $this->_time ) );
    }
    /**
     * Día del mes
     * @return int
     */
    public final function getDay(){
        return intval( date( 'j' ,$this->_time ) );
    }
    /**
     * Día de la semana
     * @return int
     */
    public final function getWeekDay(){
        return intval( date( 'w' ,$this->_time ) );
    }
    /**
     * Hora
     * @return int
     */
    public final function getHour(){
        return intval( date( 'H' ,$this->_time ) );
    }
    /**
     * Minuto
     * @return int
     */
    public final function getMinute(){
        return intval( date( 'i' ,$this->_time ) );
    }
    /**
     * Indica si es un año bisiesto
     * @return boolean
     */
    public final function isLeap(){
        return intval( date( 'L', $this->_time ) ) > 0 ;
    }
    /**
     * 
     * @param boolean $display
     * @return string
     */
    public final function getDate( $display = false ){
        $date = date($this->_format, $this->_time);
        
        return $display ?
                TripManStringProvider::displayDate($date) :
                $date;
    }
    /**
     * Quita días/horas/etc a una fecha
     * @param int $amount Cantidad a restar
     * @param string $units tipo de unidades a restar
     * @return \TripManTimeProvider Chaining
     */
    public final function sub( $amount , $units = 'days' ){
        switch( $units ){
            case 'years':
            case 'months':
            case 'weeks':
            case 'days':
            case 'hours':
            case 'minutes':
                $this->_time = strtotime(sprintf('%s - %s %s',$this->getTime(),$amount, $units ) );
                break;
        }
        return $this;
    }
    /**
     * Añade dias/horas/etc a una fecha
     * @param int $amount Cantidad a sumar
     * @param string $units tipo de unidades a sumar
     */
    public final function add( $amount, $units = 'days' ){
        
        switch( $units ){
            case 'years':
            case 'months':
            case 'weeks':
            case 'days':
            case 'hours':
            case 'minutes':
                $this->_time = strtotime(sprintf('%s + %s %s',$this->getTime(),$amount, $units ) );
                break;
        }
        return $this;
    }
    /**
     * Crea una fecha
     * @param mixed $date
     * @return \TripManTimeProvider
     */
    public static final function createDate( $date , $format = null ){

        if( is_null( $format ) ){
            $format = TripManager::getOption('tripman_date_format');
        }
        
        return new TripManTimeProvider( $date, $format );
    }
    /**
     * Crea una marca de tiempo
     * @param mixed $time
     * @return \TripManTimeProvider
     */
    public static final function createTime( $time, $format = null ){

        if( is_null( $format ) ){
            $format = TripManager::getOption('tripman_time_format');
        }
        
        return new TripManTimeProvider( $time, $format );
    }
    /**
     * Lista las fechas entre el rango determinado
     * @param string $dateFrom
     * @param string $dateTo
     * @return \TripManTimeProvider[] Lista de fechas
     */
    public static final function listPeriod( $dateFrom, $dateTo ){

        $dateFormat = TripManager::getOption('tripman_date_format');

        //rango desde fecha
        $fromDate = new DateTime( $dateFrom );
        //hasta fecha
        $toDate = new DateTime( $dateTo );
        //establece un intervalo para iterar sobre el periodo
        $interval = DateInterval::createFromDateString('1 day');
        //agregar un día mas al límite, por que la selección excluye la fecha hasta
        $toDate->add($interval);
        //genera un periodo sobre el que iterar
        $period = new DatePeriod($fromDate, $interval, $toDate);

        $calendar = array();
        
        foreach( $period as $day ){

            $calendar[] = new TripManTimeProvider( $day->format( $dateFormat ), $dateFormat );
        }
        
        return $calendar;
    }
}


