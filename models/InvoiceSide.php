<?php

  /**
   * Model reprezentujący stronę transkacji faktury.
   *
   * @author Marcin Czołnowski
   * @license http://creativecommons.org/licenses/by-nd/3.0/deed.en
   * @version v0.2
   */
   
  class InvoiceSide {
  
    /**
     * Nazwa strony transakcji.
     * @var string
     */
    public $name = null;
    
    /**
     * Adres strony transakcji.
     * @var string
     */
    public $address = null;
  
    /**
     * Miasto strony transakcji.
     * @var string
     */
    public $city = null;
  
    /**
     * Kod pocztowy miasta strony transakcji.
     * @var string
     */
    public $zip = null;
  
    /**
     * NIP strony transakcji.
     * @var string
     */
    public $nip = null;
    
    /**
     * Numer telefonu strony transakcji.
     * @var string
     */
    public $phone = null;
  
    /**
     * Numer fax strony transakcji.
     * @var string
     */
    public $fax = null;
  
    /**
     * Numer konta bankowanego strony transakcji.
     * @var string
     */
    public $bank_account = null;
  
    /**
     * Treść podpisu składanego na fakturze.
     * @var string
     */
    public $sign = null;
  
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
     * Zwraca kod pocztowy połączony z miastem.
     * @return string
     */
    public function getZip_City() {
    
      return $this -> zip . ' ' . $this -> city;
    
    }
  
  }
