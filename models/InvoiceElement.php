<?php

  /**
   * Model reprezentujący usługę faktury.
   *
   * @author Marcin Czołnowski
   * @license http://creativecommons.org/licenses/by-nd/3.0/deed.en
   * @version v0.2
   */

  class InvoiceElement {
  
    /**
     * Nazwa usługi.
     * @var string
     */
    protected $name = null;
    
    /**
     * Data realizacji.
     * @var string
     */
    protected $date = null;
    
    /**
     * Ilość usług.
     * @var int
     */
    protected $amount = null;
    
    /**
     * Jednostka pojedynczej usługi.
     * @var string
     */
    protected $unit = 'usługa';
    
    /**
     * Cena pojedynczej usługi.
     * @var float
     */
    protected $price = 0;
    
    /**
     * Wartość podatku w postaci całkowitej.
     * @var int
     */
    protected $tax = 22;
    
    /**
     * Konstruktor inicjalizujący model.
     * @param string $number Numer faktury.
     */
    public function __construct( $data = array()) {
    
      if( !empty( $data ))
        foreach( $data as $var => $value )
          $this -> { $var } = $value;
    
    }
  
    /**
     * Magic setter.
     * @see http://www.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __set( $name, $value ) {
    
      if( method_exists( $this, 'set' . ucfirst( $name )))
        $this -> { 'set' . ucfirst( $name )}( $value );
      else
        $this -> { $name } = $value;
    
    }
    
    /**
     * Magic getter.
     * @see http://www.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __get( $name ) {
    
      if( method_exists( $this, 'get' . ucfirst( $name )))
        return $this -> { 'get' . ucfirst( $name )}();
    
      return $this -> { $name };
    
    }
    
    /**
     * Magic caller.
     * @see http://www.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods
     * @throws BadMethodCallException
     */
    public function __call( $name, $args ) {
    
      if( substr( $name, 0, 3 ) == 'get' )
        return $this -> { strtolower( substr( $name, 3, 1 )) . substr( $name, 4 )};
    
      throw new BadMethodCallException( 'Called method does\'t exists!' );
    
    }
    
    /**
     * Ustawia odpowiednio format daty.
     * @param string $date Data.
     */
    public function setDate( $date ) {
    
      $date = $this -> validDate( $date, 'Y-m-d H:i:s' );
      if( !is_null( $date ))
        $this -> date = $date;
    
    }
    
    /**
     * Reprezentuję usługę jako tablicę.
     * @return array
     */
    public function asArray() {
    
      return array(
        $this -> name, $this -> date, $this -> amount,
        $this -> unit, $this -> price, $this -> tax . '%'
      );
    
    }
    
    /**
     * Przetwarza datę na odpowiedni format.
     * @param string $date Data.
     * @param string $format Format daty.
     * @return string
     */
    final protected function validDate( $date, $format = 'Y-m-d' ) {
    
      return strtotime( $date ) ? date( $format, strtotime( $date )) : null;
    
    }
    
  }
