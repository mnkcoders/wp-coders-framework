<?php namespace CODERS\Framework\Models;

defined('ABSPATH') or die;
/**
 * Modelo para gestión de registros regidos por fechas o rangos de tiempo
 */
abstract class CalendarModel extends \CODERS\Framework\Component implements \CODERS\Framework\IModel{

    const DAY_MONDAY = 1;
    const DAY_TUESDAY = 2;
    const DAY_WEDNESDAY = 3;
    const DAY_THURSDAY = 4;
    const DAY_FRIDAY = 5;
    const DAY_SATURDAY = 6;
    const DAY_SUNDAY = 0;

    const MONTH_JAUNARY = 1;
    const MONTH_FEBRUARY = 2;
    const MONTH_MARCH = 3;
    const MONTH_APRIL = 4;
    const MONTH_MAY = 5;
    const MONTH_JUNE = 6;
    const MONTH_JULY = 7;
    const MONTH_AUGUST = 8;
    const MONTH_SEPTEMBER = 9;
    const MONTH_OCTOBER = 10;
    const MONTH_NOVEMBER = 11;
    const MONTH_DECEMBER = 12;
    
    public function __construct( array $data = array( ), array $settings = array( ) ) {
        
        if( count($settings)){
            foreach($settings as $var => $val ){
                $this->set($var, $val);
            }
        }
    }
    /**
     * @return array Lista de registros a importar indexados por la fecha que marcará el calendario
     */
    abstract protected function importRecords( $dateFrom, $dateTo );
    /**
     * @param array $data
     * @return array Registro a rellenar sobre una fecha
     */
    abstract protected function fillDateRecord( array $data );
    /**
     * @return array Registro por defecto para representar fechas sin asignar
     */
    abstract protected function fillDateEmpty( $date );
    /**
     * @return string
     */
    public function __toString() {
        return parent::getName();
    }
    /**
     * @param string $var
     * @param mixed $val
     * @return \CODERS\Framework\Models\CalendarModel
     */
    public function set($var, $val) {
        parent::set($var, $val);
        return $this;
    }
    /**
     * Formato de Fecha
     * @return string
     */
    protected final function getDateFormat(){
        return TripManager::getOption('tripman_date_format','Y-m-d');
    }
    /**
     * Año actual o según la fecha de referencia
     * @param string $date Fecha de referencia
     * @return int
     */
    public static final function getYear( $date = null ){
        
        $time = !is_null($date) ? strtotime($date) : TripManager::getTime();
        
        return intval(date('Y',$time));
    }
    /**
     * Mes actual o según la fecha de referencia
     * @param string $date Fecha de referencia
     * @return int
     */
    public static final function getMonth( $date = null ){
        
        $time = !is_null($date) ? strtotime($date) : TripManager::getTime();
        
        return intval(date('m',$time));
    }
    /**
     * Provee el número de días de un mes dada la fecha de referencia, por defecto la fecha de hoy
     * @param string $date
     * @return int Número de días del mes
     */
    public static final function getMonthDayCount( $date = null ){
        
        if(is_null($date)){
            $date = TripManager::getDate();
        }
        
        $current = strtotime( $date );
        
        return intval(date('t',$current));
    }
    /**
     * Obtiene el día de la semana actual o dada una fecha de referencia
     * @param string $date Fecha de referencia
     * @return int Número correspondiente al día de la semana (empieza por domingo)
     */
    public static final function getWeekDay( $date = null ){
        
        $time = !is_null($date) ? strtotime($date) : TripManager::getTime();
        
        //retorna en función de si es numérico (marca de tiempo) o fecha (texto)
        return intval(date('w', $time));
    }
    /**
     * Retorna la Fecha del último día de la semana actual o según la fecha proveida
     * @param int $time Fecha de referencia
     * @return int Marca de tiempo del primer día de la semana según la fecha de referencia
     */
    public final function getLastWeekDay( $date = null ){
        
        if( is_null( $date ) ){
            $date = TripManager::getDate();
        }

        //obtener semana de la fecha actual
        $weekDay = $this->getWeekDay($date);

        $offset = $weekDay > 0 ? 7 - $weekDay : 0;

        return date(
                TripManager::getOption('tripman_date_format','Y-m-d'),
                strtotime(sprintf('%s +%s days',$date, $offset )) );
    }
    /**
     * Retorna la Fecha del primer día de la semana actual o según la fecha proveida
     * @param string $date Fecha de referencia
     * @return int Marca de tiempo del primer día de la semana según la fecha de referencia
     */
    public final function getFirstWeekDay( $date = null ){
        
        if( is_null( $date ) ){
            $date = TripManager::getDate();
        }
        //obtener semana de la fecha actual
        $weekDay = $this->getWeekDay($date);
        
        $offset = $weekDay > 0 ? $weekDay-1 : 6;

        return date(
                TripManager::getOption('tripman_date_format','Y-m-d'),
                strtotime(sprintf('%s -%s days',$date, $offset )) );
    }
    /**
     * Día actual o según la fecha de referencia
     * @param string $date Fecha de referencia
     * @return int
     */
    public static final function getDay( $date = null ){
        
        $time = !is_null($date) ? strtotime($date) : TripManager::getTime();
        
        return intval(date('d',$time));
    }
    /**
     * Agrega o sustrae días a la fecha de referencia, por defecto fecha actual
     * @param string $date Fecha de referencia
     * @param int $offset Número de días de diferencia
     * @param boolean $substract Indica si sustrae los días en lugar de agregarlos (fecha pasada)
     * @return string Fecha de referencia modificada por el offset
     */
    public static final function getDayOffset( $date = null, $offset = 7, $substract = false ){
        
        if(is_null($date)){
            $date = TripManager::getDate();
        }
        
        $time = strtotime(sprintf('%s %s%s days',
                $date,
                $substract ? '-' : '+',
                $offset));
        
        return date(TripManager::getOption('tripman_date_format'),$time);
    }
    /**
     * Convierte la fecha de referencia a una marca de tiempo
     * @param string $date
     * @return int Marca de tiempo
     */
    public final function getTime( $date = null ){
        return !is_null($date) ?
                strtotime($date) :
                TripManager::getTime();
    }
    /**
     * Lista las fechas entre el rango determinado
     * @param string $dateFrom
     * @param string $dateTo
     * @return array Lista de fechas
     */
    protected final function listPeriod( $dateFrom, $dateTo ){

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
            
            $calendar[] = $day->format($dateFormat);
        }
        
        return $calendar;
    }
    /**
     * Lista las fechas dentro de un rango
     * @param string $dateFrom
     * @param string $dateTo
     * @param boolean $reverse Revertir el orden de la selección de fechas (de mayour a menor)
     * @return array
     */
    public final function listDates( $dateFrom, $dateTo, $reverse = false ){
        //importar  los registros de fecha a fecha
        $records = $this->importRecords($dateFrom,$dateTo);
        
        if( $this->get('avoid_empty_records',FALSE) ){
            //si se ha definido explicitamente extraer solo los registros existentes, retornar
            //el resultado
            return $records;
        }
        
        //inicializar calendario
        $calendar = array();

        foreach( $this->listPeriod($dateFrom, $dateTo) as $date ){
            //rellenar el registro de fecha con datos existentes, o datos vacíos
            $calendar[ $date ] = isset( $records[$date]) ?
                    $this->fillDateRecord($records[$date]) :
                    $this->fillDateEmpty( $date );
        }

        if( $reverse ){
            krsort($calendar);
        }
        
        return $calendar;
    }
    /**
     * Agrupa las fechas por meses
     * @param array $calendar
     * @return array
     */
    protected function groupByMonth( array $calendar ){

        $output = array();
        
        //agrupa el resultado por meses
        foreach( $calendar as $date => $content ){
            
            $month = $this->getMonth( $date );
            $day = $this->getDay( $date );
            
            if( !isset($output[$month]) ){
                $output[$month] = array( $day=>$content);
            }
            else{
                $output[$month][$day] = $content;
            }
        }

        return $output;
    }
    /**
     * Agrupa las fechas por años/meses
     * @param array $calendar
     * @return array
     */
    protected function groupByYear( array $calendar ){

        $output = array();
        
        //agrupa el resultado por meses
        foreach( $calendar as $date => $content ){
            $year = $this->getYear( $date );
            $month = $this->getMonth( $date );
            $day = $this->getDay( $date );
            
            if( isset($output[$year])){
                if( !isset($output[$year][$month]) ){
                    $output[$year][$month] = array($day=>$content);
                }
                else{
                    $output[$year][$month][$day] = $content;
                }
            }
            else{
                $output[$year] = array($month => array($day => $content));
            }
        }

        return $output;
    }
    /**
     * Lista los días de la semana según la fecha indicada
     * @param string $date Fecha de referencia, nulo para obtener la fecha actual por defecto
     * @param int $weeks Número de semanas a mostrar contando la actual, 1 por defecto
     * @return array
     */
    //public function listWeek( $date = null, $weeks = 1, $substract = false ){
    public function listWeek( $dateFrom, $dateTo ){
        
        $reverse = $this->get('reverse_selection',false);
        
        $calendar = $this->listDates( $dateFrom, $dateTo , $reverse );
        
        return $calendar;
    }
    /**
     * Lista los días del mes según la fecha indicada
     * @param int $month Mes de referencia, si es 0 toma el mes actual
     * @param int $year Año de referencia, si 0, toma el año acutal
     * @return array Lista de días del mes
     */
    public function listMonth( $month = 0, $year = 0 ){
        
        if( $month === 0 ){
            $month = $this->getMonth();
        }
        
        $firstDay = sprintf('%s-%s-01',
                strval( $year > 0 ? $year : $this->getYear() ),
                strlen($month) < 2 ? '0'.$month : $month );

        //día inicial (en valor numérico)
        $dayValue = $this->getDay($firstDay);
        //días del mes
        $monthDayCount = $this->getMonthDayCount($firstDay);
        //incrementar desde la fecha actual los días faltantes a final de mes
        $lastDay = $this->getDayOffset($firstDay, $monthDayCount-$dayValue);

        $calendar = $this->listDates( $firstDay,$lastDay );
        
        return $this->groupByMonth($calendar);
    }
    /**
     * Lista todos los días del año
     * @param int $year
     * @param int $yearCount Años adicionales sucesivos
     * @return array
     */
    public function listYear( $year = 0, $yearCount = 0 ){
        
        if( $year === 0 ){
            $year = $this->getYear();
        }
        
        $limit = $year + $yearCount;
        
        $calendar = $this->listDates(
                sprintf('%s-01-01',$year),
                sprintf('%s-12-31',$limit));
        
        return $this->groupByYear($calendar);
    }
    /**
     * Lista el planing de la temporada
     * @param int $year
     * @return array
     */
    public function listSeason( $year = 0 ){
        $year = $year >  0 ? $year : $this->getYear();
        $seasonStart = TripManager::getOption('tripman_season_start','01-01');
        $seasonEnd = TripManager::getOption('tripman_season_end','12-31');
        $calendar = $this->listDates(
                sprintf('%s-%s',$year,$seasonStart),
                sprintf('%s-%s',$year,$seasonEnd));
        
        return $this->groupByMonth($calendar);
    }
    /**
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    public function get($var, $default = null) {
        switch( $var ){
            case 'date':
                return TripManager::getDate();
            case 'current_year':
                return $this->getYear();
            case 'current_month':
                return $this->getMonth();
            case 'current_day':
                return $this->getDay();
            case 'list':
                //var_dump(parent::get('list'));
                //permite definir el parámetro list para extraer cualquiera de los formatos de tiempo seleccionados
                //semana, mes, año, temporada ...
                return $this->get('list_'.parent::get('view','month'));
            //case 'list_range':
            //case 'list_custom':
            case 'list_dates':
                $from = $this->get('date_from');
                $to = $this->get('date_to');
                
                if( !is_null($from) && !is_null($to)){
                    $dates = $this->groupByMonth( $this->listDates($from, $to) );
                    return $dates;
                }
                return $this->get('list_week');
            case 'list_month':
                
                $year = intval(parent::get('year',$this->getYear()));
                $month = intval(parent::get('month',$this->getMonth()));
                
                return $this->listMonth($month,$year);
            case 'list_week':
                //mostrar las próximas 4 semanas por defecto
                return $this->listWeek(
                        parent::get('date_from',TripManager::getDate()),
                        parent::get('date_to',TripManager::getDate()));
            case 'list_season':
                return $this->listSeason(parent::get('year',$this->getYear()));
            case 'list_year':
                return $this->listYear(
                        parent::get('year',$this->getYear()),
                        parent::get('year_to',0));
            default:
                return parent::get($var, $default);
        }
    }
    /**
     * Nombre del modelo de calendario
     * @return string
     */
    public function getName() {
        
        $class = parent::getName();
        
        $preffix = strlen(TripManager::PLUGIN_NAME);
        
        $suffix = strlen($class) - strrpos($class, 'Calendar');
        
        return substr($class, $preffix , $suffix );
    }
    /**
     * Prepara una selección anual de fechas
     * @param int $fromYear
     * @return \CODERS\Framework\Models\CalendarModel
     */
    public function setYearLayout( $fromYear, $toYear = 0 ){

        $this->set('view','year');
        
        $this->set('year',$fromYear);
        
        $this->set('year_to',$toYear);
        
        return $this;
    }
    /**
     * Prepara una selección de fechas por temporada
     * @param int $year
     * @return \CODERS\Framework\Models\CalendarModel
     */
    public function setSeasonLayout( $year ){

        $this->set('view','season');
        
        $this->set('year',$year);
        
        return $this;
    }
    /**
     * Prepara una selección mensual de fechas
     * @param int $month
     * @param int $year
     * @return \CODERS\Framework\Models\CalendarModel
     */
    public function setMonthLayout( $month, $year = 0 ){

        $this->set('view','month');
        
        $this->set('year',$year > 0 ? $year : $this->getYear() );

        $this->set('month',$month );
        
        return $this;
    }
    /**
     * Establece una selección personalizada por rango
     * @param string $dateFrom
     * @param string $dateTo
     * @param boolean $reverse
     * @return \CODERS\Framework\Models\CalendarModel
     */
    public function setRangeLayout( $dateFrom, $dateTo, $reverse = false ){
        $this->set('view','dates')
                ->set('date_from',$dateFrom)
                ->set('date_to',$dateTo)
                ->set('reverse_selection', $reverse);
        
        return $this;
    }
    /**
     * Prepara una selección semanal de fechas
     * @param string $dateFrom
     * @param string $dateTo 
     * @param boolean $cropFullWeek Incluye los días anteriores/posteriores para completar la selección de semanas
     * @param boolean $reverse Invierte el orden de selección
     * @return \CODERS\Framework\Models\CalendarModel
     */
    public function setWeekLayout( $dateFrom, $dateTo, $cropFullWeek = false, $reverse = false ){

        $this->set('view','week');
        
        $this->set('date_from', $cropFullWeek ? $this->getFirstWeekDay( $dateFrom ) : $dateFrom );
        
        $this->set('date_to', $cropFullWeek ? $this->getLastWeekDay( $dateTo )  : $dateTo );
        
        $this->set('reverse_selection',$reverse);
        
        return $this;
    }
}


