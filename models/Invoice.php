<?php

  /**
   * Model reprezentujący abstrakcyjna fakturę. Wzorzec do tworzenia faktur.
   *
   * @author Marcin Czołnowski
   * @license http://creativecommons.org/licenses/by-nd/3.0/deed.en
   * @version v0.2
   */

  // Modele którę wchodzą w relację z fakturami.
  require_once 'InvoiceSide.php';
  require_once 'InvoiceElement.php';
  
  abstract class Invoice {
  
    /**
     * Numer faktury.
     * @var string
     */
    public $number = null;

    /**
     * Treść nagłółka faktury.
     * @var string
     */
    public $headerText = 'FAKTURA VAT NR %s';

    /**
     * Miejsce wystawienia faktury.
     * @var string
     */
    public $issuePlace = null;
    
    /**
     * Data wystawienia faktury
     * @var string
     */
    protected $issueDate = null;
  
    /**
     * Data sprzedaży.
     * @var string
     */
    protected $salesDate = null;
  
    /**
     * Typ faktury.
     * @var string
     */
    protected $type = 'oryginał';
  
    /**
     * Sprzedający.
     * @var InvoiceSide
     * oo - 1
     */
    protected $seller = null;
  
    /**
     * Kupujący.
     * @var InvoiceSide
     * oo - 1
     */
    protected $buyer = null;
  
    /**
     * Nagłówek tablicy usług.
     * @var array
     */
    protected $serviceHeaders = array(
      'Nazwa usługi', 'Data realizacji', 'Ilość', 'j.m.',
      'Cena PLN', 'Stawka VAT'
    );
    
    /**
     * Opcję tablicy usług.
     * @var array
     */
    protected $serviceOptions = array(
      null, null, array( 'align' => 'center' ), array( 'align' => 'center' ),
      array( 'price' => true ), array( 'align' => 'center' )
    );
  
    /**
     * Usługi powiązane z fakturą.
     * @var array
     * 1 - oo
     */
    protected $services = array();
    
    /**
     * Data płatności.
     * @var string
     */
    protected $paymentDate = null;
    
    /**
     * Suma płatności za fakturę.
     * @var float
     */
    protected $paymentSum = 0x0;
  
    /**
     * Zmienna klasy potrzebna do uzyskania polimorfizmu.
     * @var array
     */
    protected $prices = null;
  
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
     * Zapisuję daną fakture do pliku.
     * @param Invoice $invoice Instancja faktury.
     * @param string $filename Ścieżka do pliku.
     * @return boolean
     */
    public static function save( Invoice $invoice, $filename ) {

      return (boolean) file_put_contents( $filename, serialize( $invoice ));
      //return unserialize(file_get_contents( $filename ))->number;
    
    }
    
    /**
     * Ładuje fakturę z pliku.
     * @param string $filename Ścieżka do pliku.
     * @return Invoice
     * @throws InvalidArgumentException
     */
    public static function load( $filename ) {
    
      if( !file_exists( $filename ))
        throw new InvalidArgumentException( 'Invalid path to file!' );

      $f = file_get_contents( $filename );
      $us = unserialize( $f );
        
      if( $us instanceof Invoice )
        return $us;
    
      throw new InvalidArgumentException( 'Invlaid file format!' );
    
    }
    
    /**
     * Nadaję typ fakturzę. Dostepne są tylko typy z puli: oryginał, kopia, duplikat.
     * @param string @type Nowy typ.
     * @return Invoice
     */
    public function setType( $type ) {
    
      if( in_array( $type, array( 'oryginał', 'kopia', 'duplikat' )))
          $this -> type = $type;
    
    }
    
    /**
     * Ustawia poprawną datę wystawienia faktury.
     * @param string $date Data.
     */
    public function setIssueDate( $date ) {
    
      $date = $this -> validDate( $date );
      if( !is_null( $date ))
        $this -> issueDate = $date;
    
    }
  
    /**
     * Ustawia poprawną datę sprzedaży.
     * @param string $date Data.
     */
    public function setSalesDate( $date ) {
    
      $date = $this -> validDate( $date );
      if( !is_null( $date ))
        $this -> salesDate = $date;
    
    }
    
    /**
     * Ustawia poprawnego sprzedawcę.
     * @param InvoiceSide $seller Instancja sprzedawcy.
     */
    public function setSeller( InvoiceSide $seller ) {
    
      if( !is_null( $seller ))
        $this -> seller = $seller;
    
    }
    
    /**
     * Ustawia poprawnego kupca.
     * @param InvoiceSide $buyer Instancja kupca.
     */
    public function setBuyer( InvoiceSide $buyer ) {
    
      if( !is_null( $buyer ))
        $this -> buyer = $buyer;
    
    }
    
    /**
     * Zwraca usługi w postaci tablicy dancych do tabeli.
     * @return array
     */
    public function getServices() {

      $index = 0;
      $result = array();

      if( !empty( $this -> services ))
        foreach( $this -> services as $service )
          $result[] = array_merge(
            array( ++$index ), $service -> asArray(), array(
              round( $service -> amount * $service -> price, 2 )
            )
          );
          
      return $result;
      
    }
    
    /**
     * Dodaję nowe usługi do faktury.
     * @param mixed $value Tablica usług bądź pojedyncza instancja usługi.
     */
    public function setServices( $value ) {
    
      if( is_array( $value ))        
        foreach( $value as $nextOne )
          $this -> setServices( $nextOne );
        
      elseif( $value instanceof InvoiceElement )
        $this -> services[] = $value;
    
    }
  
    /**
     * Zwraca tablicę podsumowania z podziałem na kwoty podatków.
     * @return array
     */
    public function getPrices() {
    
      if( empty( $this -> services ))
        return array();
    
      $result = array();
      foreach( $this -> services as $service ) {
        
        if( !isset( $result[ $service -> tax ]))
          $result[ $service -> tax ] = array(
            $service -> tax . '%', 0x0, 0x0, 0x0
          );
        
        $result[ $service -> tax ][ 1 ] += $service -> price * $service -> amount;
        
      }
      
      $result[ count( $result )] = array( 'Razem', 0x0, 0x0, 0x0 );
      foreach( $result as $tax => $d ) {
        
        if( $d[ 0 ] == 'Razem' )
          continue;
        
        $result[ $tax ][ 2 ] = ( $tax / 100 ) * $d[ 1 ];
        $result[ $tax ][ 3 ] = $d[ 1 ] + $result[ $tax ][ 2 ];
        
        for( $sum = 1; $sum < 4; ++$sum )
          $result[ count( $result ) - 1 ][ $sum ] += $result[ $tax ][ $sum ];
                
      }
    
      $this -> paymentSum = $result[ count( $result ) - 1 ][ 3 ];
    
      return $result;
    
    }
    
    /**
     * Ustawia odpowiednią datę płatności.
     * @param string $date Data.
     */
    public function setPaymentDate( $date ) {
    
      $date = $this -> validDate( $date );
      if( !is_null( $date ))
        $this -> paymentDate = $date;
    
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
