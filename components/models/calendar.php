<?php namespace CODERS\Framework;

defined('ABSPATH') or die;
/**
 * 
 */
abstract class CalendarModel extends \CODERS\Framework\Model{
    
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
    /**
     * @var string
     */
    private $_dateFormat = 'Y-m-d';
    /**
     * @var string
     */
    private $_timeFormat = 'Y-m-d H:i:s';

    protected function __construct( $endpoint , array $data = array()) {
        
        parent::__construct($endpoint , $data);

    }
    /**
     * @param string $date
     * @return int
     */
    public static final function getYear( $date = '' ){
        $time = strlen($date) ? strtotime($date) : time();
        return intval(date('Y',$time));
    }
    /**
     * @param string $date
     * @return int
     */
    public static final function getMonth( $date = '' ){
        $time = strlen($date) ? strtotime($date) : time();
        return intval(date('m',$time));
    }
    /**
     * @param string $date
     * @return int
     */
    public static final function getMonthDayCount( $date = '' ){
        $current = strlen($date) ? strtotime( $date ) : time();
        return intval(date('t',$current));
    }
    /**
     * @param string $date
     * @return int
     */
    public static final function getWeekDay( $date = '' ){
        $time = strlen($date) ? strtotime($date) : time();
        return intval(date('w', $time));
    }
    /**
     * @param int $date
     * @return int
     */
    public final function getLastWeekDay( $date = '' ){
        
        if(strlen($date) === '' ){
            $date = date($this->_dateFormat);
        }

        $weekDay = $this->getWeekDay($date);

        $offset = $weekDay > 0 ? 7 - $weekDay : 0;

        return date(
                date($this->_dateFormat),
                strtotime(sprintf('%s +%s days',$date, $offset ) ) );
    }
    /**
     * @param string $date
     * @return int
     */
    public final function getFirstWeekDay( $date = '' ){
        
        if( strlen( $date ) ){
            $date = date($this->_dateFormat);
        }
        
        $weekDay = $this->getWeekDay($date);
        $offset = $weekDay > 0 ? $weekDay-1 : 6;

        return date(
                date($this->_dateFormat),
                strtotime(sprintf('%s -%s days',$date, $offset )) );
    }
    /**
     * @param string $date
     * @return int
     */
    public static final function getDay( $date = '' ){
        $time = strlen($date) ? strtotime($date) : time();
        return intval(date('d',$time));
    }
    /**
     * @param string $date
     * @param int $offset
     * @param boolean $substract
     * @return string
     */
    public static final function offsetDay( $date = '', $offset = 7, $substract = false ){
        
        if( strlen( $date ) ){
            $date = date($this->_dateFormat);
        }
        
        $time = strtotime(sprintf('%s %s%s days',
                $date,
                $substract ? '-' : '+',
                $offset));
        
        return date($this->_dateFormat,$time);
    }
    /**
     * @param string $from
     * @param string $to
     * @return array
     */
    protected final function listPeriod( $from, $to ){

        $format = 'Y-m-d';

        $dateFrom = new DateTime( $from );
        $dateTo = new DateTime( $to );
        $interval = DateInterval::createFromDateString('1 day');
        $dateTo->add($interval);
        $period = new DatePeriod($dateFrom, $interval, $dateTo);

        $calendar = array();
        
        foreach( $period as $day ){
            
            $calendar[] = $day->format($format);
        }
        
        return $calendar;
    }
    /**
     * @param string $from
     * @param string $to
     * @param boolean $reverse Revertir
     * @return array
     */
    public final function listDates( $from, $to, $reverse = false ){
        
        return array();
    }
    /**
     * @param string $from
     * @param string $to
     * @return array
     */
    public function listWeek( $from, $to ){
        
        return array();
    }
    /**
     * @param int $month
     * @param int $year
     * @return array Lista de días del mes
     */
    public function listMonth( $month = 0, $year = 0 ){

        //group dates by month
        return array();
    }
    /**
     * @param int $current
     * @param int $count
     * @return array
     */
    public function listYear( $current = 0, $count = 0 ){
        //group dates by year
        return array();        
    }
}
